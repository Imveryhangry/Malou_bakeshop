<?php


session_start();

// All responses from this file are JSON
header('Content-Type: application/json');

// Path goes up one level (..) because this file is inside /api/
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── LOGOUT ──────────────────────────────────────────────────
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    // Redirect to login — not JSON, this is a page redirect
    header('Location: ../index.php');
    exit;
}

// ── LOGIN ───────────────────────────────────────────────────
if ($method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation — fields must not be empty
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        exit;
    }

    // Prepared statement: the ? is a placeholder — PDO fills it in safely.
    // This prevents SQL Injection (a very common attack on beginner PHP apps).
    $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // password_verify() compares the submitted password against the stored hash
    if ($user && password_verify($password, $user['password_hash'])) {
        // Store user info in the session (server-side memory per browser tab)
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect username or password.']);
    }
    exit;
}

// Any other HTTP method is not allowed
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);