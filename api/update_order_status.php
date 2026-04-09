<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../includes/db.php';

// Admins can update any order; florists can update orders assigned to them.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

function ensureOrdersSchema(PDO $conn): void {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) return;

    $columnTypeStmt = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'status' LIMIT 1");
    $columnType = strtolower((string) $columnTypeStmt->fetchColumn());

    if (strpos($columnType, 'enum(') === 0) {
        $requiredStatuses = ['pending', 'accepted', 'declined', 'preparing', 'delivering', 'delivered'];
        $missing = false;
        foreach ($requiredStatuses as $statusValue) {
            if (strpos($columnType, "'{$statusValue}'") === false) {
                $missing = true;
                break;
            }
        }
        if ($missing) {
            $conn->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('Pending','Accepted','Declined','Preparing','Delivering','Delivered') DEFAULT 'Pending'");
        }
    }

    $checks = [
        'inventory_deducted'  => "ALTER TABLE orders ADD COLUMN inventory_deducted TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
        'assigned_florist'    => "ALTER TABLE orders ADD COLUMN assigned_florist VARCHAR(100) NULL AFTER status",
        'payment_method'      => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER assigned_florist",
        'payment_status'      => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid' AFTER payment_method",
        'payment_confirmed_at'=> "ALTER TABLE orders ADD COLUMN payment_confirmed_at DATETIME NULL AFTER payment_status",
    ];

    foreach ($checks as $column => $sql) {
        $exists = (int) $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = '{$column}'")->fetchColumn();
        if ($exists === 0) {
            $conn->exec($sql);
        }
    }
}

try {
    $id     = isset($_POST['id'])     ? (int) $_POST['id']              : 0;
    $status = isset($_POST['status']) ? trim((string) $_POST['status']) : '';

    $allowed = ['Pending', 'Accepted', 'Declined', 'Preparing', 'Delivering', 'Delivered'];

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if (!in_array($status, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => false, 'message' => 'Orders table not found']);
        exit;
    }

    ensureOrdersSchema($conn);

    $conn->beginTransaction();

    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $isAdminLike = in_array($role, ['admin', 'staff'], true);

    $orderStmt = $conn->prepare("
        SELECT id, COALESCE(inventory_deducted, 0) AS inventory_deducted, COALESCE(assigned_florist, '') AS assigned_florist
        FROM orders
        WHERE id = ?
        FOR UPDATE
    ");
    $orderStmt->execute([$id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if (!$isAdminLike) {
        $userStmt = $conn->prepare("SELECT TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS full_name FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([(int)$_SESSION['user_id']]);
        $userName = trim((string)$userStmt->fetchColumn());
        $assignedFlorist = trim((string)($order['assigned_florist'] ?? ''));

        $matchesAssignedFlorist = $assignedFlorist !== '' && (
            strcasecmp($assignedFlorist, $userName) === 0 ||
            strcasecmp($assignedFlorist, (string)$_SESSION['user_id']) === 0
        );

        if (!$matchesAssignedFlorist) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Order not assigned to this florist']);
            exit;
        }
    }

    // Deduct inventory only once when order is first Accepted
    $shouldDeduct = ($status === 'Accepted' && (int) $order['inventory_deducted'] === 0);

    if ($shouldDeduct) {
        $hasOrderItemsTable = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
        $hasInventoryTable  = $conn->query("SHOW TABLES LIKE 'inventory'")->rowCount() > 0;

        if ($hasOrderItemsTable && $hasInventoryTable) {
            $itemsStmt = $conn->prepare("SELECT inventory_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$id]);
            $rows = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $decrementStock = $conn->prepare("UPDATE inventory SET stock = GREATEST(stock - ?, 0) WHERE id = ?");
            foreach ($rows as $row) {
                $inventoryId = (int) ($row['inventory_id'] ?? 0);
                $qty         = max(0, (int) ($row['quantity'] ?? 0));
                if ($inventoryId > 0 && $qty > 0) {
                    $decrementStock->execute([$qty, $inventoryId]);
                }
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ?, inventory_deducted = 1 WHERE id = ?");
        $stmt->execute([$status, $id]);

    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order status updated']);

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
