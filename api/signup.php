<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once '../includes/db.php';
require_once 'api/send_email.php'; // REQUIRED

$input = json_decode(file_get_contents("php://input"), true);
$first_name = trim($input['firstName'] ?? '');
$last_name = trim($input['lastName'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!$first_name || !$last_name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Fill all fields"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$full_name = $first_name . ' ' . $last_name;

// Check existing
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "Email exists"]);
    exit;
}

// Create user + code
$stmt = $conn->prepare("INSERT INTO users (full_name, first_name, last_name, email, password, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$full_name, $first_name, $last_name, $email, $hashed]);

$code = sprintf("%06d", rand(100000, 999999));
$stmt = $conn->prepare("REPLACE INTO email_verifications (email, code) VALUES (?, ?)");
$stmt->execute([$email, $code]);

// SEND EMAIL OR FAIL
$emailSent = sendEmail($email, $code, 'signup');

echo json_encode([
    "success" => $emailSent,
    "message" => $emailSent ? "✅ Check your email!" : "❌ Email failed - try Gmail 'less secure apps'",
    "pending_data" => ["email" => $email, "firstName" => $first_name, "lastName" => $last_name]
]);
?>
