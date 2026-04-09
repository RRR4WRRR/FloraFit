<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['gcash_qr']) || !is_array($_FILES['gcash_qr'])) {
    echo json_encode(['success' => false, 'message' => 'Please choose a QR image to upload.']);
    exit;
}

$file = $_FILES['gcash_qr'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try another image.']);
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'message' => 'Please upload an image under 5 MB.']);
    exit;
}

$temporaryPath = (string)($file['tmp_name'] ?? '');
if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
    echo json_encode(['success' => false, 'message' => 'Invalid uploaded file.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = strtolower((string)$finfo->file($temporaryPath));
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowedMimeTypes[$mimeType])) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed.']);
    exit;
}

$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/FloraFit/uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
    echo json_encode(['success' => false, 'message' => 'Upload folder is not available.']);
    exit;
}

foreach (glob($uploadDir . DIRECTORY_SEPARATOR . 'gcash_qr.*') ?: [] as $existingFile) {
    @unlink($existingFile);
}
foreach (glob($uploadDir . DIRECTORY_SEPARATOR . 'gcash-qr.*') ?: [] as $existingFile) {
    @unlink($existingFile);
}

$extension = $allowedMimeTypes[$mimeType];
$fileName = 'gcash_qr.' . $extension;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($temporaryPath, $destination)) {
    echo json_encode(['success' => false, 'message' => 'Could not save the QR image.']);
    exit;
}

@chmod($destination, 0644);

$relativePath = '/FloraFit/uploads/' . $fileName;
echo json_encode([
    'success' => true,
    'message' => 'GCash QR uploaded successfully.',
    'path' => $relativePath,
    'url' => $relativePath . '?v=' . (@filemtime($destination) ?: time())
]);
?>