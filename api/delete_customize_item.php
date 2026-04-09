<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    // Get the model file path before deleting to optionally remove the file
    $stmt = $conn->prepare("SELECT model_file FROM customize_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM customize_items WHERE id = ?");
    $stmt->execute([$id]);
    
    // Optionally delete the model file (commented out for safety - you may want to keep files)
    // if ($item && file_exists('models/' . $item['model_file'])) {
    //     unlink('models/' . $item['model_file']);
    // }
    
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

