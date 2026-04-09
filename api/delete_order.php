<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order id']);
        exit;
    }

    $conn->beginTransaction();

    $hasMeta = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
    if ($hasMeta) {
        $stmtMeta = $conn->prepare("DELETE FROM order_item_meta WHERE order_id = ?");
        $stmtMeta->execute([$id]);
    }

    $hasOrderItems = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
    if ($hasOrderItems) {
        $stmtItems = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$id]);
    }

    $stmtOrder = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmtOrder->execute([$id]);

    if ($stmtOrder->rowCount() === 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order deleted']);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


