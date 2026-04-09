<?php
header("Content-Type: application/json");
include '../includes/db.php';

try {
    $stmt = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'customer' ORDER BY full_name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "users" => $users]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
