<?php
header("Content-Type: application/json");
include '../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "vouchers" => $vouchers
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error loading vouchers: " . $e->getMessage()
    ]);
}
?>
