<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

function ensureOrdersFloristSchema(PDO $conn): void {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        return;
    }

    $hasAssignedFloristColumn = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'assigned_florist'")->fetchColumn();
    if ((int)$hasAssignedFloristColumn === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN assigned_florist VARCHAR(100) NULL AFTER status");
    }
}

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $florist = trim((string)($_POST['florist'] ?? ''));

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order id']);
        exit;
    }

    if ($florist === '') {
        $florist = 'Unassigned';
    }

    if (mb_strlen($florist) > 100) {
        echo json_encode(['success' => false, 'message' => 'Florist name is too long']);
        exit;
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => false, 'message' => 'Orders table not found']);
        exit;
    }

    ensureOrdersFloristSchema($conn);

    $stmt = $conn->prepare("UPDATE orders SET assigned_florist = ? WHERE id = ?");
    $stmt->execute([$florist, $id]);

    if ($stmt->rowCount() === 0) {
        $existsStmt = $conn->prepare("SELECT id FROM orders WHERE id = ? LIMIT 1");
        $existsStmt->execute([$id]);
        if (!$existsStmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Florist updated']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

