<?php
header("Content-Type: application/json");
include '../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$type    = $data['type']    ?? null;
$value   = $data['value']   ?? null;

if (!$user_id || !$type || !$value) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

if (!in_array($type, ['percentage', 'fixed'])) {
    echo json_encode(["success" => false, "message" => "Invalid voucher type."]);
    exit;
}

if (!is_numeric($value) || $value <= 0) {
    echo json_encode(["success" => false, "message" => "Value must be a positive number."]);
    exit;
}

if ($type === 'percentage' && $value > 100) {
    echo json_encode(["success" => false, "message" => "Percentage cannot exceed 100."]);
    exit;
}

function generateCode($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

try {
    do {
        $code = generateCode();
        $check = $conn->prepare("SELECT id FROM vouchers WHERE code = ?");
        $check->execute([$code]);
    } while ($check->fetch());

    $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    $created_at  = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO vouchers (user_id, code, type, value, status, expiry_date, created_at)
        VALUES (?, ?, ?, ?, 'active', ?, ?)
    ");
    $stmt->execute([$user_id, $code, $type, $value, $expiry_date, $created_at]);

    echo json_encode([
        "success" => true,
        "message" => "Voucher assigned successfully.",
        "voucher" => [
            "code"        => $code,
            "type"        => $type,
            "value"       => $value,
            "expiry_date" => $expiry_date
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
