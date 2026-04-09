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
    $orderId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order id']);
        exit;
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => false, 'message' => 'Orders table not found']);
        exit;
    }

    ensureOrderPaymentSchema($conn);

    $findStmt = $conn->prepare("SELECT id, COALESCE(payment_status, 'Unpaid') AS payment_status FROM orders WHERE id = ? LIMIT 1");
    $findStmt->execute([$orderId]);
    $order = $findStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $paymentStatus = strtolower(trim((string)($order['payment_status'] ?? 'unpaid')));
    if ($paymentStatus !== 'pending confirmation') {
        if ($paymentStatus === 'paid') {
            echo json_encode(['success' => true, 'message' => 'Payment already marked as paid']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Order payment is not awaiting confirmation']);
        exit;
    }

    $updateStmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid' WHERE id = ?");
    $updateStmt->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Order payment marked as paid']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

