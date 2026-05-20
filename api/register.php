<?php
// ============================================================
//  api/register.php
//  Creates new user accounts.
//  IMPORTANT: Only accessible by a logged-in admin.
//  This is NOT a public endpoint.
//
//  Actions:
//    POST action=create  → register a new user
//    GET  ?action=list   → list all users
//    POST action=delete  → delete a user (cannot delete yourself)
// ============================================================

session_start();
header('Content-Type: application/json');

// Block anyone who is not already logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST ALL USERS ───────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT user_id, username, created_at FROM users ORDER BY created_at ASC
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── CREATE NEW USER ──────────────────────────────────────────
if ($action === 'create') {
    $username        = trim($_POST['username']         ?? '');
    $password        = trim($_POST['password']         ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    // Validation
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required.']);
        exit;
    }
    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
        exit;
    }
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password is required.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    if ($password !== $password_confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Check if username already exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => "Username \"{$username}\" is already taken."]);
        exit;
    }

    // Hash the password — NEVER store plain text
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);

    echo json_encode([
        'success' => true,
        'message' => "Account \"{$username}\" created successfully.",
        'id'      => $pdo->lastInsertId()
    ]);
    exit;
}

// ── DELETE USER ──────────────────────────────────────────────
if ($action === 'delete') {
    $target_id = (int)($_POST['user_id'] ?? 0);

    // Cannot delete yourself
    if ($target_id === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }

    // Cannot delete the last remaining user
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the only remaining account.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$target_id]);

    echo json_encode(['success' => true, 'message' => 'Account deleted.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);