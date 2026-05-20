<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? '';


if ($action === 'overview') {
    $totalRevenue = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) FROM sales
    ")->fetchColumn();

    $monthRevenue = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) FROM sales
        WHERE DATE_FORMAT(sale_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ")->fetchColumn();

    $totalOrders = $pdo->query("
        SELECT COUNT(*) FROM orders WHERE status = 'Completed'
    ")->fetchColumn();

    $avgOrder = $pdo->query("
        SELECT COALESCE(AVG(total_amount), 0) FROM sales
    ")->fetchColumn();

    echo json_encode([
        'success' => true,
        'data'    => [
            'total_revenue' => $totalRevenue,
            'month_revenue' => $monthRevenue,
            'total_orders'  => $totalOrders,
            'avg_order'     => $avgOrder,
        ]
    ]);
    exit;
}

// SUMMARY 

if ($action === 'summary') {
    $period = $_GET['period'] ?? 'daily';

    // Choose grouping format based on period
    $groupFormat = match($period) {
        'weekly'  => '%x-W%v',          // e.g. 2026-W19
        'monthly' => '%Y-%m',           // e.g. 2026-05
        default   => '%Y-%m-%d',        // e.g. 2026-05-08  (daily)
    };

    $labelFormat = match($period) {
        'weekly'  => '%x — Week %v',
        'monthly' => '%M %Y',
        default   => '%M %d, %Y',
    };

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(s.sale_date, :groupFmt)  AS period_key,
            DATE_FORMAT(s.sale_date, :labelFmt)  AS period_label,
            COUNT(s.sale_id)                      AS order_count,
            SUM(s.total_amount)                   AS total_sales,
            AVG(s.total_amount)                   AS avg_sale
        FROM sales s
        GROUP BY period_key, period_label
        ORDER BY MIN(s.sale_date) DESC
        LIMIT 60
    ");
    $stmt->execute([':groupFmt' => $groupFormat, ':labelFmt' => $labelFormat]);

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'best_sellers') {
    $limit = min((int)($_GET['limit'] ?? 10), 50);

    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            p.name                  AS product_name,
            cat.name                AS category,
            SUM(oi.quantity)        AS total_qty_sold,
            COUNT(DISTINCT o.order_id) AS times_ordered,
            SUM(oi.quantity * oi.unit_price) AS total_revenue
        FROM order_items oi
        JOIN products   p   ON p.product_id   = oi.product_id
        JOIN categories cat ON cat.category_id = p.category_id
        JOIN orders     o   ON o.order_id      = oi.order_id
        WHERE o.status = 'Completed'
        GROUP BY p.product_id, p.name, cat.name
        ORDER BY total_qty_sold DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}


if ($action === 'all_records') {
    $stmt = $pdo->query("
        SELECT s.sale_id, s.order_id, s.total_amount, s.sale_date,
               c.name AS customer_name
        FROM sales s
        JOIN orders    o ON o.order_id   = s.order_id
        JOIN customers c ON c.customer_id = o.customer_id
        ORDER BY s.sale_date DESC
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);