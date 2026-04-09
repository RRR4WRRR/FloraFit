<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

function normalizeDateParam($value, $fallback) {
    $value = trim((string)$value);
    if ($value === '') return $fallback;

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : $fallback;
}

function normalizeCategoryKey($category) {
    $value = strtolower(trim((string)$category));
    if ($value === 'flower' || $value === 'flowers') return 'flower';
    if ($value === 'filler' || $value === 'fillers') return 'filler';
    if ($value === 'green' || $value === 'greenery') return 'greenery';
    return $value;
}

function buildDateSeries($startDate, $endDate, array $dailyMap) {
    $series = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {
        $key = $current->format('Y-m-d');
        $entry = $dailyMap[$key] ?? ['order_count' => 0, 'revenue' => 0, 'commission' => 0];
        $series[] = [
            'date' => $key,
            'label' => $current->format('M d'),
            'order_count' => (int)($entry['order_count'] ?? 0),
            'revenue' => (float)($entry['revenue'] ?? 0),
            'commission' => (float)($entry['commission'] ?? 0),
        ];
        $current->modify('+1 day');
    }

    return $series;
}

try {
    $defaultStart = date('Y-m-01');
    $defaultEnd = date('Y-m-d');

    $startDate = normalizeDateParam($_GET['start_date'] ?? '', $defaultStart);
    $endDate = normalizeDateParam($_GET['end_date'] ?? '', $defaultEnd);

    if ($startDate > $endDate) {
        [$startDate, $endDate] = [$endDate, $startDate];
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode([
            'success' => true,
            'range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'label' => $startDate === $endDate ? date('F j, Y', strtotime($startDate)) : date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate))
            ],
            'summary' => [
                'order_count' => 0,
                'total_revenue' => 0,
                'top_flower' => ['name' => 'None', 'quantity' => 0],
                'top_filler' => ['name' => 'None', 'quantity' => 0],
                'top_greenery' => ['name' => 'None', 'quantity' => 0],
            ],
            'status_counts' => [],
            'daily' => []
        ]);
        exit;
    }

    $summaryStmt = $conn->prepare("SELECT COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS total_revenue, COALESCE(SUM(florist_commission), 0) AS total_commission FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
    $summaryStmt->execute([$startDate, $endDate]);
    $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: ['order_count' => 0, 'total_revenue' => 0, 'total_commission' => 0];

    $dailyStmt = $conn->prepare("SELECT DATE(created_at) AS order_date, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS revenue, COALESCE(SUM(florist_commission), 0) AS commission FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC");
    $dailyStmt->execute([$startDate, $endDate]);
    $dailyRows = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

    $dailyMap = [];
    foreach ($dailyRows as $row) {
        $dailyMap[$row['order_date']] = [
            'order_count' => (int)($row['order_count'] ?? 0),
            'revenue' => (float)($row['revenue'] ?? 0),
            'commission' => (float)($row['commission'] ?? 0),
        ];
    }

    $statusStmt = $conn->prepare("SELECT LOWER(COALESCE(status, 'Pending')) AS status_key, COUNT(*) AS total FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY LOWER(COALESCE(status, 'Pending')) ORDER BY total DESC");
    $statusStmt->execute([$startDate, $endDate]);
    $statusCounts = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    $topItems = [
        'flower' => ['name' => 'None', 'quantity' => 0],
        'filler' => ['name' => 'None', 'quantity' => 0],
        'greenery' => ['name' => 'None', 'quantity' => 0],
    ];

    $hasOrderItemsTable = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
    $hasInventoryTable = $conn->query("SHOW TABLES LIKE 'inventory'")->rowCount() > 0;

    if ($hasOrderItemsTable && $hasInventoryTable) {
        $topStmt = $conn->prepare("SELECT COALESCE(i.name, 'Unknown Item') AS item_name, COALESCE(i.category, '') AS category_name, SUM(COALESCE(oi.quantity, 1)) AS total_quantity
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN inventory i ON i.id = oi.inventory_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY COALESCE(i.name, 'Unknown Item'), COALESCE(i.category, '')
            ORDER BY total_quantity DESC, item_name ASC");
        $topStmt->execute([$startDate, $endDate]);
        $topRows = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($topRows as $row) {
            $categoryKey = normalizeCategoryKey($row['category_name'] ?? '');
            if (!isset($topItems[$categoryKey])) {
                continue;
            }
            if ((int)$topItems[$categoryKey]['quantity'] > 0) {
                continue;
            }

            $topItems[$categoryKey] = [
                'name' => (string)($row['item_name'] ?? 'Unknown Item'),
                'quantity' => (int)($row['total_quantity'] ?? 0),
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'range' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $startDate === $endDate
                ? date('F j, Y', strtotime($startDate))
                : date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate))
        ],
        'summary' => [
            'order_count' => (int)($summaryRow['order_count'] ?? 0),
            'total_revenue' => (float)($summaryRow['total_revenue'] ?? 0),
            'top_flower' => $topItems['flower'],
            'top_filler' => $topItems['filler'],
            'top_greenery' => $topItems['greenery'],
        ],
        'status_counts' => array_map(function ($row) {
            return [
                'status' => ucfirst((string)($row['status_key'] ?? 'pending')),
                'total' => (int)($row['total'] ?? 0),
            ];
        }, $statusCounts),
        'daily' => buildDateSeries($startDate, $endDate, $dailyMap),
        'generated_at' => date('c')
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'summary' => [
            'order_count' => 0,
            'total_revenue' => 0,
            'top_flower' => ['name' => 'None', 'quantity' => 0],
            'top_filler' => ['name' => 'None', 'quantity' => 0],
            'top_greenery' => ['name' => 'None', 'quantity' => 0],
        ],
        'status_counts' => [],
        'daily' => []
    ]);
}
?>
