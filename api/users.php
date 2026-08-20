<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

header('Content-Type: application/json');
require_role('admin');

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Fetch users
        try {
            $stmt = $pdo->query("SELECT id, name, username, role, status, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            json_response(['success' => true, 'data' => $users]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            json_response(['success' => false, 'message' => 'Failed to fetch users'], 500);
        }
        break;

    case 'POST':
        // Check CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $name = sanitize_input($_POST['name'] ?? '');
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize_input($_POST['role'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');

        // Validation
        if (empty($name) || empty($username) || empty($password) || empty($role)) {
            json_response(['success' => false, 'message' => 'All required fields must be filled']);
        }
        if (strlen($password) < 6) {
            json_response(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        }
        if (!in_array($role, ['admin', 'manager', 'cashier'])) {
            json_response(['success' => false, 'message' => 'Invalid role']);
        }
        if (!in_array($status, ['active', 'inactive'])) {
            json_response(['success' => false, 'message' => 'Invalid status']);
        }

        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                json_response(['success' => false, 'message' => 'Username already exists']);
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $password_hash, $role, $status]);
            
            $new_user_id = $pdo->lastInsertId();
            log_activity($pdo, $_SESSION['user_id'], 'create_user', "Created user ID: $new_user_id ($username)");

            json_response(['success' => true, 'message' => 'User created successfully']);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            json_response(['success' => false, 'message' => 'Failed to create user'], 500);
        }
        break;

    case 'PUT':
        // Get PUT data
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
            // Toggle Status functionality
            $id = $data['id'] ?? 0;
            $status = $data['status'] ?? '';
            
            if ($id == $_SESSION['user_id']) {
                json_response(['success' => false, 'message' => 'You cannot deactivate your own account.']);
            }
            if (!in_array($status, ['active', 'inactive'])) {
                json_response(['success' => false, 'message' => 'Invalid status']);
            }

            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                
                log_activity($pdo, $_SESSION['user_id'], 'update_user_status', "Updated status for user ID: $id to $status");
                json_response(['success' => true, 'message' => 'User status updated successfully']);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                json_response(['success' => false, 'message' => 'Failed to update user status'], 500);
            }
        } else {
            // Full Update functionality
            $id = $data['id'] ?? 0;
            $name = sanitize_input($data['name'] ?? '');
            $username = sanitize_input($data['username'] ?? '');
            $password = $data['password'] ?? '';
            $role = sanitize_input($data['role'] ?? '');
            $status = sanitize_input($data['status'] ?? 'active');

            if (empty($id) || empty($name) || empty($username) || empty($role)) {
                json_response(['success' => false, 'message' => 'All required fields must be filled']);
            }
            if (!in_array($role, ['admin', 'manager', 'cashier'])) {
                json_response(['success' => false, 'message' => 'Invalid role']);
            }
            if (!in_array($status, ['active', 'inactive'])) {
                json_response(['success' => false, 'message' => 'Invalid status']);
            }
            if ($id == $_SESSION['user_id'] && $status === 'inactive') {
                json_response(['success' => false, 'message' => 'You cannot deactivate your own account.']);
            }

            try {
                // Check username uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Username already exists']);
                }

                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        json_response(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                    }
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, password_hash = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $username, $password_hash, $role, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $username, $role, $status, $id]);
                }

                log_activity($pdo, $_SESSION['user_id'], 'update_user', "Updated user ID: $id");
                json_response(['success' => true, 'message' => 'User updated successfully']);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                json_response(['success' => false, 'message' => 'Failed to update user'], 500);
            }
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
