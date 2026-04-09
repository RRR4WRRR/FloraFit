<?php
include '../includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS shop_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        badge VARCHAR(50) DEFAULT '',
        image TEXT,
        images LONGTEXT NULL,
        description TEXT NULL,
        flower_requirements LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Shop products table created successfully!";
} catch(PDOException $e){
    echo "Error: " . $e->getMessage();
}
?>
