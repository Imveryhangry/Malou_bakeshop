<?php

require_once 'config/session.php';
require_once 'config/db.php';

// ── Fetch summary numbers ────────────────────────────────────

// Total orders today
$todayOrders = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

// Pending orders count
$pendingOrders = $pdo->query("
    SELECT COUNT(*) FROM orders WHERE status = 'Pending'
")->fetchColumn();

// Total sales this month
$monthlySales = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) FROM sales
    WHERE DATE_FORMAT(sale_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
")->fetchColumn();

// Low stock items count
$lowStockCount = $pdo->query("
    SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level
")->fetchColumn();

// Recent 5 orders
$recentOrders = $pdo->query("
    SELECT o.order_id, c.name AS customer, o.status,
           o.due_date, o.created_at
    FROM orders o
    JOIN customers c ON c.customer_id = o.customer_id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll();

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- TOP BAR -->
  <header class="topbar">
    <span class="topbar-title">Dashboard</span>
    <div class="topbar-user">
      <div class="topbar-avatar">
        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
      </div>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <div class="page-header">
      <div>
        <h2>Good <?= (date('H') < 12) ? 'Morning' : ((date('H') < 18) ? 'Afternoon' : 'Evening') ?> </h2>
        <p>Here's what's happening at Malou Bakes today — <?= date('F j, Y') ?></p>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card accent-rose">
        <div class="stat-card-label">Orders Today</div>
        <div class="stat-card-value"><?= $todayOrders ?></div>
        <div class="stat-card-sub">New orders placed today</div>
      </div>

      <div class="stat-card accent-amber">
        <div class="stat-card-label">Pending Orders</div>
        <div class="stat-card-value"><?= $pendingOrders ?></div>
        <div class="stat-card-sub">Awaiting action</div>
      </div>

      <div class="stat-card accent-green">
        <div class="stat-card-label">Sales This Month</div>
        <div class="stat-card-value">₱<?= number_format($monthlySales, 0) ?></div>
        <div class="stat-card-sub"><?= date('F Y') ?></div>
      </div>

      <div class="stat-card <?= $lowStockCount > 0 ? 'accent-red' : 'accent-green' ?>">
        <div class="stat-card-label">Low Stock Alerts</div>
        <div class="stat-card-value"><?= $lowStockCount ?></div>
        <div class="stat-card-sub">
          <?= $lowStockCount > 0 ? 'Items need restocking' : 'All items are sufficient' ?>
        </div>
      </div>
    </div>

    <!-- RECENT ORDERS TABLE -->
    <div class="card">
      <div class="card-header">
        <h3>Recent Orders</h3>
        <a href="pages/orders.php" class="btn btn-ghost btn-sm">View All</a>
      </div>

      <div class="table-wrap">
        <?php if (empty($recentOrders)): ?>
          <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <p>No orders yet. Add your first order to get started.</p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $row): ?>
              <tr>
                <td class="text-muted">#<?= $row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['customer']) ?></td>
                <td><?= statusBadgePhp($row['status']) ?></td>
                <td><?= date('M j, Y', strtotime($row['due_date'])) ?></td>
                <td class="text-muted text-sm"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
<?php
// PHP version of statusBadge (for server-rendered tables)
function statusBadgePhp($status) {
    $map = [
        'Pending'     => 'badge-pending',
        'In Progress' => 'badge-progress',
        'Completed'   => 'badge-completed',
        'Cancelled'   => 'badge-cancelled',
    ];
    $cls = $map[$status] ?? 'badge-cancelled';
    return "<span class='badge {$cls}'>{$status}</span>";
}
?>
</body>
</html>