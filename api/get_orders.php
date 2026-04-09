<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => true, 'orders' => []]);
        exit;
    }

    $hasAssignedFloristColumn = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'assigned_florist'")->fetchColumn();
    if ((int)$hasAssignedFloristColumn === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN assigned_florist VARCHAR(100) NULL AFTER status");
    }

    $hasPaymentMethodColumn = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method'")->fetchColumn();
    if ((int)$hasPaymentMethodColumn === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER assigned_florist");
    }

    $hasPaymentStatusColumn = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_status'")->fetchColumn();
    if ((int)$hasPaymentStatusColumn === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid' AFTER payment_method");
    }

    $hasPaymentConfirmedAtColumn = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_confirmed_at'")->fetchColumn();
    if ((int)$hasPaymentConfirmedAtColumn === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN payment_confirmed_at DATETIME NULL AFTER payment_status");
    }

    $stmt = $conn->query("SELECT
            o.id,
            CONCAT('ORD-', LPAD(o.id, 6, '0')) AS order_number,
            COALESCE(u.full_name, 'Guest') AS customer_name,
            '' AS recipient_name,
            COALESCE(o.assigned_florist, 'Unassigned') AS assigned_florist,
            COALESCE(o.status, 'Pending') AS status,
            COALESCE(o.payment_method, '') AS payment_method,
            COALESCE(o.payment_status, 'Unpaid') AS payment_status,
            o.payment_confirmed_at,
            COALESCE(o.total, 0) AS total,
            COALESCE(o.florist_commission, 0) AS florist_commission,
            o.created_at
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        ORDER BY o.id DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orders) > 0) {
        $orderIds = array_map(function ($order) {
            return (int)$order['id'];
        }, $orders);

        $itemsByOrder = [];

        $hasMetaTable = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
        if ($hasMetaTable) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $metaStmt = $conn->prepare("SELECT order_id, item_name, item_description, item_image, quantity FROM order_item_meta WHERE order_id IN ($placeholders) ORDER BY id ASC");
            $metaStmt->execute($orderIds);
            $metaRows = $metaStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($metaRows as $row) {
                $orderId = (int)$row['order_id'];
                if (!isset($itemsByOrder[$orderId])) {
                    $itemsByOrder[$orderId] = [];
                }
                $itemsByOrder[$orderId][] = [
                    'name' => $row['item_name'] ?? 'Item',
                    'description' => $row['item_description'] ?? '',
                    'image' => $row['item_image'] ?? '',
                    'quantity' => (int)($row['quantity'] ?? 1)
                ];
            }
        }

        $hasOrderItemsTable = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
        $hasInventoryTable = $conn->query("SHOW TABLES LIKE 'inventory'")->rowCount() > 0;
        if ($hasOrderItemsTable && $hasInventoryTable) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $fallbackStmt = $conn->prepare("SELECT oi.order_id, oi.quantity, i.name, i.variety, i.category, i.image
                FROM order_items oi
                LEFT JOIN inventory i ON i.id = oi.inventory_id
                WHERE oi.order_id IN ($placeholders)");
            $fallbackStmt->execute($orderIds);
            $fallbackRows = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($fallbackRows as $row) {
                $orderId = (int)$row['order_id'];
                if (!isset($itemsByOrder[$orderId])) {
                    $itemsByOrder[$orderId] = [];
                }

                $exists = false;
                foreach ($itemsByOrder[$orderId] as $existingItem) {
                    if (($existingItem['name'] ?? '') === ($row['name'] ?? '')) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $descriptionParts = [];
                    if (!empty($row['variety'])) $descriptionParts[] = $row['variety'];
                    if (!empty($row['category'])) $descriptionParts[] = $row['category'];

                    $itemsByOrder[$orderId][] = [
                        'name' => $row['name'] ?? 'Inventory Item',
                        'description' => count($descriptionParts) ? implode(' • ', $descriptionParts) : '',
                        'image' => $row['image'] ?? '',
                        'quantity' => (int)($row['quantity'] ?? 1)
                    ];
                }
            }
        }

        foreach ($orders as &$order) {
            $orderId = (int)$order['id'];
            $order['items'] = $itemsByOrder[$orderId] ?? [];
        }
        unset($order);
    }

    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'orders' => [], 'message' => $e->getMessage()]);
}
?>

