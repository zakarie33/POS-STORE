<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();
require_role('admin'); // Only admin can change settings
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }

    $store_name = sanitize_input($data['store_name'] ?? '');
    $phone = sanitize_input($data['phone'] ?? '');
    $address = sanitize_input($data['address'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE settings SET store_name=?, phone=?, address=? WHERE id=1");
        $stmt->execute([$store_name, $phone, $address]);
        log_activity($pdo, $_SESSION['user_id'], 'update_settings', 'Store settings updated');
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
