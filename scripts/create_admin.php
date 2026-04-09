<?php
header("Content-Type: application/json");
include '../includes/db.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : null;
$password = isset($_GET['password']) ? $_GET['password'] : null;

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Usage: create_admin.php?email=you@example.com&password=YourPass"]);
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
    if ($existing) {
        echo json_encode(["success" => false, "message" => "User already exists"]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO users (full_name, first_name, last_name, email, password, role, status, email_verified) VALUES (?, ?, ?, ?, ?, 'admin', 'active', 1)");
    $ins->execute([$email, 'Admin', 'User', $email, $hash]);

    echo json_encode(["success" => true, "message" => "Admin account created. You can now login.", "email" => $email]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

?>
