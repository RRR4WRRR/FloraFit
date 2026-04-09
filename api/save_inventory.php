<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/db.php';
require_once '../includes/stock_sms_helper.php';

ensureStockNotificationsTable($conn);
$config = getSemaphoreStockConfig();

// ------------------------
// Collect POST data
// ------------------------
$id       = $_POST['id'] ?? null;
$sku      = trim((string)($_POST['sku'] ?? ''));
$name     = trim((string)($_POST['name'] ?? ''));
$variety  = trim((string)($_POST['variety'] ?? ''));
$category = trim((string)($_POST['category'] ?? ''));
$stock    = (int)($_POST['stock'] ?? 0);
$price    = (float)($_POST['price'] ?? 0.00);
$image    = (string)($_POST['existing_image'] ?? '');

// ------------------------
// Auto-generate SKU
// ------------------------
if ($sku === '') {
    $sku = generateSKU();
}

// ------------------------
// Handle image upload
// ------------------------
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    // Absolute path to FloraFit/uploads/inventory/ regardless of where this script lives
    $targetDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/FloraFit/uploads/inventory/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowedExts, true)) {
        $imageName = $sku . '_' . time() . '.' . $ext;
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $imageName; // Store ONLY the filename in DB
        } else {
            error_log('IMAGE UPLOAD FAILED: Could not move file to ' . $targetFile);
        }
    } else {
        error_log('IMAGE UPLOAD FAILED: Invalid extension ' . $ext);
    }
}

// ------------------------
// Save or update inventory
// ------------------------
try {
    $previousStock = null;

    if ($id) {
        $stmt = $conn->prepare('SELECT stock FROM inventory WHERE id = ?');
        $stmt->execute([$id]);
        $previousValue = $stmt->fetchColumn();
        $previousStock = $previousValue === false ? null : (int)$previousValue;

        $stmt = $conn->prepare('UPDATE inventory SET sku=?, name=?, variety=?, category=?, stock=?, price=?, image=? WHERE id=?');
        $stmt->execute([$sku, $name, $variety, $category, $stock, $price, $image, $id]);
    } else {
        $stmt = $conn->prepare('INSERT INTO inventory (sku, name, variety, category, stock, price, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$sku, $name, $variety, $category, $stock, $price, $image]);
        $id = (int)$conn->lastInsertId();
    }

    $smsStatus = maybeSendCriticalStockAlert(
        $conn,
        $config,
        (int)$id,
        $name,
        $variety,
        $category,
        $stock,
        $previousStock
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Item saved successfully!',
        'id' => $id,
        'sms' => $smsStatus,
    ]);
} catch (PDOException $e) {
    error_log('SQL ERROR: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $e->getMessage()]);
}

// ------------------------
// Generate SKU FL-001, FL-002, etc.
// ------------------------
function generateSKU(): string
{
    global $conn;
    $stmt = $conn->prepare("SELECT sku FROM inventory WHERE sku LIKE 'FL-%' ORDER BY CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row && preg_match('/FL-(\d+)/', (string)$row['sku'], $matches)) {
        $next = (int)$matches[1] + 1;
    } else {
        $next = 1;
    }

    return sprintf('FL-%03d', $next);
}

function maybeSendCriticalStockAlert(PDO $conn, array $config, int $itemId, string $itemName, string $variety, string $category, int $stock, ?int $previousStock): array
{
    $threshold = max(1, (int)($config['threshold'] ?? 10));
    $crossedIntoCritical = $stock > 0 && $stock <= $threshold && ($previousStock === null || $previousStock > $threshold);

    if (!$crossedIntoCritical) {
        return [
            'attempted' => false,
            'message' => 'No SMS needed for this stock update.',
        ];
    }

    if ($itemId > 0 && alreadyNotifiedTodayByItem($conn, $itemId, 'critical')) {
        return [
            'attempted' => false,
            'message' => 'A critical stock SMS was already sent today for this item.',
        ];
    }

    if (($config['apiKey'] ?? '') === '' || ($config['to'] ?? '') === '') {
        return [
            'attempted' => false,
            'message' => 'SMS skipped because Semaphore settings (apiKey or to) are missing in .env.',
        ];
    }

    $messageLines = [
        'FLORA FIT - CRITICAL STOCK ALERT',
        date('M d, Y h:i A'),
        '================================',
        'Item: ' . $itemName,
    ];

    if ($variety !== '') {
        $messageLines[] = 'Variety: ' . $variety;
    }

    if ($category !== '') {
        $messageLines[] = 'Category: ' . $category;
    }

    $messageLines[] = 'Stock: ' . $stock . ' units left';

    if ($previousStock !== null) {
        $messageLines[] = 'Previous: ' . $previousStock . ' units';
    }

    $messageLines[] = 'RESTOCK IMMEDIATELY.';

    $result = sendSemaphoreSmsMessage(
        (string)$config['apiKey'],
        (string)$config['to'],
        implode("\n", $messageLines),
        (string)($config['senderName'] ?? 'SEMAPHORE')
    );

    if ($result['success']) {
        logStockNotificationEntry($conn, $itemId, $itemName, $stock, 'critical');
    } else {
        error_log('[FloraFit] save_inventory critical SMS failed: ' . $result['message']);
    }

    return [
        'attempted' => true,
        'success' => (bool)$result['success'],
        'message' => (string)($result['message'] ?? 'Unknown SMS status.'),
        'sid' => $result['sid'] ?? null,
    ];
}
?>