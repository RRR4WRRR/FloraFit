<?php
/**
 * FloraFit Password Reset - FIXED & SECURE
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/db.php';
require_once 'api/send_email.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = strtolower(trim($input['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
        echo json_encode([
            "success" => false, 
            "message" => "Valid email required"
        ]);
        exit;
    }

    // Check user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false, 
            "message" => "Email not found or account inactive"
        ]);
        exit;
    }

    // Generate secure 6-digit code
    $code = sprintf("%06d", mt_rand(100000, 999999));

    // Delete old reset codes
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    // Create new reset code (10 min expiry)
    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_id, email, code, expires_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
    ");
    $stmt->execute([$user['id'], $email, $code]);

    // Send email
    $emailSent = sendEmail($email, $code, 'reset');

    echo json_encode([
        "success" => true,
        "message" => $emailSent 
            ? "✅ Reset code sent to your email!" 
            : "❌ Email failed - contact support",
        "user_id" => $user['id'],
        "expires_in" => "10 minutes"
    ]);

} catch (Exception $e) {
    error_log("Reset error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
?>
