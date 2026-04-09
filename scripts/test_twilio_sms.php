<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/stock_sms_helper.php';

$config = getSemaphoreStockConfig();
$message = 'FloraFit SMS check - ' . date('M d, Y h:i A');
$result = sendSemaphoreSmsMessage(
    $config['apiKey'],
    $config['to'],
    $message,
    $config['senderName'] ?? 'SEMAPHORE'
);

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message'],
    'httpCode' => $result['httpCode'] ?? 0,
    'messageId' => $result['messageId'] ?? null,
    'status' => $result['status'] ?? null
]);
?>
