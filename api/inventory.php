<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST ALL INVENTORY ITEMS ─────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT item_id, name, quantity, unit, reorder_level, updated_at,
               CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END AS is_low_stock
        FROM inventory
        ORDER BY is_low_stock DESC, name ASC
    ");
    // Low stock items bubble to the top automatically
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── CREATE NEW INVENTORY ITEM ────────────────────────────────
if ($action === 'create') {
    $name          = trim($_POST['name']          ?? '');
    $quantity      = (float)($_POST['quantity']   ?? 0);
    $unit          = trim($_POST['unit']          ?? '');
    $reorder_level = (float)($_POST['reorder_level'] ?? 0);

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required.']);
        exit;
    }
    if (empty($unit)) {
        echo json_encode(['success' => false, 'message' => 'Unit of measurement is required.']);
        exit;
    }
    if ($quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity cannot be negative.']);
        exit;
    }
    if ($reorder_level < 0) {
        echo json_encode(['success' => false, 'message' => 'Reorder level cannot be negative.']);
        exit;
    }

    // Check for duplicate name (UNIQUE constraint on inventory.name)
    $check = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => "An item named \"{$name}\" already exists."]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO inventory (name, quantity, unit, reorder_level)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $quantity, $unit, $reorder_level]);

    echo json_encode([
        'success' => true,
        'message' => "\"{$name}\" added to inventory.",
        'id'      => $pdo->lastInsertId()
    ]);
    exit;
}

// ── UPDATE ITEM DETAILS ──────────────────────────────────────
if ($action === 'update') {
    $item_id       = (int)($_POST['item_id']      ?? 0);
    $name          = trim($_POST['name']          ?? '');
    $unit          = trim($_POST['unit']          ?? '');
    $reorder_level = (float)($_POST['reorder_level'] ?? 0);

    if ($item_id === 0 || empty($name) || empty($unit)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Check duplicate name — exclude the current item itself
    $check = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE name = ? AND item_id != ?");
    $check->execute([$name, $item_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => "Another item named \"{$name}\" already exists."]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE inventory
        SET name = ?, unit = ?, reorder_level = ?
        WHERE item_id = ?
    ");
    $stmt->execute([$name, $unit, $reorder_level, $item_id]);

    echo json_encode(['success' => true, 'message' => "\"{$name}\" updated successfully."]);
    exit;
}

// ── RESTOCK (update quantity only) ───────────────────────────
// Separated from full update so restocking is a distinct,
// clear action — no risk of accidentally changing item name.
if ($action === 'restock') {
    $item_id      = (int)($_POST['item_id']   ?? 0);
    $new_quantity = (float)($_POST['quantity'] ?? -1);

    if ($item_id === 0 || $new_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity or item.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE item_id = ?");
    $stmt->execute([$new_quantity, $item_id]);

    echo json_encode(['success' => true, 'message' => 'Stock level updated.']);
    exit;
}

// ── DELETE INVENTORY ITEM ────────────────────────────────────
if ($action === 'delete') {
    $item_id = (int)($_POST['item_id'] ?? 0);

    if ($item_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
    $stmt->execute([$item_id]);

    echo json_encode(['success' => true, 'message' => 'Item removed from inventory.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);