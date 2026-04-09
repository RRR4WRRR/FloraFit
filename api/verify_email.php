<?php
/**
 * FloraFit Verify Email - FIXED FOR YOUR DB
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once '../includes/db.php';

$input = json_decode(file_get_contents("php://input"), true);
$email = trim($input['email'] ?? '');
$code = trim($input['code'] ?? '');

if (!$email || !$code || strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(["success" => false, "message" => "Valid 6-digit code required"]);
    exit;
}

// Verify code (1hr expiry)
$stmt = $conn->prepare("
    SELECT ev.*, u.id 
    FROM email_verifications ev 
    JOIN users u ON ev.email = u.email 
    WHERE ev.email = ? AND ev.code = ? 
    AND ev.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$email, $code]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo json_encode(["success" => false, "message" => "❌ Wrong or expired code"]);
    exit;
}

// ACTIVATE USER
$stmt = $conn->prepare("
    UPDATE users 
    SET status = 'active' 
    WHERE id = ? AND email = ?
");
$updated = $stmt->execute([$record['id'], $email]);

// Delete used code
$conn->prepare("DELETE FROM email_verifications WHERE email = ?")->execute([$email]);

echo json_encode([
    "success" => true,
    "message" => "✅ Verified! Welcome to FloraFit 🌸",
    "user_id" => $record['id']
]);
?>
