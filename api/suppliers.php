<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
        echo json_encode(['success' => true, 'suppliers' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }

    $action = $data['action'] ?? '';
    $name = sanitize_input($data['name'] ?? '');
    $phone = sanitize_input($data['phone'] ?? '');
    $address = sanitize_input($data['address'] ?? '');

    if (empty($name) && $action !== 'delete') {
        echo json_encode(['success' => false, 'message' => 'Name is required']); exit;
    }

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)");
            $stmt->execute([$name, $phone, $address]);
            echo json_encode(['success' => true, 'message' => 'Supplier added']);
        } elseif ($action === 'update') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$name, $phone, $address, $id]);
            echo json_encode(['success' => true, 'message' => 'Supplier updated']);
        } elseif ($action === 'delete') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
