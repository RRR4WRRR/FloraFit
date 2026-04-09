<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

function ensureOrderPaymentSchema(PDO $conn): void {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        return;
    }

    $columnChecks = [
        'assigned_florist' => "ALTER TABLE orders ADD COLUMN assigned_florist VARCHAR(100) NULL AFTER status",
        'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER assigned_florist",
        'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid' AFTER payment_method",
        'payment_confirmed_at' => "ALTER TABLE orders ADD COLUMN payment_confirmed_at DATETIME NULL AFTER payment_status"
    ];

    foreach ($columnChecks as $columnName => $alterSql) {
        $exists = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = '{$columnName}'")->fetchColumn();
        if ((int)$exists === 0) {
            $conn->exec($alterSql);
        }
    }
}

try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $orderId = isset($payload['order_id']) ? (int)$payload['order_id'] : 0;
    $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
    $paymentMethod = trim((string)($payload['payment_method'] ?? 'GCash'));

    if ($orderId <= 0 || $userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order or user']);
        exit;
    }

    if (strcasecmp($paymentMethod, 'GCash') !== 0) {
        echo json_encode(['success' => false, 'message' => 'Only GCash payment is supported']);
        exit;
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => false, 'message' => 'Orders table not found']);
        exit;
    }

    ensureOrderPaymentSchema($conn);

    $findStmt = $conn->prepare("SELECT id, user_id, status, COALESCE(payment_status, 'Unpaid') AS payment_status FROM orders WHERE id = ? LIMIT 1");
    $findStmt->execute([$orderId]);
    $order = $findStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || (int)$order['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $status = trim((string)($order['status'] ?? 'Pending'));
    if (!in_array($status, ['Accepted', 'Preparing', 'Delivering', 'Delivered'], true)) {
        echo json_encode(['success' => false, 'message' => 'Order is not confirmed for payment yet']);
        exit;
    }

    $currentPaymentStatus = strtolower(trim((string)($order['payment_status'] ?? 'unpaid')));
    if ($currentPaymentStatus !== 'unpaid') {
        echo json_encode(['success' => false, 'message' => 'Payment was already submitted']);
        exit;
    }

    $updateStmt = $conn->prepare("UPDATE orders SET payment_method = 'GCash', payment_status = 'Pending Confirmation', payment_confirmed_at = NOW() WHERE id = ? AND user_id = ?");
    $updateStmt->execute([$orderId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Payment confirmation submitted']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

