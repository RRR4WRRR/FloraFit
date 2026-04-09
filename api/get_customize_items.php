<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    // Check if table exists, if not suggest running create script
    $tableCheck = $conn->query("SHOW TABLES LIKE 'customize_items'")->rowCount();
    
    if ($tableCheck == 0) {
        echo json_encode([
            'error' => 'Table not found',
            'message' => 'Please run create_customize_items_table.php first',
            'items' => []
        ]);
        exit;
    }
    
    // Join with inventory to get stock levels
    // Match by name (case-insensitive)
    // Note: Wraps and accessories are always available (not tracked in inventory)
    $sql = "SELECT 
                c.*,
                CASE
                    WHEN LOWER(c.category) IN ('wrap', 'accessory', '') OR c.name LIKE '%wrap%' THEN 999
                    ELSE COALESCE(SUM(i.stock), 0)
                END as total_stock,
                COALESCE(MAX(i.price), 0) as price
            FROM customize_items c
            LEFT JOIN inventory i ON LOWER(TRIM(c.name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(i.name)) COLLATE utf8mb4_unicode_ci
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert total_stock to integer
    foreach ($items as &$item) {
        $item['total_stock'] = intval($item['total_stock']);
        $item['in_stock'] = $item['total_stock'] > 0;
    }
    
    echo json_encode(['items' => $items]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'items' => []]);
}
?>

