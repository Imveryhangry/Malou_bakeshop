<?php
// ============================================================
//  includes/sidebar.php
//  Included by every page inside the app shell.
//  $current_page is set by each page before including this.
//  Example: $current_page = 'orders';
// ============================================================
$current_page = $current_page ?? '';
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <h2>Malou Bakes <span>Dvo</span></h2>
    <p>Management System</p>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main</div>

    <a href="/malou-bakes/dashboard.php"
       class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Dashboard
    </a>

    <div class="sidebar-section-label">Manage</div>

    <a href="/malou-bakes/pages/orders.php"
       class="nav-item <?= $current_page === 'orders' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Orders
    </a>

    <a href="/malou-bakes/pages/products.php"
       class="nav-item <?= $current_page === 'products' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Products
    </a>

    <a href="/malou-bakes/pages/inventory.php"
       class="nav-item <?= $current_page === 'inventory' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Inventory
    </a>

    <div class="sidebar-section-label">Reports</div>

    <a href="/malou-bakes/pages/sales.php"
       class="nav-item <?= $current_page === 'sales' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Sales
    </a>

    <a href="/malou-bakes/pages/users.php"
       class="nav-item <?= $current_page === 'users' ? 'active' : '' ?>">
      <span class="nav-icon"></span> Users
    </a>
  </nav>

  <div class="sidebar-footer">
    <strong><?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></strong><br>
    <a href="/malou-bakes/api/auth.php?action=logout">Logout</a>
  </div>
</aside>