<?php
include '../includes/db.php';

function ensureShopProductsSchema(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS shop_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        badge VARCHAR(50) DEFAULT '',
        image TEXT,
        images LONGTEXT NULL,
        description TEXT NULL,
        flower_requirements LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $columns = [
        'image' => 'TEXT NULL',
        'images' => 'LONGTEXT NULL',
        'description' => 'TEXT NULL',
        'flower_requirements' => 'LONGTEXT NULL'
    ];

    foreach ($columns as $columnName => $definition) {
        $check = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shop_products' AND COLUMN_NAME = ?");
        $check->execute([$columnName]);
        if ((int)$check->fetchColumn() === 0) {
            $conn->exec("ALTER TABLE shop_products ADD COLUMN {$columnName} {$definition}");
        }
    }
}

try {
    ensureShopProductsSchema($conn);
    
    $stmt = $conn->query("SELECT * FROM shop_products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        $imagesRaw = $product['images'] ?? '';
        $parsedImages = [];

        if (is_string($imagesRaw) && trim($imagesRaw) !== '') {
            $decoded = json_decode($imagesRaw, true);
            if (is_array($decoded)) {
                $parsedImages = array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        if (count($parsedImages) === 0 && !empty($product['image'])) {
            $parsedImages[] = trim((string)$product['image']);
        }

        if (!empty($product['image']) === false && count($parsedImages) > 0) {
            $product['image'] = $parsedImages[0];
        }

        $product['images'] = $parsedImages;

        $requirementsRaw = $product['flower_requirements'] ?? '';
        $parsedRequirements = [];
        if (is_string($requirementsRaw) && trim($requirementsRaw) !== '') {
            $decodedReq = json_decode($requirementsRaw, true);
            if (is_array($decodedReq)) {
                foreach ($decodedReq as $flower => $qty) {
                    $flowerName = trim((string)$flower);
                    $qtyInt = (int)$qty;
                    if ($flowerName !== '' && $qtyInt > 0) {
                        $parsedRequirements[$flowerName] = $qtyInt;
                    }
                }
            }
        }
        $product['flower_requirements'] = $parsedRequirements;
    }
    unset($product);

    echo json_encode($products);
} catch(PDOException $e){
    echo json_encode([]);
}
?>

