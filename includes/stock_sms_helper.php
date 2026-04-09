<?php

function loadProjectEnvVars(?string $path = null): array
{
    // Look for .env in the project root (one level above /includes/)
    $envPath = $path ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    $values = [];

    if (!is_file($envPath)) {
        return $values;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $values[$name] = $value;
    }

    return $values;
}

function getSemaphoreStockConfig(): array
{
    $env = loadProjectEnvVars();

    return [
        'apiKey' => trim((string)($env['SEMAPHORE_API_KEY'] ?? '')),
        'senderName' => trim((string)($env['SEMAPHORE_SENDER_NAME'] ?? 'SEMAPHORE')),
        'to' => normalizeSemaphoreNumber((string)($env['SEMAPHORE_TO'] ?? '')),
        'threshold' => max(1, (int)($env['STOCK_CRITICAL_THRESHOLD'] ?? 10)),
    ];
}

function normalizeSemaphoreNumber(string $number): string
{
    $digits = preg_replace('/\D+/', '', trim($number)) ?? '';

    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '63') && strlen($digits) === 12) {
        return '0' . substr($digits, 2);
    }

    if (str_starts_with($digits, '9') && strlen($digits) === 10) {
        return '0' . $digits;
    }

    return $digits;
}

function ensureStockNotificationsTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS stock_notifications (
        id INT(11) NOT NULL AUTO_INCREMENT,
        item_id INT(11) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        stock_level INT(11) NOT NULL DEFAULT 0,
        notification_type VARCHAR(50) NOT NULL DEFAULT 'critical',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_item_id (item_id),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function alreadyNotifiedTodayByItem(PDO $conn, int $itemId, string $type = 'critical'): bool
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM stock_notifications WHERE item_id = ? AND notification_type = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$itemId, $type]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        error_log('[FloraFit] stock_notifications check failed: ' . $e->getMessage());
        return false;
    }
}

function alreadyNotifiedTodayByName(PDO $conn, string $itemName, string $type = 'critical'): bool
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM stock_notifications WHERE item_name = ? AND notification_type = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$itemName, $type]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        error_log('[FloraFit] stock_notifications name check failed: ' . $e->getMessage());
        return false;
    }
}

function logStockNotificationEntry(PDO $conn, ?int $itemId, string $itemName, int $stockLevel, string $type = 'critical'): void
{
    try {
        $stmt = $conn->prepare("INSERT INTO stock_notifications (item_id, item_name, stock_level, notification_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$itemId, $itemName, $stockLevel, $type]);
    } catch (Throwable $e) {
        error_log('[FloraFit] stock_notifications insert failed: ' . $e->getMessage());
    }
}

function getTelegramConfig(): array
{
    $env = loadProjectEnvVars();
    return [
        'token' => trim((string)($env['TELEGRAM_BOT_TOKEN'] ?? '')),
        'chatId' => trim((string)($env['TELEGRAM_CHAT_ID'] ?? '')),
    ];
}

function sendTelegramNotification(string $token, string $chatId, string $message): array
{
    if (empty($token) || empty($chatId)) {
        return ['success' => false, 'message' => 'Telegram configuration missing.'];
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode((string)$response, true);
    $success = ($httpCode === 200 && ($decoded['ok'] ?? false));

    return [
        'success' => $success,
        'httpCode' => $httpCode,
        'message' => $success ? 'Telegram notification sent.' : ($decoded['description'] ?? 'Failed to send Telegram message.'),
        'response' => $decoded
    ];
}

function sendSemaphoreSmsMessage(string $apiKey, string $to, string $message, string $senderName = 'SEMAPHORE'): array
{
    $looksLikePlaceholder = static function (string $value): bool {
        $normalized = strtoupper(trim($value));
        return $normalized === ''
            || str_contains($normalized, 'PASTE_')
            || str_contains($normalized, 'YOUR_')
            || $normalized === 'SEMAPHORE_API_KEY_HERE'
            || $normalized === '09123456789';
    };

    if ($looksLikePlaceholder($apiKey) || $looksLikePlaceholder($to)) {
        return [
            'success' => false,
            'httpCode' => 0,
            'message' => 'Semaphore settings are missing in .env.',
            'response' => null,
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'httpCode' => 0,
            'message' => 'PHP cURL is not enabled on this server.',
            'response' => null,
        ];
    }

    $payload = [
        'apikey' => $apiKey,
        'number' => normalizeSemaphoreNumber($to),
        'message' => $message,
    ];

    $senderName = trim($senderName);
    if ($senderName !== '') {
        $payload['sendername'] = $senderName;
    }

    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'httpCode' => $httpCode,
            'message' => 'Semaphore cURL error: ' . $curlError,
            'response' => null,
        ];
    }

    $decoded = json_decode((string)$response, true);
    $firstResult = is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])
        ? $decoded[0]
        : (is_array($decoded) ? $decoded : []);

    $success = $httpCode >= 200 && $httpCode < 300 && !empty($firstResult['message_id']);
    $messageText = $success
        ? 'Semaphore SMS queued successfully.'
        : trim((string)($firstResult['message'] ?? $firstResult['error'] ?? ('Semaphore request failed with HTTP ' . $httpCode)));

    return [
        'success' => $success,
        'httpCode' => $httpCode,
        'message' => $messageText,
        'messageId' => $firstResult['message_id'] ?? null,
        'status' => $firstResult['status'] ?? null,
        'response' => $decoded,
    ];
}