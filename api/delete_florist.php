<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

// Only admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "POST method required"]);
    exit;
}

$id = $_POST['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

// Delete only florists
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'florist'");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => true, 
        "message" => "Florist deleted successfully"
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Florist not found or already deleted"
    ]);
}
?>
