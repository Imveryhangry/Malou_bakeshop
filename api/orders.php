<?php
// ============================================================
//  api/orders.php
//  Handles all operations for the Orders module.
//
//  Actions:
//    GET  ?action=list           → all orders with customer + total
//    GET  ?action=detail&id=X   → single order with all items
//    GET  ?action=customers      → all customers (for dropdown)
//    GET  ?action=products       → active products (for order form)
//    POST action=create          → new order (uses DB transaction)
//    POST action=update_status   → advance order status
//    POST action=cancel          → cancel an order
//    POST action=delete          → delete a Cancelled order
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST ALL ORDERS ──────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT o.order_id, o.status, o.due_date, o.notes,
               o.created_at, o.updated_at,
               c.customer_id, c.name AS customer_name,
               c.contact_number,
               COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total
        FROM orders o
        JOIN customers c ON c.customer_id = o.customer_id
        LEFT JOIN order_items oi ON oi.order_id = o.order_id
        GROUP BY o.order_id, o.status, o.due_date, o.notes,
                 o.created_at, o.updated_at,
                 c.customer_id, c.name, c.contact_number
        ORDER BY
            FIELD(o.status, 'Pending', 'In Progress', 'Completed', 'Cancelled'),
            o.due_date ASC
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}


if ($action === 'detail') {
    $order_id = (int)($_GET['id'] ?? 0);
    if ($order_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order.']);
        exit;
    }

    $stmtOrder = $pdo->prepare("
        SELECT o.order_id, o.status, o.due_date, o.notes,
               o.created_at, o.updated_at,
               c.name AS customer_name, c.contact_number
        FROM orders o
        JOIN customers c ON c.customer_id = o.customer_id
        WHERE o.order_id = ?
    ");
    $stmtOrder->execute([$order_id]);
    $order = $stmtOrder->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $stmtItems = $pdo->prepare("
        SELECT oi.order_item_id, oi.quantity, oi.unit_price,
               p.name AS product_name,
               (oi.quantity * oi.unit_price) AS subtotal
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order_id]);
    $order['items'] = $stmtItems->fetchAll();
    $order['order_total'] = array_sum(array_column($order['items'], 'subtotal'));

    echo json_encode(['success' => true, 'data' => $order]);
    exit;
}


if ($action === 'customers') {
    $stmt = $pdo->query("SELECT customer_id, name, contact_number FROM customers ORDER BY name");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}


if ($action === 'products') {
    $stmt = $pdo->query("
        SELECT p.product_id, p.name, p.price, c.name AS category_name
        FROM products p
        JOIN categories c ON c.category_id = p.category_id
        WHERE p.status = 'active'
        ORDER BY c.name, p.name
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// CREATE NEW ORDER

if ($action === 'create') {

    // -- Customer fields
    $customer_mode   = trim($_POST['customer_mode']   ?? '');  // 'existing' or 'new'
    $customer_id     = (int)($_POST['customer_id']    ?? 0);
    $customer_name   = trim($_POST['customer_name']   ?? '');
    $contact_number  = trim($_POST['contact_number']  ?? '');

    // -- Order fields
    $due_date        = trim($_POST['due_date']         ?? '');
    $notes           = trim($_POST['notes']            ?? '');

    $items_json      = $_POST['items'] ?? '[]';
    $items           = json_decode($items_json, true);

    // ---- Validation ----
    if (empty($due_date)) {
        echo json_encode(['success' => false, 'message' => 'Due date is required.']);
        exit;
    }
    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'An order must have at least one product.']);
        exit;
    }
    foreach ($items as $item) {
        if ((int)($item['product_id'] ?? 0) === 0 || (int)($item['quantity'] ?? 0) < 1) {
            echo json_encode(['success' => false, 'message' => 'Each item must have a valid product and quantity of at least 1.']);
            exit;
        }
    }

    // ---- Begin Transaction ----
    try {
        $pdo->beginTransaction();

        // Step 1: Resolve customer
        if ($customer_mode === 'new') {
            if (empty($customer_name) || empty($contact_number)) {
                throw new Exception('Customer name and contact number are required for new customers.');
            }
            $stmtCust = $pdo->prepare("INSERT INTO customers (name, contact_number) VALUES (?, ?)");
            $stmtCust->execute([$customer_name, $contact_number]);
            $customer_id = $pdo->lastInsertId();
        } else {
            if ($customer_id === 0) {
                throw new Exception('Please select an existing customer.');
            }
        }

        // Step 2: Create the order record
        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (customer_id, status, due_date, notes)
            VALUES (?, 'Pending', ?, ?)
        ");
        $stmtOrder->execute([$customer_id, $due_date, $notes]);
        $order_id = $pdo->lastInsertId();

        // Step 3: Insert each order item
        $stmtProduct = $pdo->prepare("SELECT price FROM products WHERE product_id = ? AND status = 'active'");
        $stmtItem    = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $stmtProduct->execute([(int)$item['product_id']]);
            $product = $stmtProduct->fetch();
            if (!$product) {
                throw new Exception('One or more selected products are no longer available.');
            }
            $stmtItem->execute([
                $order_id,
                (int)$item['product_id'],
                (int)$item['quantity'],
                $product['price']   // price fetched from DB, not from POST
            ]);
        }

        // All steps succeeded — commit to database
        $pdo->commit();

        echo json_encode([
            'success'  => true,
            'message'  => "Order #$order_id created successfully.",
            'order_id' => $order_id
        ]);

    } catch (Exception $e) {
        // Something failed — undo everything
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_status') {
    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($order_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order.']);
        exit;
    }

    // Fetch current status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Determine next status
    $flow = [
        'Pending'     => 'In Progress',
        'In Progress' => 'Completed',
    ];

    $current = $order['status'];
    if (!isset($flow[$current])) {
        echo json_encode(['success' => false, 'message' => "Order is already {$current} and cannot be advanced."]);
        exit;
    }

    $next = $flow[$current];

    try {
        $pdo->beginTransaction();

        // Advance the status
        $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmtUpdate->execute([$next, $order_id]);

        // If now Completed → auto-create sales record
        if ($next === 'Completed') {
            // Check a sales record doesn't already exist (safety guard)
            $checkSale = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE order_id = ?");
            $checkSale->execute([$order_id]);

            if ($checkSale->fetchColumn() == 0) {
                $stmtSale = $pdo->prepare("
                    INSERT INTO sales (order_id, total_amount)
                    SELECT ?, COALESCE(SUM(quantity * unit_price), 0)
                    FROM order_items
                    WHERE order_id = ?
                ");
                $stmtSale->execute([$order_id, $order_id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Order #$order_id marked as $next."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── CANCEL ORDER ─────────────────────────────────────────────
if ($action === 'cancel') {
    $order_id = (int)($_POST['order_id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE orders SET status = 'Cancelled'
        WHERE order_id = ? AND status IN ('Pending', 'In Progress')
    ");
    $stmt->execute([$order_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => "Order #$order_id has been cancelled."]);
    exit;
}

// ── DELETE ORDER ─────────────────────────────────────────────
// Only Cancelled orders can be deleted hard.
// Completed orders are permanent records — cannot be deleted.
if ($action === 'delete') {
    $order_id = (int)($_POST['order_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order || $order['status'] !== 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Only cancelled orders can be deleted.']);
        exit;
    }

    // order_items will cascade delete automatically (ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);

    echo json_encode(['success' => true, 'message' => "Order #$order_id has been deleted."]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);