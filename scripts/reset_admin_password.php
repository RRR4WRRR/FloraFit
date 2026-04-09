<?php
header("Content-Type: application/json");
include '../includes/db.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : null;
$password = isset($_GET['password']) ? $_GET['password'] : null;

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Usage: reset_admin_password.php?email=you@example.com&password=NewPass"]);
    exit;
}

// Validate password requirements
if (strlen($password) < 8) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long"]);
    exit;
}
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must contain at least 1 uppercase letter"]);
    exit;
}
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must contain at least 1 number"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    if (!$existing) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $upd->execute([$hash, $email]);

    echo json_encode(["success" => true, "message" => "Password updated", "email" => $email]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

?>
