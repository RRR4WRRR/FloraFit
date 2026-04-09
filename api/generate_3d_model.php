<?php
header('Content-Type: application/json');

ini_set('log_errors', 1);
ini_set('error_log', './uploads/3d/debug.log');
error_reporting(E_ALL);

$apiKey = 'tsk_Mg2mP8bQR-pvh_K5q4PuBA3GiLhmH7mB7Vzk9IVDnfo';

error_log('=== generate_3d_model.php called ===');
error_log('FILES: ' . print_r($_FILES, true));

$imageFile = $_FILES['image'] ?? null;

if (!$imageFile || $imageFile['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $imageFile['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are supported']);
    exit;
}

// Map MIME type to the string Tripo3D expects
$tripoType = match($mimeType) {
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => 'jpg'
};

// Ensure save directory exists
$saveDir = './uploads/3d/';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

// ============================================================
// STEP 1: Upload image to Tripo3D
// ============================================================
$ch = curl_init('https://api.tripo3d.ai/v2/openapi/upload/sts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile(
            $imageFile['tmp_name'],
            $mimeType,
            basename($imageFile['name'])
        )
    ]
]);

$uploadRaw  = curl_exec($ch);
$uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$uploadErr  = curl_error($ch);
curl_close($ch);

error_log('STEP 1 response code: ' . $uploadCode);
error_log('STEP 1 response body: ' . $uploadRaw);

if ($uploadErr || $uploadCode < 200 || $uploadCode >= 300) {
    $uploadErrorData = json_decode($uploadRaw, true);
    $uploadMessage = $uploadErrorData['message'] ?? ($uploadErr ?: "HTTP $uploadCode");
    $uploadSuggestion = $uploadErrorData['suggestion'] ?? null;

    echo json_encode([
        'success' => false,
        'message' => 'Image upload failed: ' . $uploadMessage . ($uploadSuggestion ? ' — ' . $uploadSuggestion : ''),
        'raw'     => $uploadErrorData
    ]);
    exit;
}

$uploadData = json_decode($uploadRaw, true);
$fileToken = $uploadData['data']['image_token'] ?? $uploadData['data']['file_token'] ?? null;

if (!$fileToken) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not get upload token from Tripo3D',
        'raw'     => $uploadData
    ]);
    exit;
}

// Build payload — Tripo3D v2 API expects file_token here, even when the upload response field is named image_token.
$payload = json_encode([
    'type'          => 'image_to_model',
    'file'          => [
        'type'       => $tripoType,
        'file_token' => $fileToken
    ],
    'model_version' => 'v2.0-20240919'
]);

error_log('STEP 2 payload: ' . $payload);
error_log('STEP 2 file_token: ' . $fileToken);
error_log('STEP 2 tripoType: ' . $tripoType);

$ch = curl_init('https://api.tripo3d.ai/v2/openapi/task');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$taskRaw  = curl_exec($ch);
$taskCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$taskErr  = curl_error($ch);
curl_close($ch);

error_log('STEP 2 response code: ' . $taskCode);
error_log('STEP 2 response body: ' . $taskRaw);

if ($taskErr || $taskCode < 200 || $taskCode >= 300) {
    $taskErrorData = json_decode($taskRaw, true);
    $taskMessage = $taskErrorData['message'] ?? ($taskErr ?: "HTTP $taskCode");
    $taskSuggestion = $taskErrorData['suggestion'] ?? null;

    echo json_encode([
        'success' => false,
        'message' => 'Task creation failed: ' . $taskMessage . ($taskSuggestion ? ' — ' . $taskSuggestion : ''),
        'raw'     => $taskErrorData
    ]);
    exit;
}

$taskData = json_decode($taskRaw, true);
$taskId   = $taskData['data']['task_id'] ?? null;

if (!$taskId) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not get task ID from Tripo3D',
        'raw'     => $taskData
    ]);
    exit;
}

// ============================================================
// STEP 3: Poll until done (max 5 minutes, check every 5s)
// ============================================================
set_time_limit(360); // Allow PHP up to 6 minutes so it doesn't die mid-poll

$maxAttempts = 60; // 60 × 5s = 300 seconds (5 minutes)

for ($i = 0; $i < $maxAttempts; $i++) {
    sleep(5);

    $ch = curl_init("https://api.tripo3d.ai/v2/openapi/task/{$taskId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
        ]
    ]);

    $pollRaw  = curl_exec($ch);
    $pollCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($pollCode !== 200) continue;

    $pollData = json_decode($pollRaw, true);
    $status   = $pollData['data']['status'] ?? '';

    if ($status === 'success') {
        // --------------------------------------------------------
        // STEP 4: Download the GLB and save locally
        // --------------------------------------------------------
        $glbUrl = $pollData['data']['output']['model'] ?? null;

        if (!$glbUrl) {
            echo json_encode([
                'success' => false,
                'message' => 'Task succeeded but no GLB URL found',
                'raw'     => $pollData
            ]);
            exit;
        }

        $filename = 'flower_' . time() . '_' . uniqid() . '.glb';
        $savePath = $saveDir . $filename;

        $ch = curl_init($glbUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $glbData = curl_exec($ch);
        $dlErr   = curl_error($ch);
        curl_close($ch);

        if ($dlErr || !$glbData) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to download GLB file: ' . $dlErr
            ]);
            exit;
        }

        file_put_contents($savePath, $glbData);

        echo json_encode([
            'success'   => true,
            'model_url' => $saveDir . $filename,
            'task_id'   => $taskId
        ]);
        exit;
    }

    if ($status === 'failed' || $status === 'cancelled') {
        echo json_encode([
            'success' => false,
            'message' => "Task ended with status: {$status}",
            'task_id' => $taskId
        ]);
        exit;
    }

    // status is 'queued' or 'running' — keep polling
}

echo json_encode([
    'success' => false,
    'message' => 'Generation timed out after 5 minutes. Try again or check your Tripo3D dashboard.',
    'task_id' => $taskId ?? null
]);
?>
