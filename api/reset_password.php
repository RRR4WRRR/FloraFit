<?php
ob_start();
header("Content-Type: application/json");

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include '../includes/db.php';

try {

    $data = json_decode(file_get_contents('php://input'), true);

    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');
    $new_password = $data['new_password'] ?? '';

    if (!$email || !$code || !$new_password) {
        throw new Exception("Missing parameters");
    }

    if (strlen($new_password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        throw new Exception("Password must contain uppercase letter");
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        throw new Exception("Password must contain number");
    }

    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND code = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        throw new Exception("Invalid code");
    }

    if (strtotime($entry['expires_at']) < time()) {
        throw new Exception("Code expired");
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->execute([$hashed, $entry['user_id']]);

    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->execute([$email]);

    ob_clean();
    echo json_encode(["success" => true, "message" => "Password updated"]);
    exit;

} catch (Exception $e) {
    error_log("RESET ERROR: " . $e->getMessage());

    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}
