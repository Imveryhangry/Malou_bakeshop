<?php



session_start();
header('Content-Type: application/json');

// Block unauthenticated requests
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST ALL PRODUCTS ────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT p.product_id, p.name, p.description, p.price,
               p.status, p.created_at,
               c.name AS category_name, c.category_id
        FROM products p
        JOIN categories c ON c.category_id = p.category_id
        ORDER BY c.name, p.name
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── LIST ALL CATEGORIES (for the Add/Edit dropdown) ─────────
if ($action === 'categories') {
    $stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── CREATE NEW PRODUCT ───────────────────────────────────────
if ($action === 'create') {
    $name        = trim($_POST['name']        ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required.']);
        exit;
    }
    if ($category_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a category.']);
        exit;
    }
    if ($price < 0) {
        echo json_encode(['success' => false, 'message' => 'Price cannot be negative.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (category_id, name, description, price, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$category_id, $name, $description, $price, $status]);

    echo json_encode([
        'success' => true,
        'message' => "Product \"{$name}\" added successfully.",
        'id'      => $pdo->lastInsertId()
    ]);
    exit;
}

// ── UPDATE PRODUCT ───────────────────────────────────────────
if ($action === 'update') {
    $product_id  = (int)($_POST['product_id']  ?? 0);
    $name        = trim($_POST['name']          ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']      ?? 0);
    $description = trim($_POST['description']  ?? '');
    $status      = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($product_id === 0 || empty($name) || $category_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET category_id = ?, name = ?, description = ?, price = ?, status = ?
        WHERE product_id = ?
    ");
    $stmt->execute([$category_id, $name, $description, $price, $status, $product_id]);

    echo json_encode(['success' => true, 'message' => "Product \"{$name}\" updated successfully."]);
    exit;
}

// ── DELETE PRODUCT ───────────────────────────────────────────
if ($action === 'delete') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }

    // Business rule: cannot delete a product linked to an active/pending order
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE oi.product_id = ?
          AND o.status IN ('Pending', 'In Progress')
    ");
    $check->execute([$product_id]);

    if ($check->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete this product — it is linked to an active order.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE product_id = ?");
    $stmt->execute([$product_id]);

    echo json_encode(['success' => true, 'message' => 'Product removed from catalog.']);
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);