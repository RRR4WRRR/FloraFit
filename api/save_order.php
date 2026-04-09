<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';
require_once '../includes/stock_sms_helper.php';

function safeNumber($value, $default = 0): float {
    return is_numeric($value) ? (float)$value : (float)$default;
}

function isValidEmailAddress(?string $value): bool {
    $email = trim((string)$value);
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhilippineMobile(?string $value): bool {
    $clean = preg_replace('/[^\d+]/', '', (string)$value);
    return (bool)preg_match('/^(\+63|0)9\d{9}$/', $clean);
}

function combineAddressParts(array $recipient): string {
    $parts = [
        trim((string)($recipient['addressLine'] ?? '')),
        trim((string)($recipient['districtBarangay'] ?? '')),
        trim((string)($recipient['city'] ?? '')),
        trim((string)($recipient['provinceMunicipality'] ?? '')),
        trim((string)($recipient['region'] ?? ''))
    ];

    $parts = array_values(array_filter($parts, static function ($value) {
        return $value !== '';
    }));

    return implode(', ', $parts);
}

function normalizeFlowerRequirements($raw): array {
    if (!is_array($raw)) {
        return [];
    }

    $normalized = [];

    foreach ($raw as $key => $value) {
        if (is_array($value)) {
            $name = trim((string)($value['name'] ?? $value['flower'] ?? ''));
            $qty = max(0, (int)($value['quantity'] ?? $value['qty'] ?? $value['count'] ?? 0));
            if ($name !== '' && $qty > 0) {
                $normalized[$name] = ($normalized[$name] ?? 0) + $qty;
            }
            continue;
        }

        $name = trim((string)$key);
        $qty = max(0, (int)$value);
        if ($name !== '' && $qty > 0) {
            $normalized[$name] = ($normalized[$name] ?? 0) + $qty;
        }
    }

    return $normalized;
}

function collectInventoryAdjustments(array $cart): array {
    $adjustments = [];

    foreach ($cart as $item) {
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $flowerRequirements = normalizeFlowerRequirements($item['flowerRequirements'] ?? []);
        if (count($flowerRequirements) > 0) {
            foreach ($flowerRequirements as $flowerName => $requiredQty) {
                $name = trim((string)$flowerName);
                $recipeQty = max(0, (int)$requiredQty);
                if ($name !== '' && $recipeQty > 0) {
                    $adjustments[$name] = ($adjustments[$name] ?? 0) + ($recipeQty * $qty);
                }
            }
            continue;
        }

        $customSummary = $item['customData']['summary'] ?? null;
        if (is_array($customSummary) && count($customSummary) > 0) {
            foreach ($customSummary as $entry) {
                $flowerName = trim((string)($entry['name'] ?? ''));
                $flowerCount = max(1, (int)($entry['count'] ?? 1));
                if ($flowerName !== '') {
                    $adjustments[$flowerName] = ($adjustments[$flowerName] ?? 0) + ($flowerCount * $qty);
                }
            }
            continue;
        }

        $itemName = trim((string)($item['name'] ?? ''));
        if ($itemName !== '') {
            $adjustments[$itemName] = ($adjustments[$itemName] ?? 0) + $qty;
        }
    }

    return $adjustments;
}

function buildItemDescription(array $item): string {
    $descriptionParts = [];

    $baseDescription = trim((string)($item['description'] ?? ''));
    if ($baseDescription !== '') {
        $descriptionParts[] = $baseDescription;
    }

    $customSummary = $item['customData']['summary'] ?? null;
    if (is_array($customSummary) && count($customSummary) > 0) {
        $parts = [];
        foreach ($customSummary as $entry) {
            $name = trim((string)($entry['name'] ?? 'Flower'));
            $count = max(1, (int)($entry['count'] ?? 1));
            $parts[] = $name . ' x' . $count;
        }
        $descriptionParts[] = 'Custom bouquet: ' . implode(', ', $parts);
    }

    $flowerRequirements = normalizeFlowerRequirements($item['flowerRequirements'] ?? []);
    if (count($flowerRequirements) > 0) {
        $recipeParts = [];
        foreach ($flowerRequirements as $flowerName => $requiredQty) {
            $name = trim((string)$flowerName);
            $qty = max(0, (int)$requiredQty);
            if ($name !== '' && $qty > 0) {
                $recipeParts[] = $name . ' x' . $qty;
            }
        }
        if (count($recipeParts) > 0) {
            $descriptionParts[] = 'Flowers required: ' . implode(', ', $recipeParts);
        }
    }

    $addOns = $item['addOns'] ?? [];
    if (is_array($addOns) && count($addOns) > 0) {
        $addOnNames = array_values(array_filter(array_map(function ($entry) {
            return trim((string)($entry['name'] ?? ''));
        }, $addOns)));

        if (count($addOnNames) > 0) {
            $descriptionParts[] = 'Add-ons: ' . implode(', ', $addOnNames);
        }
    }

    if (count($descriptionParts) === 0) {
        return 'No extra add-ons';
    }

    return implode(' | ', $descriptionParts);
}

function repairOrderItemsSchema(PDO $conn): void {
    $hasOrderItemsTable = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
    if (!$hasOrderItemsTable) {
        return;
    }

    $fkStmt = $conn->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'order_items'
          AND REFERENCED_TABLE_NAME IS NOT NULL");
    $fkRows = $fkStmt ? $fkStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($fkRows as $fkRow) {
        $constraintName = trim((string)($fkRow['CONSTRAINT_NAME'] ?? ''));
        $columnName = trim((string)($fkRow['COLUMN_NAME'] ?? ''));
        $referencedTable = strtolower(trim((string)($fkRow['REFERENCED_TABLE_NAME'] ?? '')));

        $invalidInventoryRef = $columnName === 'inventory_id' && $referencedTable !== 'inventory';
        $invalidOrderRef = $columnName === 'order_id' && $referencedTable !== 'orders';

        if (($invalidInventoryRef || $invalidOrderRef) && $constraintName !== '') {
            $conn->exec("ALTER TABLE order_items DROP FOREIGN KEY `{$constraintName}`");
        }
    }

    $existingRefs = [];
    $refCheckStmt = $conn->query("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'order_items'
          AND REFERENCED_TABLE_NAME IS NOT NULL");
    $refRows = $refCheckStmt ? $refCheckStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($refRows as $refRow) {
        $existingRefs[strtolower((string)$refRow['COLUMN_NAME'])] = strtolower((string)$refRow['REFERENCED_TABLE_NAME']);
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if ($hasOrdersTable && (($existingRefs['order_id'] ?? '') !== 'orders')) {
        try {
            $conn->exec("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (Throwable $ignored) {
        }
    }

    $hasInventoryTable = $conn->query("SHOW TABLES LIKE 'inventory'")->rowCount() > 0;
    if ($hasInventoryTable && (($existingRefs['inventory_id'] ?? '') !== 'inventory')) {
        try {
            $conn->exec("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE SET NULL ON UPDATE CASCADE");
        } catch (Throwable $ignored) {
        }
    }
}

function ensureVoucherSchema(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        code VARCHAR(50) NOT NULL,
        type VARCHAR(20) NOT NULL,
        value DECIMAL(10,2) NOT NULL DEFAULT 0,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        expiry_date DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME NULL,
        UNIQUE KEY uniq_voucher_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vouchers'")->fetchAll(PDO::FETCH_COLUMN);
    $alterMap = [
        'status' => "ALTER TABLE vouchers ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'active'",
        'expiry_date' => "ALTER TABLE vouchers ADD COLUMN expiry_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'created_at' => "ALTER TABLE vouchers ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'used_at' => "ALTER TABLE vouchers ADD COLUMN used_at DATETIME NULL"
    ];

    foreach ($alterMap as $columnName => $sql) {
        if (!in_array($columnName, $columns, true)) {
            $conn->exec($sql);
        }
    }
}

function ensureOrderSchema(PDO $conn): void {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        $conn->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            status ENUM('Pending','Accepted','Declined','Preparing','Delivering','Delivered') DEFAULT 'Pending',
            assigned_florist VARCHAR(100) NULL,
            payment_method VARCHAR(50) NULL,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid',
            payment_confirmed_at DATETIME NULL,
            inventory_deducted TINYINT(1) NOT NULL DEFAULT 0,
            sender_name VARCHAR(150) NULL,
            sender_email VARCHAR(150) NULL,
            sender_phone VARCHAR(50) NULL,
            recipient_name VARCHAR(150) NULL,
            recipient_phone VARCHAR(50) NULL,
            delivery_address TEXT NULL,
            delivery_date DATE NULL,
            message_to_recipient TEXT NULL,
            special_instructions TEXT NULL,
            subtotal DECIMAL(10,2) NULL,
            shipping_fee DECIMAL(10,2) NULL,
            voucher_id INT NULL,
            voucher_code VARCHAR(50) NULL,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            florist_commission DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $cols = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'")->fetchAll(PDO::FETCH_COLUMN);

        $alterMap = [
            'inventory_deducted' => "ALTER TABLE orders ADD COLUMN inventory_deducted TINYINT(1) NOT NULL DEFAULT 0",
            'assigned_florist' => "ALTER TABLE orders ADD COLUMN assigned_florist VARCHAR(100) NULL",
            'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL",
            'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid'",
            'payment_confirmed_at' => "ALTER TABLE orders ADD COLUMN payment_confirmed_at DATETIME NULL",
            'sender_name' => "ALTER TABLE orders ADD COLUMN sender_name VARCHAR(150) NULL",
            'sender_email' => "ALTER TABLE orders ADD COLUMN sender_email VARCHAR(150) NULL",
            'sender_phone' => "ALTER TABLE orders ADD COLUMN sender_phone VARCHAR(50) NULL",
            'recipient_name' => "ALTER TABLE orders ADD COLUMN recipient_name VARCHAR(150) NULL",
            'recipient_phone' => "ALTER TABLE orders ADD COLUMN recipient_phone VARCHAR(50) NULL",
            'delivery_address' => "ALTER TABLE orders ADD COLUMN delivery_address TEXT NULL",
            'delivery_date' => "ALTER TABLE orders ADD COLUMN delivery_date DATE NULL",
            'message_to_recipient' => "ALTER TABLE orders ADD COLUMN message_to_recipient TEXT NULL",
            'special_instructions' => "ALTER TABLE orders ADD COLUMN special_instructions TEXT NULL",
            'subtotal' => "ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) NULL",
            'shipping_fee' => "ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) NULL",
            'voucher_id' => "ALTER TABLE orders ADD COLUMN voucher_id INT NULL",
            'voucher_code' => "ALTER TABLE orders ADD COLUMN voucher_code VARCHAR(50) NULL",
            'discount_amount' => "ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0",
            'florist_commission' => "ALTER TABLE orders ADD COLUMN florist_commission DECIMAL(10,2) NOT NULL DEFAULT 0"
        ];

        foreach ($alterMap as $columnName => $sql) {
            if (!in_array($columnName, $cols, true)) {
                $conn->exec($sql);
            }
        }
    }

    $hasOrderItemsTable = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
    if (!$hasOrderItemsTable) {
        $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NULL,
            inventory_id INT NULL,
            quantity INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    repairOrderItemsSchema($conn);

    $conn->exec("CREATE TABLE IF NOT EXISTS order_item_meta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        item_description TEXT,
        item_image TEXT,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NULL,
        line_total DECIMAL(10,2) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $metaCols = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_item_meta'")->fetchAll(PDO::FETCH_COLUMN);
    $metaAlterMap = [
        'item_description' => "ALTER TABLE order_item_meta ADD COLUMN item_description TEXT NULL",
        'item_image' => "ALTER TABLE order_item_meta ADD COLUMN item_image TEXT NULL",
        'unit_price' => "ALTER TABLE order_item_meta ADD COLUMN unit_price DECIMAL(10,2) NULL",
        'line_total' => "ALTER TABLE order_item_meta ADD COLUMN line_total DECIMAL(10,2) NULL"
    ];

    foreach ($metaAlterMap as $columnName => $sql) {
        if (!in_array($columnName, $metaCols, true)) {
            $conn->exec($sql);
        }
    }
}

function findApplicableVoucher(PDO $conn, ?int $userId, string $voucherCode): ?array {
    $code = trim($voucherCode);
    if ($userId === null || $userId <= 0 || $code === '') {
        return null;
    }

    $hasVouchersTable = $conn->query("SHOW TABLES LIKE 'vouchers'")->rowCount() > 0;
    if (!$hasVouchersTable) {
        throw new RuntimeException('Voucher storage is not available right now.');
    }

    $stmt = $conn->prepare("SELECT id, code, type, value, status, expiry_date FROM vouchers WHERE user_id = ? AND code = ? LIMIT 1");
    $stmt->execute([$userId, $code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        throw new RuntimeException('Voucher not found for this account.');
    }

    $status = strtolower(trim((string)($voucher['status'] ?? '')));
    if ($status !== 'active') {
        throw new RuntimeException('This voucher is no longer active.');
    }

    $expiryRaw = trim((string)($voucher['expiry_date'] ?? ''));
    if ($expiryRaw !== '' && strtotime($expiryRaw) < strtotime(date('Y-m-d 00:00:00'))) {
        throw new RuntimeException('This voucher has already expired.');
    }

    return $voucher;
}

function calculateVoucherDiscount(?array $voucher, float $subtotal): float {
    if (!$voucher || $subtotal <= 0) {
        return 0.0;
    }

    $type = strtolower(trim((string)($voucher['type'] ?? '')));
    $value = safeNumber($voucher['value'] ?? 0, 0);

    if ($type === 'percentage') {
        return min($subtotal, round($subtotal * ($value / 100), 2));
    }

    return min($subtotal, round($value, 2));
}

try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
        exit;
    }

    $cart = $payload['cart'] ?? [];
    if (!is_array($cart) || count($cart) === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $sender = is_array($payload['sender'] ?? null) ? $payload['sender'] : [];
    $recipient = is_array($payload['recipient'] ?? null) ? $payload['recipient'] : [];

    $senderName = trim((string)($sender['name'] ?? ''));
    $senderPhone = trim((string)($sender['phone'] ?? ''));
    $senderEmail = trim((string)($sender['email'] ?? ''));
    $recipientName = trim(((string)($recipient['firstName'] ?? '')) . ' ' . ((string)($recipient['lastName'] ?? '')));
    $recipientPhone = trim((string)($recipient['phone'] ?? ''));
    $deliveryAddress = combineAddressParts($recipient);
    $deliveryDate = trim((string)($payload['deliveryDate'] ?? ''));
    $paymentMethod = strtolower(trim((string)($payload['paymentMethod'] ?? '')));
    $gcashNumber = trim((string)($payload['gcashNumber'] ?? ''));
    $messageToRecipient = trim((string)($payload['messageToRecipient'] ?? ''));
    $specialInstructions = trim((string)($payload['specialInstructions'] ?? ''));

    if ($senderName === '') {
        echo json_encode(['success' => false, 'message' => 'Sender name is required.']);
        exit;
    }
    if (!isValidPhilippineMobile($senderPhone)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid Philippine sender phone number.']);
        exit;
    }
    if (!isValidEmailAddress($senderEmail)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']);
        exit;
    }

    if ($recipientName === '') {
        $recipientName = $senderName;
    }
    if ($recipientPhone === '') {
        $recipientPhone = $senderPhone;
    }
    if (!isValidPhilippineMobile($recipientPhone)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid Philippine recipient phone number.']);
        exit;
    }
    if ($deliveryAddress === '') {
        echo json_encode(['success' => false, 'message' => 'Delivery address is required.']);
        exit;
    }
    if ($deliveryDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
        echo json_encode(['success' => false, 'message' => 'Delivery date is required.']);
        exit;
    }
    if (!in_array($paymentMethod, ['cod', 'gcash'], true)) {
        echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
        exit;
    }
    if ($paymentMethod === 'gcash' && !isValidPhilippineMobile($gcashNumber)) {
        echo json_encode(['success' => false, 'message' => 'A valid GCash number is required for GCash payments.']);
        exit;
    }

    ensureOrderSchema($conn);
    ensureVoucherSchema($conn);

    $shipping = max(0, safeNumber($payload['shippingFee'] ?? 150, 150));
    $subtotal = 0;
    $customSubtotal = 0;

    foreach ($cart as $item) {
        $base = safeNumber($item['basePrice'] ?? 0, 0);
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $addOns = $item['addOns'] ?? [];
        $addOnTotal = 0;

        if (is_array($addOns)) {
            foreach ($addOns as $addOn) {
                $addOnTotal += safeNumber($addOn['price'] ?? 0, 0);
            }
        }

        $itemTotal = ($base + $addOnTotal) * $qty;
        $subtotal += $itemTotal;

            // Track custom bouquet subtotal for targeted commission
        if (!empty($item['customData']['summary'])) {
            $customSubtotal += $itemTotal;
        }
    }

    // Commission: 5% on custom bouquets only, 0% on regular items
    $floristCommission = round($customSubtotal * 0.05, 2);

    $userId = null;
    if (isset($payload['userId']) && is_numeric($payload['userId']) && (int)$payload['userId'] > 0) {
        $userId = (int)$payload['userId'];
    }

    $voucherPayload = is_array($payload['voucher'] ?? null) ? $payload['voucher'] : [];
    $voucherCode = trim((string)($voucherPayload['code'] ?? ($payload['voucherCode'] ?? '')));
    $voucher = null;
    $discountAmount = 0.0;

    if ($voucherCode !== '') {
        $voucher = findApplicableVoucher($conn, $userId, $voucherCode);
        $discountAmount = calculateVoucherDiscount($voucher, $subtotal);
    }

    $total = max(0, $subtotal - $discountAmount) + $shipping;
    $nameToQty = collectInventoryAdjustments($cart);

    $conn->beginTransaction();

    $insertOrder = $conn->prepare(
        "INSERT INTO orders (
            user_id, status, payment_method, payment_status, inventory_deducted,
            sender_name, sender_email, sender_phone,
            recipient_name, recipient_phone, delivery_address, delivery_date,
            message_to_recipient, special_instructions,
            subtotal, shipping_fee, voucher_id, voucher_code, discount_amount, florist_commission, total
         ) VALUES (
            ?, 'Pending', ?, 'Unpaid', 0,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?, ?, ?
         )"
    );
    $insertOrder->execute([
        $userId,
        $paymentMethod,
        $senderName,
        $senderEmail,
        $senderPhone,
        $recipientName,
        $recipientPhone,
        $deliveryAddress,
        $deliveryDate,
        $messageToRecipient,
        $specialInstructions,
        $subtotal,
        $shipping,
        $voucher['id'] ?? null,
        $voucher['code'] ?? null,
        $discountAmount,
        $floristCommission,
        $total
    ]);
    $orderId = (int)$conn->lastInsertId();

    $insertOrderMeta = $conn->prepare(
        "INSERT INTO order_item_meta (order_id, item_name, item_description, item_image, quantity, unit_price, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($cart as $item) {
        $itemName = trim((string)($item['name'] ?? 'Item'));
        $itemDescription = buildItemDescription($item);
        $itemImage = trim((string)($item['image'] ?? ''));
        $itemQty = max(1, (int)($item['quantity'] ?? 1));
        $base = safeNumber($item['basePrice'] ?? 0, 0);
        $addOnTotal = 0;

        if (is_array($item['addOns'] ?? null)) {
            foreach ($item['addOns'] as $addOn) {
                $addOnTotal += safeNumber($addOn['price'] ?? 0, 0);
            }
        }

        $unitPrice = round($base + $addOnTotal, 2);
        $lineTotal = round($unitPrice * $itemQty, 2);
        $insertOrderMeta->execute([$orderId, $itemName, $itemDescription, $itemImage, $itemQty, $unitPrice, $lineTotal]);
    }

    $hasInventoryTable = $conn->query("SHOW TABLES LIKE 'inventory'")->rowCount() > 0;
    if ($hasInventoryTable && !empty($nameToQty)) {
        $findInventory = $conn->prepare("SELECT id FROM inventory WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
        $insertOrderItem = $conn->prepare("INSERT INTO order_items (order_id, inventory_id, quantity) VALUES (?, ?, ?)");

        foreach ($nameToQty as $itemName => $qtyToDeduct) {
            $findInventory->execute([$itemName]);
            $match = $findInventory->fetch(PDO::FETCH_ASSOC);
            if (!$match || empty($match['id'])) {
                continue;
            }

            $inventoryId = (int)$match['id'];
            $qtyInt = max(1, (int)$qtyToDeduct);
            $insertOrderItem->execute([$orderId, $inventoryId, $qtyInt]);
        }
    }

    if ($voucher && !empty($voucher['id'])) {
        $markVoucherUsed = $conn->prepare("UPDATE vouchers SET status = 'used', used_at = NOW() WHERE id = ?");
        $markVoucherUsed->execute([(int)$voucher['id']]);
    }

    $conn->commit();

    // --- START TELEGRAM NOTIFICATION ---
    $telegramStatus = ['success' => false, 'message' => 'Telegram not attempted (missing config)'];
    try {
        $tgConfig = getTelegramConfig();
        if (!empty($tgConfig['token']) && !empty($tgConfig['chatId'])) {
            $orderNum = 'ORD-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
            $itemsSummary = implode("\n", array_map(function($item) {
                return "• " . ($item['name'] ?? 'Item') . " (x" . ($item['quantity'] ?? 1) . ")";
            }, $cart));
            
            $tgMessage = "<b>🌸 New FloraFit Order!</b>\n\n" .
                         "<b>Order #:</b> <code>$orderNum</code>\n" .
                         "<b>Customer:</b> $senderName\n" .
                         "<b>Total:</b> ₱" . number_format($total, 2) . "\n" .
                         "<b>Florist Commission:</b> ₱" . number_format($floristCommission, 2) . " (Custom Items)\n\n" .
                         "<b>Items:</b>\n$itemsSummary";
            
            $telegramStatus = sendTelegramNotification(
                $tgConfig['token'],
                $tgConfig['chatId'],
                $tgMessage
            );
        } else {
             $telegramStatus['message'] = 'Telegram config empty. Check .env';
        }
    } catch (Throwable $tgError) {
        $telegramStatus = ['success' => false, 'message' => 'Exception: ' . $tgError->getMessage()];
    }
    // --- END TELEGRAM NOTIFICATION ---

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_number' => 'ORD-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT),
        'order_id' => $orderId,
        'subtotal' => round($subtotal, 2),
        'shipping_fee' => round($shipping, 2),
        'discount_amount' => round($discountAmount, 2),
        'total' => round($total, 2),
        'voucher_code' => $voucher['code'] ?? null,
        'receipt_url' => '/FloraFit/api/order_receipt.php?order_id=' . $orderId,
        'telegram_status' => $telegramStatus
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>