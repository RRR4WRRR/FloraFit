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

function ensureOrderFeedbackSchema(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS order_feedback (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        rating TINYINT(1) NOT NULL,
        feedback_text TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_order_user (order_id, user_id),
        KEY idx_order_id (order_id),
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function ensureOrderItemMetaSchema(PDO $conn): void {
    $hasMetaTable = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
    if (!$hasMetaTable) {
        return;
    }

    $columns = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_item_meta'")->fetchAll(PDO::FETCH_COLUMN);
    $alterMap = [
        'item_description' => "ALTER TABLE order_item_meta ADD COLUMN item_description TEXT NULL",
        'item_image' => "ALTER TABLE order_item_meta ADD COLUMN item_image TEXT NULL",
        'unit_price' => "ALTER TABLE order_item_meta ADD COLUMN unit_price DECIMAL(10,2) NULL",
        'line_total' => "ALTER TABLE order_item_meta ADD COLUMN line_total DECIMAL(10,2) NULL"
    ];

    foreach ($alterMap as $columnName => $sql) {
        if (!in_array($columnName, $columns, true)) {
            $conn->exec($sql);
        }
    }
}

try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'orders' => [], 'message' => 'Invalid user id']);
        exit;
    }

    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        echo json_encode(['success' => true, 'orders' => []]);
        exit;
    }

    ensureOrderPaymentSchema($conn);
    ensureOrderFeedbackSchema($conn);
    ensureOrderItemMetaSchema($conn);

    $stmt = $conn->prepare("SELECT
            o.id,
            CONCAT('ORD-', LPAD(o.id, 6, '0')) AS order_number,
            COALESCE(o.status, 'Pending') AS status,
            COALESCE(o.subtotal, 0) AS subtotal,
            COALESCE(o.shipping_fee, 0) AS shipping_fee,
            COALESCE(o.discount_amount, 0) AS discount_amount,
            COALESCE(o.voucher_code, '') AS voucher_code,
            COALESCE(o.total, 0) AS total,
            COALESCE(o.payment_method, '') AS payment_method,
            COALESCE(o.payment_status, 'Unpaid') AS payment_status,
            COALESCE(o.sender_name, '') AS sender_name,
            COALESCE(o.recipient_name, '') AS recipient_name,
            COALESCE(o.delivery_address, '') AS delivery_address,
            o.delivery_date,
            o.payment_confirmed_at,
            o.created_at,
            CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS has_feedback,
            COALESCE(f.rating, 0) AS feedback_rating,
            COALESCE(f.feedback_text, '') AS feedback_text,
            f.updated_at AS feedback_updated_at
        FROM orders o
        LEFT JOIN order_feedback f ON f.order_id = o.id AND f.user_id = o.user_id
        WHERE o.user_id = ?
          AND COALESCE(o.status, 'Pending') IN ('Accepted', 'Preparing', 'Delivering', 'Delivered')
        ORDER BY o.id DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orders) > 0) {
        $orderIds = array_map(function ($order) {
            return (int)$order['id'];
        }, $orders);

        $itemsByOrder = [];

        $hasMetaTable = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
        if ($hasMetaTable) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $metaStmt = $conn->prepare("SELECT order_id, item_name, item_description, item_image, quantity, COALESCE(unit_price, 0) AS unit_price, COALESCE(line_total, 0) AS line_total FROM order_item_meta WHERE order_id IN ($placeholders) ORDER BY id ASC");
            $metaStmt->execute($orderIds);
            $rows = $metaStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $orderId = (int)$row['order_id'];
                if (!isset($itemsByOrder[$orderId])) {
                    $itemsByOrder[$orderId] = [];
                }
                $itemsByOrder[$orderId][] = [
                    'name' => $row['item_name'] ?? 'Item',
                    'description' => $row['item_description'] ?? '',
                    'image' => $row['item_image'] ?? '',
                    'quantity' => (int)($row['quantity'] ?? 1),
                    'unit_price' => (float)($row['unit_price'] ?? 0),
                    'line_total' => (float)($row['line_total'] ?? 0)
                ];
            }
        }

        foreach ($orders as &$order) {
            $orderId = (int)$order['id'];
            $orderItems = $itemsByOrder[$orderId] ?? [];
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

            $order['items'] = $orderItems;
        }
        unset($order);
    }

    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'orders' => [], 'message' => $e->getMessage()]);
}
?>

