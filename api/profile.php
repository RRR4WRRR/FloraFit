<?php
// Ensure API returns clean JSON only; log errors instead of displaying them
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header("Content-Type: application/json");
ob_start(); // capture any accidental output
include '../includes/db.php';

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    // Get user profile data
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "User ID required"]);
        exit;
    }
    
    // Build SELECT list dynamically based on existing columns
    $colsToCheck = ['contact_number','address','profile_picture'];
    $selectCols = ['id','first_name','last_name','email'];
    $placeholders = [];

    $colStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN (" . implode(',', array_fill(0, count($colsToCheck), '?')) . ")");
    $colStmt->execute($colsToCheck);
    $existing = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($colsToCheck as $c) {
        if (in_array($c, $existing)) $selectCols[] = $c;
    }

    $sel = implode(', ', $selectCols);
    $stmt = $conn->prepare("SELECT $sel FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $payload = ["success" => true, "user" => $user];
    } else {
        $payload = ["success" => false, "message" => "User not found"];
    }
    // Clean any buffered output and log it (so HTML/warnings won't break JSON)
    $buf = ob_get_clean();
    if (trim($buf) !== '') {
        error_log("profile.php unexpected output: " . $buf);
    }
    echo json_encode($payload);
} elseif ($action === 'update') {
    // Update user profile
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'] ?? null;
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $contact_number = trim($data['contact_number'] ?? '');
    $address = trim($data['address'] ?? '');
    $password = $data['password'] ?? null;
    
    // Validate password if provided
    if ($password && strlen($password) > 0) {
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
    }
    
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "User ID required"]);
        exit;
    }
    
    try {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already in use"]);
            exit;
        }

        // Determine which optional columns exist so we only reference them
        $optionalCols = ['contact_number','address','profile_picture'];
        $colStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN (" . implode(',', array_fill(0, count($optionalCols), '?')) . ")");
        $colStmt->execute($optionalCols);
        $existingCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);

        $fields = ['first_name = ?', 'last_name = ?', 'email = ?'];
        $params = [$first_name, $last_name, $email];

        if (in_array('contact_number', $existingCols)) {
            $fields[] = 'contact_number = ?';
            $params[] = $contact_number;
        }
        if (in_array('address', $existingCols)) {
            $fields[] = 'address = ?';
            $params[] = $address;
        }

        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $fields[] = 'password = ?';
            $params[] = $hashed_password;
        }

        if (in_array('profile_picture', $existingCols)) {
            // Only set profile_picture when provided (use COALESCE to keep existing if empty)
            $fields[] = "profile_picture = COALESCE(NULLIF(?, ''), profile_picture)";
            $params[] = trim($data['profile_picture'] ?? '');
        }

        $params[] = $user_id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $payload = ["success" => true, "message" => "Profile updated successfully!"];
        $buf = ob_get_clean();
        if (trim($buf) !== '') {
            error_log("profile.php unexpected output: " . $buf);
        }
        echo json_encode($payload);
    } catch (Exception $e) {
        $buf = ob_get_clean();
        if (trim($buf) !== '') {
            error_log("profile.php unexpected output: " . $buf);
        }
        echo json_encode(["success" => false, "message" => "Error updating profile: " . $e->getMessage()]);
    }
} else {
    $buf = ob_get_clean();
    if (trim($buf) !== '') {
        error_log("profile.php unexpected output: " . $buf);
    }
    echo json_encode(["success" => false, "message" => "Invalid action"]);
}
?>

