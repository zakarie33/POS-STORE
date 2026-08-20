<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

header('Content-Type: application/json');
api_require_login();

// Only admins can receive and fetch notifications for now
if (!has_role('admin')) {
    json_response(['success' => true, 'notifications' => []]);
}

$action = $_GET['action'] ?? 'poll';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'poll') {
        // Poll for new notifications (for toasts) and get unread count
        $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
        
        try {
            // Get new notifications since last poll
            $stmt = $pdo->prepare("
                SELECT id, title, message, type, created_at 
                FROM notifications 
                WHERE (user_id = ? OR user_id IS NULL) 
                AND id > ? 
                ORDER BY id ASC
            ");
            $stmt->execute([$user_id, $last_id]);
            $new_notifications = $stmt->fetchAll();
            
            // Get total unread count
            $stmtCount = $pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE (user_id = ? OR user_id IS NULL) 
                AND is_read = 0
            ");
            $stmtCount->execute([$user_id]);
            $unread_count = $stmtCount->fetchColumn();

            json_response([
                'success' => true, 
                'notifications' => $new_notifications,
                'unread_count' => $unread_count
            ]);
        } catch (PDOException $e) {
            error_log("Notifications Poll Error: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Failed to poll notifications'], 500);
        }
    } elseif ($action === 'history') {
        // Fetch history for dropdown
        try {
            $stmt = $pdo->prepare("
                SELECT id, title, message, type, is_read, created_at 
                FROM notifications 
                WHERE (user_id = ? OR user_id IS NULL) 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $history = $stmt->fetchAll();

            json_response(['success' => true, 'notifications' => $history]);
        } catch (PDOException $e) {
            error_log("Notifications History Error: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Failed to fetch history'], 500);
        }
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'mark_read' || $action === 'clear_all')) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    
    if (!verify_csrf_token($data['csrf_token'] ?? '')) {
        json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    try {
        if ($action === 'mark_read') {
            $id = $data['id'] ?? null;
            if ($id === 'all') {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
                $stmt->execute([intval($id), $user_id]);
            }
            json_response(['success' => true]);
        } elseif ($action === 'clear_all') {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? OR user_id IS NULL");
            $stmt->execute([$user_id]);
            json_response(['success' => true]);
        }
    } catch (PDOException $e) {
        error_log("Notification Action Error: " . $e->getMessage());
        json_response(['success' => false, 'message' => 'Action failed'], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}
