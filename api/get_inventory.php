<?php
include '../includes/db.php';

try {
    $stmt = $conn->query("SELECT * FROM inventory ORDER BY id DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $img = $item['image'] ?? '';

        if (empty($img)) {
            $item['image'] = null;

        } elseif (strpos($img, 'data:') === 0) {
            // Raw base64 data URI stored — extract only the base64 part
            // Return as proper data URI so browser can render it
            $item['image'] = $img; // keep as-is, dashboard will use directly

        } elseif (preg_match('/^[A-Za-z0-9+\/=]{100,}$/', $img)) {
            // Raw base64 string stored WITHOUT the data: prefix
            // Wrap it so browser can render it
            $item['image'] = 'data:image/jpeg;base64,' . $img;

        } else {
            // It's a file path — strip everything, keep only the filename
            $item['image'] = basename($img);
        }
    }
    unset($item);

    echo json_encode($items);
} catch(PDOException $e){
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>