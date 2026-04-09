<?php
header('Content-Type: application/json; charset=utf-8');

// Always look in FloraFit/uploads/ (the correct public folder)
$baseDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/FloraFit/uploads';
$candidates = ['gcash_qr.jpg', 'gcash_qr.jpeg', 'gcash_qr.png', 'gcash_qr.webp', 'gcash-qr.jpg', 'gcash-qr.png'];

foreach ($candidates as $fileName) {
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($fullPath)) {
        $version = @filemtime($fullPath) ?: time();
        echo json_encode([
            'success' => true,
            'available' => true,
            'url' => '/FloraFit/uploads/' . $fileName . '?v=' . $version,
            'message' => 'GCash QR loaded successfully.'
        ]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'available' => false,
    'url' => null,
    'message' => 'GCash QR not uploaded yet. Add your QR image as uploads/gcash_qr.jpg, .jpeg, .png, or .webp.'
]);
?>