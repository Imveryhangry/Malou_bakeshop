<?php
// ============================================================
//  config/db.php
//  Database connection — included by every PHP file that
//  needs to read or write to the database.
//
//  We use PDO (PHP Data Objects) instead of mysqli because:
//   - It supports prepared statements (protection against SQL injection)
//   - It works with multiple database types if you ever switch
//   - Error handling is cleaner
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'malou_bakes_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

// DSN = Data Source Name — tells PDO where and how to connect
$dsn = "mysql:host=" . DB_HOST
     . ";dbname="    . DB_NAME
     . ";charset="   . DB_CHARSET;

// PDO options:
//   ERRMODE_EXCEPTION  → throw an error instead of silently failing
//   DEFAULT_FETCH_ASSOC → rows come back as ['column' => 'value'] arrays
//   EMULATE_PREPARES false → use real prepared statements (more secure)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // In production you would log this, not display it.
    // For development it's fine to show the message.
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}
// $pdo is now available to any file that does: require_once 'config/db.php';