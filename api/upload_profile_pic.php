<?php
header("Content-Type: application/json");

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['profile_pic'])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES['profile_pic'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Upload error"]);
    exit;
}

// Basic validation (you can expand checks)
$allowed = ['image/jpeg','image/png','image/webp'];
if (!in_array($file['type'], $allowed)) {
    echo json_encode(["success" => false, "message" => "Invalid file type"]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$target = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    // Return relative path for use in frontend
    $rel = 'uploads/' . $filename;
    echo json_encode(["success" => true, "path" => $rel]);
} else {
    echo json_encode(["success" => false, "message" => "Could not save file"]);
}
?>
