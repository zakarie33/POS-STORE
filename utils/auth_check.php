<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login, redirect to index if not
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Check if user has specific role
 */
function has_role($roles) {
    if (!is_logged_in()) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    return $_SESSION['user_role'] === $roles;
}

/**
 * Require specific role, die if not
 */
function require_role($roles) {
    if (!has_role($roles)) {
        die("Unauthorized access. You do not have permission to view this page.");
    }
}

/**
 * Require login for API endpoints (returns JSON error)
 */
function api_require_login() {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
