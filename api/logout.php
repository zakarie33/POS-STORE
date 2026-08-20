<?php
require_once '../config/db.php';
require_once '../utils/security.php';

log_activity($pdo ?? null, $_SESSION['user_id'] ?? null, 'logout', 'User logged out');
session_destroy();
header("Location: ../index.php");
exit;
