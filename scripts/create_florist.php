<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require '../includes/db.php';
require 'api/send_email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');

if (!$name || !$email) {
    echo json_encode(["success" => false, "message" => "Name and email are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    echo json_encode(["success" => false, "message" => "Email already exists."]);
    exit;
}

// Split name into first/last
$names      = explode(" ", $name, 2);
$first_name = $names[0];
$last_name  = $names[1] ?? '';

// Generate temporary password
$tempPassword   = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#'), 0, 10);
$hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

// Insert florist
$stmt = $conn->prepare("
    INSERT INTO users (first_name, last_name, email, password, role, is_first_login, created_by)
    VALUES (?, ?, ?, ?, 'florist', 1, ?)
");

try {
    $stmt->execute([$first_name, $last_name, $email, $hashedPassword, $_SESSION['user_id']]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Failed to create account: " . $e->getMessage()]);
    exit;
}

// Send email using existing send_email.php
$emailSent = sendFloristCredentials($email, $name, $tempPassword);

echo json_encode([
    "success"       => true,
    "message"       => $emailSent
                        ? "Florist account created and credentials sent to {$email}."
                        : "Florist account created, but email could not be sent.",
    "email_sent"    => $emailSent,
    "temp_password" => $tempPassword
]);
exit;
?>