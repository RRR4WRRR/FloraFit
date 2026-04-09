<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    // Get all inventory items with their categories
    $stmt = $conn->prepare("SELECT DISTINCT name, category FROM inventory ORDER BY name ASC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['items' => $items]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'items' => []]);
}
?>

