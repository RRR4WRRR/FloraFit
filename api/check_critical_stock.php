<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';
require_once '../includes/stock_sms_helper.php';

$config = getSemaphoreStockConfig();
ensureStockNotificationsTable($conn);

try {
    if ($config['apiKey'] === '' || $config['to'] === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Semaphore settings are missing in .env. Fill in SEMAPHORE_API_KEY and SEMAPHORE_TO first.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, name, variety, category, stock
        FROM inventory
        WHERE stock <= ?
        ORDER BY stock ASC
    ");
    $stmt->execute([$config['threshold']]);
    $criticalItems = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (!$criticalItems) {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'message' => 'No critical stock items found.'
        ]);
        exit;
    }

    $itemsToNotify = [];
    foreach ($criticalItems as $item) {
        if (!alreadyNotifiedTodayByItem($conn, (int)$item['id'], 'critical')) {
            $itemsToNotify[] = $item;
        }
    }

    if (!$itemsToNotify) {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'message' => 'All critical items were already notified today.'
        ]);
        exit;
    }

    $smsBody = "FLORA FIT - CRITICAL STOCK ALERT\n";
    $smsBody .= date('M d, Y h:i A') . "\n";
    $smsBody .= "================================\n";

    foreach ($itemsToNotify as $item) {
        $smsBody .= "\n- {$item['name']} ({$item['variety']})\n";
        $smsBody .= "  Category: {$item['category']}\n";
        $smsBody .= "  Stock: {$item['stock']} units left\n";
    }

    $smsBody .= "\nRESTOCK IMMEDIATELY.";

    $result = sendSemaphoreSmsMessage(
        $config['apiKey'],
        $config['to'],
        $smsBody,
        $config['senderName']
    );

    if (!$result['success']) {
        error_log('[FloraFit] Semaphore SMS failed: ' . $result['message']);
        echo json_encode([
            'success' => false,
            'count' => 0,
            'message' => 'Semaphore SMS failed: ' . $result['message'],
            'httpCode' => $result['httpCode'] ?? 0
        ]);
        exit;
    }

    foreach ($itemsToNotify as $item) {
        logStockNotificationEntry($conn, (int)$item['id'], (string)$item['name'], (int)$item['stock'], 'critical');
    }

    error_log('[FloraFit] SMS sent for ' . count($itemsToNotify) . ' critical item(s).');
    echo json_encode([
        'success' => true,
        'count' => count($itemsToNotify),
        'message' => 'Semaphore SMS queued successfully.',
        'messageId' => $result['messageId'] ?? null,
        'status' => $result['status'] ?? null
    ]);
} catch (Throwable $e) {
    error_log('[FloraFit] Fatal Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Critical stock check failed: ' . $e->getMessage()
    ]);
}
?>
