<?php
header("Content-Type: application/json");
session_start();
include '../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$email    = trim($data['email']);
$password = $data['password'];

$stmt = $conn->prepare("
    SELECT id, email, first_name, last_name, password, role, is_first_login, profile_picture 
    FROM users WHERE email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];

    $fullName = trim($user['first_name'] . " " . $user['last_name']);

    // First-login florist → generate a secure one-time token
    if ($user['role'] === 'florist' && $user['is_first_login'] == 1) {

        $token = bin2hex(random_bytes(32));

        $upd = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
        $upd->execute([$token, $user['id']]);

        echo json_encode([
            "success" => true,
            "message" => "First login - change password required",
            "token"   => $token,
            "user"    => [
                "id"           => $user['id'],
                "email"        => $user['email'],
                "name"         => $fullName,
                "firstName"    => $user['first_name'],
                "lastName"     => $user['last_name'],
                "profile_picture" => $user['profile_picture'] ?? null,
                "role"         => $user['role'],
                "is_first_login" => (int) $user['is_first_login']
            ]
        ]);
        exit;
    }

    // Normal redirects
    if ($user['role'] === 'admin') {
        $redirect = "admin\dashboard.php";
    } elseif ($user['role'] === 'florist') {
        $redirect = "florist\dashboard.php";
    } else {
        $redirect = "index.html";
    }

    echo json_encode([
        "success"  => true,
        "message"  => "Welcome!",
        "redirect" => $redirect,
        "user"     => [
            "id"           => $user['id'],
            "email"        => $user['email'],
            "name"         => $fullName,
            "firstName"    => $user['first_name'],
            "lastName"     => $user['last_name'],
            "profile_picture" => $user['profile_picture'] ?? null,
            "role"         => $user['role'],
            "is_first_login" => (int) $user['is_first_login']
        ]
    ]);

} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid credentials"
    ]);
}
?>
