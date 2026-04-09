<?php
include '../includes/db.php';

$id = $_POST['id'] ?? null;

if(!$id) {
    echo json_encode(["status" => "error", "message" => "No ID provided"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM shop_products WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["status" => "success"]);
} catch(PDOException $e){
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

