<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once '../includes/db.php';
require_once 'api/send_email.php';

$input = json_decode(file_get_contents("php://input"), true);
$email = strtolower(trim($input['email'] ?? ''));

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email required"]);
    exit;
}

// Generate new code
$code = sprintf("%06d", mt_rand(100000, 999999));

// Update/insert code
$stmt = $conn->prepare("REPLACE INTO email_verifications (email, code, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$email, $code]);

$sent = sendEmail($email, $code, 'resend');

echo json_encode([
    "success" => true,
    "message" => $sent ? "✅ New code sent!" : "✅ New code generated!"
]);
?>
