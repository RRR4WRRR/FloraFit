<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';
require_once '../includes/stock_sms_helper.php';

ensureStockNotificationsTable($conn);
$config = getSemaphoreStockConfig();
$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$criticalItems = is_array($input['critical_items'] ?? null) ? $input['critical_items'] : [];

if ($config['apiKey'] === '' || $config['to'] === '') {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Semaphore settings are missing in .env. Add SEMAPHORE_API_KEY and SEMAPHORE_TO first.'
    ]);
    exit;
}

if (!$criticalItems) {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'message' => 'No critical items were provided.'
    ]);
    exit;
}

$itemsToNotify = [];
foreach ($criticalItems as $item) {
    $itemId = (int)($item['id'] ?? 0);
    $itemName = trim((string)($item['name'] ?? 'Unknown Item'));
    $itemStock = (int)($item['stock'] ?? 0);

    $alreadySent = $itemId > 0
        ? alreadyNotifiedTodayByItem($conn, $itemId, 'critical')
        : alreadyNotifiedTodayByName($conn, $itemName, 'critical');

    if (!$alreadySent) {
        $itemsToNotify[] = [
            'id' => $itemId,
            'name' => $itemName,
            'stock' => $itemStock,
        ];
    }
}

if (!$itemsToNotify) {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'message' => 'All provided critical items were already notified today.'
    ]);
    exit;
}

$lines = [
    'FLORA FIT CRITICAL STOCK ALERT',
    date('M d, Y h:i A'),
    '================================'
];

foreach ($itemsToNotify as $item) {
    $lines[] = '- ' . $item['name'] . ': ' . $item['stock'] . ' units left';
}

$lines[] = 'RESTOCK NOW!';
$message = implode("\n", $lines);
$result = sendSemaphoreSmsMessage($config['apiKey'], $config['to'], $message, $config['senderName']);

if (!$result['success']) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Semaphore SMS failed: ' . $result['message'],
        'httpCode' => $result['httpCode'] ?? 0
    ]);
    exit;
}

foreach ($itemsToNotify as $item) {
    logStockNotificationEntry($conn, ($item['id'] > 0 ? $item['id'] : null), $item['name'], $item['stock'], 'critical');
}

echo json_encode([
    'success' => true,
    'count' => count($itemsToNotify),
    'message' => 'Semaphore SMS queued successfully.',
    'messageId' => $result['messageId'] ?? null,
    'status' => $result['status'] ?? null
]);
?>
