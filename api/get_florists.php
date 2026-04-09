<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT u.id,
               CONCAT(u.first_name, ' ', u.last_name) AS name,
               u.email,
               (u.is_first_login = 0) AS active,
               (SELECT COUNT(*) FROM orders o WHERE o.assigned_florist = CONCAT(u.first_name, ' ', u.last_name)) AS total_orders,
               (SELECT COALESCE(SUM(o.florist_commission), 0) FROM orders o WHERE o.assigned_florist = CONCAT(u.first_name, ' ', u.last_name)) AS total_commission
        FROM users u
        WHERE u.role = 'florist'
        ORDER BY u.first_name ASC
    ");
    $stmt->execute();
    $florists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($florists as &$f) {
        $f['active'] = (int) $f['active'];
    }
    unset($f);

    echo json_encode($florists);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
