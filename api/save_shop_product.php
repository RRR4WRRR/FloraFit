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

function normalizeRequirements($raw): array {
    if (is_array($raw)) {
        $data = $raw;
    } else {
        $decoded = json_decode((string)$raw, true);
        $data = is_array($decoded) ? $decoded : [];
    }

    $normalized = [];

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $flowerName = trim((string)($value['name'] ?? $value['flower'] ?? ''));
            $qty = (int)($value['quantity'] ?? $value['qty'] ?? $value['count'] ?? 0);
            if ($flowerName !== '' && $qty > 0) {
                $normalized[$flowerName] = ($normalized[$flowerName] ?? 0) + $qty;
            }
            continue;
        }

        $flowerName = trim((string)$key);
        $qty = (int)$value;
        if ($flowerName !== '' && $qty > 0) {
            $normalized[$flowerName] = ($normalized[$flowerName] ?? 0) + $qty;
        }
    }

    return $normalized;
}

$id       = $_POST['id'] ?? null;
$name     = $_POST['name'] ?? '';
$price    = $_POST['price'] ?? 0.00;
$badge    = $_POST['badge'] ?? '';
$description = trim((string)($_POST['description'] ?? ''));
$image    = trim((string)($_POST['existing_image'] ?? ''));
$existingImagesRaw = $_POST['existing_images'] ?? '[]';
$flowerRequirementsRaw = $_POST['flower_requirements'] ?? '{}';

$decodedExistingImages = json_decode((string)$existingImagesRaw, true);
$imageList = is_array($decodedExistingImages) ? array_values(array_filter(array_map('trim', $decodedExistingImages))) : [];

if ($image !== '' && count($imageList) === 0) {
    $imageList[] = $image;
}

$flowerRequirements = normalizeRequirements($flowerRequirementsRaw);

ensureShopProductsSchema($conn);

// Handle image upload
if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $imageName = time() . "_" . basename($_FILES['image']['name']);
    $targetFile = $targetDir . $imageName;
    if(move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)){
        $image = $targetFile;
        $imageList[] = $targetFile;
    }
}

if (isset($_FILES['image_gallery']) && is_array($_FILES['image_gallery']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    foreach ($_FILES['image_gallery']['name'] as $index => $fileName) {
        $error = $_FILES['image_gallery']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpName = $_FILES['image_gallery']['tmp_name'][$index] ?? '';
        if ($tmpName === '') {
            continue;
        }

        $newName = time() . "_" . $index . "_" . basename($fileName);
        $targetFile = $targetDir . $newName;
        if (move_uploaded_file($tmpName, $targetFile)) {
            $imageList[] = $targetFile;
        }
    }
}

$imageList = array_values(array_unique(array_filter(array_map('trim', $imageList))));
if (count($imageList) > 0) {
    $image = $imageList[0];
}

$imagesJson = json_encode($imageList, JSON_UNESCAPED_SLASHES);
$requirementsJson = json_encode($flowerRequirements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    if($id) {
        // Update existing product
        $stmt = $conn->prepare("UPDATE shop_products SET name=?, price=?, badge=?, image=?, images=?, description=?, flower_requirements=? WHERE id=?");
        $stmt->execute([$name, $price, $badge, $image, $imagesJson, $description, $requirementsJson, $id]);
    } else {
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO shop_products (name, price, badge, image, images, description, flower_requirements) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $badge, $image, $imagesJson, $description, $requirementsJson]);
    }
    echo json_encode(["status" => "success"]);
} catch(PDOException $e){
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

