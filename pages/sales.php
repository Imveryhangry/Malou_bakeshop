<?php

require_once __DIR__ . '/../config/session.php';
$current_page = 'sales';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .period-tabs { display:flex; gap:.5rem; margin-bottom:1.25rem; }
    .period-tab  {
      padding:.45rem 1.1rem; border:1.5px solid var(--clr-border);
      border-radius:var(--radius-sm); background:transparent;
      font-size:.85rem; cursor:pointer; color:var(--clr-text-muted);
      transition: all var(--dur) var(--ease);
    }
    .period-tab.active {
      background:var(--clr-primary); border-color:var(--clr-primary);
      color:#fff; font-weight:600;
    }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .rank-num {
      display:inline-flex; align-items:center; justify-content:center;
      width:24px; height:24px; border-radius:50%;
      background:var(--clr-primary-lt); color:var(--clr-primary);
      font-size:.75rem; font-weight:700; flex-shrink:0;
    }
    .rank-num.gold   { background:#fff3cd; color:#b8860b; }
    .rank-num.silver { background:#f0f0f0; color:#666;    }
    .rank-num.bronze { background:#fdebd0; color:#a0522d; }
    .bar-wrap { flex:1; background:var(--clr-border); border-radius:99px; height:8px; overflow:hidden; }
    .bar-fill { height:100%; background:var(--clr-primary); border-radius:99px; transition:width .6s var(--ease); }
    @media(max-width:900px){ .two-col{ grid-template-columns:1fr; } }
  </style>
</head>
<body>

<div class="app-layout">

  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <header class="topbar">
    <span class="topbar-title">Sales</span>
    <div class="topbar-user">
      <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
  </header>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h2>Sales Reports</h2>
        <p>All sales are recorded automatically when an order is marked as Completed.</p>
      </div>
    </div>

    <!-- OVERVIEW STAT CARDS -->
    <div class="stats-grid" id="overview-stats">
      <div class="stat-card accent-green">
        <div class="stat-card-label">Total Revenue</div>
        <div class="stat-card-value" id="stat-total">—</div>
        <div class="stat-card-sub">All time</div>
      </div>
      <div class="stat-card accent-rose">
        <div class="stat-card-label">This Month</div>
        <div class="stat-card-value" id="stat-month">—</div>
        <div class="stat-card-sub" id="stat-month-label">—</div>
      </div>
      <div class="stat-card accent-amber">
        <div class="stat-card-label">Completed Orders</div>
        <div class="stat-card-value" id="stat-orders">—</div>
        <div class="stat-card-sub">All time</div>
      </div>
      <div class="stat-card accent-green">
        <div class="stat-card-label">Average Order Value</div>
        <div class="stat-card-value" id="stat-avg">—</div>
        <div class="stat-card-sub">Per completed order</div>
      </div>
    </div>

    <!-- SUMMARY TABLE + BEST SELLERS side by side -->
    <div class="two-col">

      <!-- LEFT: Sales Summary by Period -->
      <div>
        <div class="period-tabs">
          <button class="period-tab active" onclick="loadSummary('daily',   this)">Daily</button>
          <button class="period-tab"        onclick="loadSummary('weekly',  this)">Weekly</button>
          <button class="period-tab"        onclick="loadSummary('monthly', this)">Monthly</button>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 id="summary-heading">Daily Sales</h3>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Period</th>
                  <th style="text-align:center;">Orders</th>
                  <th style="text-align:right;">Total Sales</th>
                  <th style="text-align:right;">Avg / Order</th>
                </tr>
              </thead>
              <tbody id="summary-tbody">
                <tr><td colspan="4">
                  <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div>
                </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT: Best Sellers -->
      <div>
        <div style="margin-bottom:1rem;height:2.25rem;display:flex;align-items:center;">
          <span style="font-size:.85rem;color:var(--clr-text-muted);">From all completed orders</span>
        </div>

        <div class="card">
          <div class="card-header">
            <h3>Best-Selling Products</h3>
          </div>
          <div class="card-body" id="best-sellers-body">
            <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div>
          </div>
        </div>
      </div>

    </div>

    <!-- FULL SALES RECORD TABLE -->
    <div class="mt-3">
      <div class="card">
        <div class="card-header">
          <h3>All Sales Records</h3>
          <span class="text-sm text-muted">Auto-generated when orders are completed</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Sale #</th>
                <th>Order #</th>
                <th>Customer</th>
                <th>Date & Time</th>
                <th style="text-align:right;">Total Amount</th>
              </tr>
            </thead>
            <tbody id="all-sales-tbody">
              <tr><td colspan="4">
                <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div>
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadOverview();
  loadSummary('daily', document.querySelector('.period-tab.active'));
  loadBestSellers();
  loadAllSales();
});

// ── OVERVIEW STATS ────────────────────────────────────────────
async function loadOverview() {
  const data = await apiGet('../api/sales.php?action=overview');
  if (!data.success) return;
  const d = data.data;

  document.getElementById('stat-total').textContent  = peso(d.total_revenue);
  document.getElementById('stat-month').textContent  = peso(d.month_revenue);
  document.getElementById('stat-orders').textContent = d.total_orders;
  document.getElementById('stat-avg').textContent    = peso(d.avg_order);

  const now = new Date();
  document.getElementById('stat-month-label').textContent =
    now.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
}

// ── PERIOD SUMMARY TABLE ──────────────────────────────────────
async function loadSummary(period, btn) {
  // Update active tab
  document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
  if (btn) btn.classList.add('active');

  const labels = { daily: 'Daily Sales', weekly: 'Weekly Sales', monthly: 'Monthly Sales' };
  document.getElementById('summary-heading').textContent = labels[period] || 'Sales';

  const tbody = document.getElementById('summary-tbody');
  tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div></td></tr>`;

  const data = await apiGet(`../api/sales.php?action=summary&period=${period}`);

  if (!data.success || data.data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4">
      <div class="empty-state"><div class="empty-icon">💰</div>
      <p>No sales records yet. Complete an order to see data here.</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = data.data.map(row => `
    <tr>
      <td>${escHtml(row.period_label)}</td>
      <td style="text-align:center;">${row.order_count}</td>
      <td style="text-align:right;font-weight:600;">${peso(row.total_sales)}</td>
      <td style="text-align:right;color:var(--clr-text-muted);">${peso(row.avg_sale)}</td>
    </tr>`).join('');
}

// ── BEST SELLERS ──────────────────────────────────────────────
async function loadBestSellers() {
  const data = await apiGet('../api/sales.php?action=best_sellers&limit=8');
  const body = document.getElementById('best-sellers-body');

  if (!data.success || data.data.length === 0) {
    body.innerHTML = `<div class="empty-state"><div class="empty-icon">🎂</div>
      <p>No sales data yet. Complete orders to see best sellers.</p></div>`;
    return;
  }

  const maxQty = Math.max(...data.data.map(p => parseInt(p.total_qty_sold)));

  const rankClass = (i) => i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';

  body.innerHTML = data.data.map((p, i) => {
    const pct = Math.round((parseInt(p.total_qty_sold) / maxQty) * 100);
    return `
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.9rem;">
        <span class="rank-num ${rankClass(i)}">${i + 1}</span>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;">
            <span style="font-size:.875rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${escHtml(p.product_name)}
            </span>
            <span style="font-size:.8rem;color:var(--clr-text-muted);flex-shrink:0;margin-left:.5rem;">
              ${p.total_qty_sold} sold
            </span>
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <div class="bar-wrap"><div class="bar-fill" style="width:${pct}%"></div></div>
            <span style="font-size:.75rem;color:var(--clr-text-muted);flex-shrink:0;">${peso(p.total_revenue)}</span>
          </div>
          <div style="font-size:.72rem;color:var(--clr-text-muted);margin-top:.2rem;">${escHtml(p.category)}</div>
        </div>
      </div>`;
  }).join('');
}

// ── ALL SALES RECORDS TABLE ───────────────────────────────────
async function loadAllSales() {
  const tbody   = document.getElementById('all-sales-tbody');
  const rawData = await apiGet('../api/sales.php?action=all_records');

  if (!rawData.success || rawData.data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5">
      <div class="empty-state"><div class="empty-icon">💰</div>
      <p>No completed sales yet.</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = rawData.data.map(s => `
    <tr>
      <td class="text-muted text-sm">#${s.sale_id}</td>
      <td>Order #${s.order_id}</td>
      <td>${escHtml(s.customer_name)}</td>
      <td>${fmtDateTime(s.sale_date)}</td>
      <td style="text-align:right;font-weight:600;">${peso(s.total_amount)}</td>
    </tr>`).join('');
}

// ── HELPERS ───────────────────────────────────────────────────
function peso(n) {
  return '₱' + parseFloat(n || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2, maximumFractionDigits: 2
  });
}

function fmtDateTime(str) {
  if (!str) return '—';
  return new Date(str).toLocaleDateString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
</script>
</body>
</html>