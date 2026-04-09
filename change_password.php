<?php
session_start();
require '../includes/db.php';

// Validate the token from the URL
$token = trim($_GET['token'] ?? '');

if (!$token) {
    header("Location: login.html");
    exit;
}

// Look up the user by token — must be a florist with is_first_login = 1
$stmt = $conn->prepare("
    SELECT id, first_name, role, is_first_login 
    FROM users 
    WHERE reset_token = ? AND role = 'florist' AND is_first_login = 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Token invalid, already used, or not a first-login florist
    header("Location: login.html");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password, clear first-login flag, and burn the token
        $upd = $conn->prepare("
            UPDATE users 
            SET password = ?, is_first_login = 0, reset_token = NULL 
            WHERE id = ?
        ");
        $upd->execute([$hashedPassword, $user['id']]);

        // Now it's safe to set the session — we're in a normal page request
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = 'florist';

        header("Location: florist_dashboard.php?welcome=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | FloraFit</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        .welcome-florist { color: #e91e63; font-weight: 500; }
        .password-strength {
            width: 0; height: 4px; background: #e0e0e0;
            border-radius: 2px; margin-top: 8px; transition: all 0.3s;
        }
        .strength-weak   { background: #f44336; }
        .strength-medium { background: #ff9800; }
        .strength-strong { background: #4caf50; }
        .error {
            background: #ffebee; color: #c62828;
            padding: 12px; border-radius: 8px;
            margin-bottom: 20px; border-left: 4px solid #f44336;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">

        <div style="text-align:center; margin-bottom:20px;">
            <i class="fas fa-seedling" style="font-size:3rem; color:#4caf50; margin-bottom:10px;"></i>
            <h2>Welcome, <span class="welcome-florist"><?= htmlspecialchars($user['first_name']) ?></span> 🌸</h2>
            <p style="color:#666;">Please set a new password to access your dashboard.</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Token passed as hidden field so POST can still reach the same page -->
        <form method="POST" action="change_password.php?token=<?= htmlspecialchars($token) ?>">
            <div class="password-wrapper">
                <input type="password"
                       name="password"
                       id="newPassword"
                       placeholder="Create a strong password (min 6 chars)"
                       required
                       minlength="6">
                <span class="toggle-password" onclick="togglePassword('newPassword', this)">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="password-strength" id="passwordStrength"></div>

            <button type="submit" style="margin-top:20px;">
                <i class="fas fa-lock-open" style="margin-right:8px;"></i>
                Set Password & Go to Dashboard
            </button>
        </form>

        <div style="text-align:center; margin-top:25px; padding-top:20px; border-top:1px solid #eee; font-size:14px; color:#666;">
            <p>🌷 Your floral design journey awaits!</p>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const iconElement = icon.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

document.getElementById('newPassword').addEventListener('input', function () {
    const password = this.value;
    const bar = document.getElementById('passwordStrength');

    let strength = 0;
    if (password.length >= 6)            strength++;
    if (password.match(/[a-z]/))         strength++;
    if (password.match(/[A-Z]/))         strength++;
    if (password.match(/[0-9]/))         strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    bar.className = 'password-strength';
    if (strength <= 2)      bar.classList.add('strength-weak');
    else if (strength <= 4) bar.classList.add('strength-medium');
    else                    bar.classList.add('strength-strong');

    bar.style.width = `${Math.min(strength * 20, 100)}%`;
});
</script>
</body>
</html>

