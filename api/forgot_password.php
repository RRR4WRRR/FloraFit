<?php
header("Content-Type: application/json");

// Turn off HTML error output, log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include '../includes/db.php';
include 'api/send_email.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');

if(!$email){
    echo json_encode(["success"=>false,"message"=>"Email required"]);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    echo json_encode(["success"=>false,"message"=>"Email not found"]);
    exit;
}

// Generate 6 digit code
$code = rand(100000,999999);

// Store reset code
$stmt = $conn->prepare("
INSERT INTO password_resets (user_id,email,code,expires_at)
VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))
");

$stmt->execute([$user['id'],$email,$code]);

$subject = "FloraFit Password Reset";

$message = "
<h2>FloraFit Password Reset</h2>
<p>Your verification code is:</p>
<h1>$code</h1>
<p>This code expires in 10 minutes.</p>
";

$sent = sendEmail($email,$code, 'reset');

if($sent){
    echo json_encode([
        "success"=>true,
        "message"=>"Verification code sent to email"
    ]);
}else{
    echo json_encode([
        "success"=>false,
        "message"=>"Email could not be sent"
    ]);
}
?>
