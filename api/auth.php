<?php
require_once '../config/db.php';
require_once '../utils/security.php';

header('Content-Type: application/json');

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Check CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

// Sanitize input
$username = sanitize_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    json_response(['success' => false, 'message' => 'Username and password are required']);
}

try {
    // Basic rate limiting (login attempts)
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE ip_address = ? AND action = 'login_failed' AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    $failed_attempts = $stmt->fetchColumn();

    if ($failed_attempts >= 5) {
        json_response(['success' => false, 'message' => 'Too many failed login attempts. Please try again after 15 minutes.'], 429);
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            log_activity($pdo, $user['id'], 'login_failed', 'Inactive account attempt');
            json_response(['success' => false, 'message' => 'Your account is inactive. Please contact admin.']);
        }

        // Login successful
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        log_activity($pdo, $user['id'], 'login_success', 'User logged in successfully');

        json_response([
            'success' => true, 
            'message' => 'Login successful',
            'redirect' => 'pages/dashboard.php'
        ]);
    } else {
        // Login failed
        $user_id = $user ? $user['id'] : null;
        log_activity($pdo, $user_id, 'login_failed', 'Invalid password or username');
        json_response(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    // Log error internally, don't expose DB errors to frontend
    error_log($e->getMessage());
    json_response(['success' => false, 'message' => 'An internal server error occurred'], 500);
}
