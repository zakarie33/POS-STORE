<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
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
    $description = sanitize_input($data['description'] ?? '');

    if (empty($name) && $action !== 'delete') {
        echo json_encode(['success' => false, 'message' => 'Name is required']); exit;
    }

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            echo json_encode(['success' => true, 'message' => 'Category added']);
        } elseif ($action === 'update') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
            $stmt->execute([$name, $description, $id]);
            echo json_encode(['success' => true, 'message' => 'Category updated']);
        } elseif ($action === 'delete') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Category deleted']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Category is in use or duplicate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
