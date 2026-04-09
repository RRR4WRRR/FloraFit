<?php
require_once '../includes/db.php';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value): string {
    return '₱' . number_format((float)$value, 2);
}

function formatDateValue($value): string {
    if (!$value) {
        return '—';
    }

    $time = strtotime((string)$value);
    if ($time === false) {
        return e($value);
    }

    return date('M d, Y h:i A', $time);
}

function getTableColumns(PDO $conn, string $table): array {
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;
$orderItems = [];
$errorMessage = '';

try {
    if ($orderId <= 0) {
        throw new RuntimeException('Invalid order ID.');
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        throw new RuntimeException('Orders table is unavailable.');
    }

    $orderColumns = getTableColumns($conn, 'orders');
    $optionalOrderFields = [
        'subtotal' => in_array('subtotal', $orderColumns, true) ? 'COALESCE(o.subtotal, 0) AS subtotal' : '0 AS subtotal',
        'shipping_fee' => in_array('shipping_fee', $orderColumns, true) ? 'COALESCE(o.shipping_fee, 0) AS shipping_fee' : '0 AS shipping_fee',
        'discount_amount' => in_array('discount_amount', $orderColumns, true) ? 'COALESCE(o.discount_amount, 0) AS discount_amount' : '0 AS discount_amount',
        'voucher_code' => in_array('voucher_code', $orderColumns, true) ? "COALESCE(o.voucher_code, '') AS voucher_code" : "'' AS voucher_code",
        'sender_name' => in_array('sender_name', $orderColumns, true) ? "COALESCE(o.sender_name, '') AS sender_name" : "'' AS sender_name",
        'sender_email' => in_array('sender_email', $orderColumns, true) ? "COALESCE(o.sender_email, '') AS sender_email" : "'' AS sender_email",
        'sender_phone' => in_array('sender_phone', $orderColumns, true) ? "COALESCE(o.sender_phone, '') AS sender_phone" : "'' AS sender_phone",
        'recipient_name' => in_array('recipient_name', $orderColumns, true) ? "COALESCE(o.recipient_name, '') AS recipient_name" : "'' AS recipient_name",
        'recipient_phone' => in_array('recipient_phone', $orderColumns, true) ? "COALESCE(o.recipient_phone, '') AS recipient_phone" : "'' AS recipient_phone",
        'delivery_address' => in_array('delivery_address', $orderColumns, true) ? "COALESCE(o.delivery_address, '') AS delivery_address" : "'' AS delivery_address",
        'delivery_date' => in_array('delivery_date', $orderColumns, true) ? 'o.delivery_date AS delivery_date' : 'NULL AS delivery_date',
        'message_to_recipient' => in_array('message_to_recipient', $orderColumns, true) ? "COALESCE(o.message_to_recipient, '') AS message_to_recipient" : "'' AS message_to_recipient",
        'special_instructions' => in_array('special_instructions', $orderColumns, true) ? "COALESCE(o.special_instructions, '') AS special_instructions" : "'' AS special_instructions"
    ];

    $orderSql = "SELECT
            o.id,
            CONCAT('ORD-', LPAD(o.id, 6, '0')) AS order_number,
            COALESCE(o.status, 'Pending') AS status,
            COALESCE(o.payment_method, '') AS payment_method,
            COALESCE(o.payment_status, 'Unpaid') AS payment_status,
            COALESCE(o.total, 0) AS total,
            " . implode(",\n            ", $optionalOrderFields) . ",
            o.created_at
        FROM orders o
        WHERE o.id = ?
        LIMIT 1";

    $stmt = $conn->prepare($orderSql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    $hasMetaTable = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
    if ($hasMetaTable) {
        $metaColumns = getTableColumns($conn, 'order_item_meta');
        $itemSql = "SELECT
                order_id,
                item_name,
                " . (in_array('item_description', $metaColumns, true) ? "COALESCE(item_description, '')" : "''") . " AS item_description,
                " . (in_array('item_image', $metaColumns, true) ? "COALESCE(item_image, '')" : "''") . " AS item_image,
                COALESCE(quantity, 1) AS quantity,
                " . (in_array('unit_price', $metaColumns, true) ? 'COALESCE(unit_price, 0)' : '0') . " AS unit_price,
                " . (in_array('line_total', $metaColumns, true) ? 'COALESCE(line_total, 0)' : '0') . " AS line_total
            FROM order_item_meta
            WHERE order_id = ?
            ORDER BY id ASC";

        $itemsStmt = $conn->prepare($itemSql);
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $totalQty = array_reduce($orderItems, static function ($sum, $item) {
        return $sum + max(1, (int)($item['quantity'] ?? 1));
    }, 0);
    $fallbackUnitPrice = $totalQty > 0 ? ((float)$order['total'] / $totalQty) : 0;

    foreach ($orderItems as &$item) {
        if ((float)($item['unit_price'] ?? 0) <= 0) {
            $item['unit_price'] = round($fallbackUnitPrice, 2);
        }
        if ((float)($item['line_total'] ?? 0) <= 0) {
            $item['line_total'] = round(((float)$item['unit_price']) * max(1, (int)($item['quantity'] ?? 1)), 2);
        }
    }
    unset($item);
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt | FloraFit</title>
    <link rel="icon" type="image/png" href="flora.png">
    <link rel="stylesheet" href="assets/css/website.css">
    <style>
        body {
            background: linear-gradient(180deg, #fff9fb 0%, #fffefc 100%);
            color: #4e3b36;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }

        .receipt-shell {
            max-width: 980px;
            margin: 32px auto;
            padding: 0 16px 40px;
        }

        .receipt-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 0 0 18px;
        }

        .receipt-btn {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(135deg, #ff8fa3 0%, #ff6f91 100%);
        }

        .receipt-btn.secondary {
            background: linear-gradient(135deg, #6c63ff 0%, #5a54d1 100%);
        }

        .receipt-paper {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 182, 193, 0.25);
            padding: 28px;
        }

        .receipt-header {
            position: static;
            top: auto;
            width: 100%;
            z-index: auto;
            background: transparent;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 12px;
            border-bottom: 1px dashed rgba(0,0,0,0.12);
            padding-bottom: 18px;
            margin-bottom: 18px;
        }

        .receipt-brand,
        .receipt-meta {
            width: 100%;
            text-align: center;
        }

        .receipt-brand h1 {
            margin: 0 0 6px;
            color: #5d4037;
        }

        .receipt-brand p,
        .receipt-meta p,
        .receipt-note {
            margin: 4px 0;
            color: #6f625d;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .receipt-card {
            background: #fff9fc;
            border: 1px solid rgba(255, 182, 193, 0.22);
            border-radius: 16px;
            padding: 16px;
        }

        .receipt-card h3 {
            margin: 0 0 10px;
            color: #5d4037;
            font-size: 1rem;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .receipt-table th,
        .receipt-table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            vertical-align: top;
        }

        .receipt-table th {
            color: #6a4d45;
            font-size: 0.92rem;
        }

        .receipt-summary {
            margin-left: auto;
            width: min(100%, 360px);
            margin-top: 18px;
        }

        .receipt-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.08);
        }

        .receipt-summary-row.total {
            font-size: 1.08rem;
            font-weight: 700;
            color: #2e7d32;
        }

        .receipt-empty {
            padding: 20px;
            border-radius: 16px;
            background: #fff4f4;
            border: 1px solid #ffc9c9;
            color: #9a3a3a;
        }

        @media (max-width: 768px) {
            .receipt-shell {
                margin: 20px auto;
            }

            .receipt-toolbar {
                justify-content: center;
            }

            .receipt-grid {
                grid-template-columns: 1fr;
            }

            .receipt-paper {
                padding: 18px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt-toolbar {
                display: none !important;
            }

            .receipt-shell {
                margin: 0;
                max-width: none;
                padding: 0;
            }

            .receipt-paper {
                box-shadow: none;
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-shell">
        <div class="receipt-toolbar">
            <a href="/FloraFit/index.html" class="receipt-btn secondary">Back to Home</a>
            <button type="button" class="receipt-btn" onclick="window.print()">Print / Save as PDF</button>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="receipt-empty"><?= e($errorMessage) ?></div>
        <?php else: ?>
            <section class="receipt-paper">
                <div class="receipt-header">
                    <div class="receipt-brand">
                        <h1>🌸 FloraFit Receipt</h1>
                        <p>Thank you for your bouquet order.</p>
                        <p class="receipt-note">Use <strong>Print / Save as PDF</strong> to keep a downloadable invoice copy.</p>
                    </div>
                    <div class="receipt-meta">
                        <p><strong>Order #:</strong> <?= e($order['order_number'] ?? '') ?></p>
                        <p><strong>Date:</strong> <?= formatDateValue($order['created_at'] ?? '') ?></p>
                        <p><strong>Status:</strong> <?= e($order['status'] ?? 'Pending') ?></p>
                        <p><strong>Payment:</strong> <?= e(strtoupper((string)($order['payment_method'] ?? ''))) ?> / <?= e($order['payment_status'] ?? 'Unpaid') ?></p>
                    </div>
                </div>

                <div class="receipt-grid">
                    <div class="receipt-card">
                        <h3>Sender</h3>
                        <p><strong>Name:</strong> <?= e($order['sender_name'] ?? '—') ?></p>
                        <p><strong>Email:</strong> <?= e($order['sender_email'] ?? '—') ?></p>
                        <p><strong>Phone:</strong> <?= e($order['sender_phone'] ?? '—') ?></p>
                    </div>
                    <div class="receipt-card">
                        <h3>Recipient & Delivery</h3>
                        <p><strong>Recipient:</strong> <?= e($order['recipient_name'] ?? '—') ?></p>
                        <p><strong>Phone:</strong> <?= e($order['recipient_phone'] ?? '—') ?></p>
                        <p><strong>Delivery Date:</strong> <?= e($order['delivery_date'] ?? '—') ?></p>
                        <p><strong>Address:</strong> <?= e($order['delivery_address'] ?? '—') ?></p>
                    </div>
                </div>

                <?php if (!empty($order['message_to_recipient']) || !empty($order['special_instructions'])): ?>
                    <div class="receipt-grid" style="grid-template-columns: 1fr;">
                        <div class="receipt-card">
                            <h3>Notes</h3>
                            <?php if (!empty($order['message_to_recipient'])): ?>
                                <p><strong>Gift Message:</strong> <?= e($order['message_to_recipient']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($order['special_instructions'])): ?>
                                <p><strong>Special Instructions:</strong> <?= e($order['special_instructions']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="receipt-card">
                    <h3>Items</h3>
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orderItems) > 0): ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($item['item_name'] ?? 'Item') ?></strong>
                                            <?php if (!empty($item['item_description'])): ?>
                                                <div style="font-size:.9rem; color:#6f625d; margin-top:4px;"><?= e($item['item_description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)($item['quantity'] ?? 1) ?></td>
                                        <td><?= money($item['unit_price'] ?? 0) ?></td>
                                        <td><?= money($item['line_total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No item details found for this order.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="receipt-summary">
                    <div class="receipt-summary-row"><span>Subtotal</span><strong><?= money($order['subtotal'] ?? 0) ?></strong></div>
                    <div class="receipt-summary-row"><span>Shipping</span><strong><?= money($order['shipping_fee'] ?? 0) ?></strong></div>
                    <div class="receipt-summary-row"><span>Discount<?= !empty($order['voucher_code']) ? ' (' . e($order['voucher_code']) . ')' : '' ?></span><strong>-<?= money($order['discount_amount'] ?? 0) ?></strong></div>
                    <div class="receipt-summary-row total"><span>Total</span><strong><?= money($order['total'] ?? 0) ?></strong></div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>