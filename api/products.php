<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        $products = $stmt->fetchAll();
        
        // Also fetch categories for the dropdown
        $stmtCat = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmtCat->fetchAll();

        echo json_encode(['success' => true, 'products' => $products, 'categories' => $categories]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($method === 'POST') {
    // Add or Update
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $data['action'] ?? '';
    $code = sanitize_input($data['code'] ?? '');
    $name = sanitize_input($data['name'] ?? '');
    $category_id = intval($data['category_id'] ?? 0);
    $cost_price = floatval($data['cost_price'] ?? 0);
    $selling_price = floatval($data['selling_price'] ?? 0);
    $stock_quantity = intval($data['stock_quantity'] ?? 0);

    if (empty($code) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Code and Name are required']);
        exit;
    }

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (code, name, category_id, cost_price, selling_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $category_id, $cost_price, $selling_price, $stock_quantity]);
            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        } elseif ($action === 'update') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("UPDATE products SET code=?, name=?, category_id=?, cost_price=?, selling_price=?, stock_quantity=? WHERE id=?");
            $stmt->execute([$code, $name, $category_id, $cost_price, $selling_price, $stock_quantity, $id]);
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } elseif ($action === 'delete') {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        }
    } catch (PDOException $e) {
        // Handle duplicate code error
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Product code already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
