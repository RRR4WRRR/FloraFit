<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = $_POST['name'] ?? '';
    $emoji = $_POST['emoji'] ?? '';
    $category = $_POST['category'] ?? 'flower';
    $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    
    // Handle model file upload or use existing
    $modelFile = '';
    
    if (isset($_FILES['model_file']) && $_FILES['model_file']['error'] === UPLOAD_ERR_OK) {
        // Upload new model file
        $uploadDir = 'models/';
        
        // Create models directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['model_file']['name'], PATHINFO_EXTENSION));
        
        // Validate file type (only allow .glb files)
        if ($fileExtension !== 'glb') {
            echo json_encode(['success' => false, 'message' => 'Only .glb files are allowed']);
            exit;
        }
        
        // Generate unique filename
        $timestamp = time();
        $originalName = pathinfo($_FILES['model_file']['name'], PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $modelFile = $timestamp . '_' . $sanitizedName . '.' . $fileExtension;
        
        $targetPath = $uploadDir . $modelFile;
        
        if (!move_uploaded_file($_FILES['model_file']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload model file']);
            exit;
        }
    } else if ($id > 0) {
        // For updates, if no new file uploaded, keep the existing file
        $stmt = $conn->prepare("SELECT model_file FROM customize_items WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $modelFile = $existing['model_file'];
        }
    } else {
        // For new items, model file is required
        echo json_encode(['success' => false, 'message' => 'Model file is required for new items']);
        exit;
    }
    
    if ($id > 0) {
        // UPDATE existing item
        $stmt = $conn->prepare("UPDATE customize_items SET name = ?, emoji = ?, model_file = ?, category = ?, display_order = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $emoji, $modelFile, $category, $display_order, $is_active, $id]);
        echo json_encode(['success' => true, 'message' => 'Item updated successfully', 'id' => $id]);
    } else {
        // INSERT new item
        $stmt = $conn->prepare("INSERT INTO customize_items (name, emoji, model_file, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $emoji, $modelFile, $category, $display_order, $is_active]);
        $newId = $conn->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Item added successfully', 'id' => $newId]);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

