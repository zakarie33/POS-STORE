<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change if necessary
define('DB_PASS', '');     // Change if necessary
define('DB_NAME', 'super_store');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check config/db.php and ensure MySQL is running.'
    ]));
}
