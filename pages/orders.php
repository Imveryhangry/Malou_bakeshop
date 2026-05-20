<?php

require_once __DIR__ . '/../config/session.php';
$current_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* Order-specific styles */
    .order-items-builder { border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm); overflow: hidden; }
    .order-item-row { display: grid; grid-template-columns: 1fr 80px 90px 32px; gap: .5rem; align-items: center; padding: .5rem .75rem; border-bottom: 1px solid var(--clr-border); background: var(--clr-bg); }
    .order-item-row:last-child { border-bottom: none; }
    .order-item-row select, .order-item-row input { padding: .4rem .6rem; border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm); font-size: .85rem; background: var(--clr-surface); color: var(--clr-text); }
    .order-item-row select:focus, .order-item-row input:focus { border-color: var(--clr-primary); outline: none; }
    .remove-item-btn { background: none; border: none; color: var(--clr-danger); font-size: 1.1rem; cursor: pointer; padding: .2rem; }
    .add-item-row { padding: .6rem .75rem; background: var(--clr-surface-alt); }
    .order-total-bar { display: flex; justify-content: flex-end; align-items: center; gap: .5rem; padding: .75rem 1rem; background: var(--clr-surface-alt); border-top: 1px solid var(--clr-border); font-weight: 600; }
    .customer-toggle { display: flex; gap: .5rem; margin-bottom: .75rem; }
    .toggle-btn { flex: 1; padding: .45rem; border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm); background: transparent; font-size: .85rem; cursor: pointer; color: var(--clr-text-muted); transition: all var(--dur) var(--ease); }
    .toggle-btn.active { background: var(--clr-primary); border-color: var(--clr-primary); color: #fff; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem 1.5rem; }
    .detail-label { font-size: .75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--clr-text-muted); margin-bottom: .15rem; }
    .detail-value { font-size: .9rem; color: var(--clr-text); margin-bottom: .75rem; }
  </style>
</head>
<body>

<div class="app-layout">

  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <header class="topbar">
    <span class="topbar-title">Orders</span>
    <div class="topbar-user">
      <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
  </header>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h2>Orders</h2>
        <p>Manage all customer orders. Status flows: Pending → In Progress → Completed.</p>
      </div>
      <button class="btn btn-primary" onclick="openAddModal()">+ New Order</button>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <input type="text" id="search-input" placeholder="Search customer or order #…" oninput="filterTable()">
      <select id="status-filter" onchange="filterTable()">
        <option value="">All Statuses</option>
        <option value="Pending">Pending</option>
        <option value="In Progress">In Progress</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
    </div>

    <!-- ORDERS TABLE -->
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Customer</th>
              <th>Contact</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Order Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="orders-tbody">
            <tr><td colspan="7">
              <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading orders…</p></div>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- ══ NEW ORDER MODAL ════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-add">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h3>New Order</h3>
      <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <div class="modal-body">
      <div id="add-error" class="alert alert-error" style="display:none;"></div>

      <!-- Customer section -->
      <h4 class="mb-1">Customer</h4>
      <div class="customer-toggle">
        <button class="toggle-btn active" id="btn-existing" onclick="setCustomerMode('existing')">Existing Customer</button>
        <button class="toggle-btn"        id="btn-new"      onclick="setCustomerMode('new')">New Customer</button>
      </div>

      <div id="section-existing">
        <div class="form-group">
          <label>Select Customer *</label>
          <select id="add-customer-id">
            <option value="">Choose a customer…</option>
          </select>
        </div>
      </div>

      <div id="section-new" style="display:none;">
        <div class="form-group">
          <label>Customer Name *</label>
          <input type="text" id="add-customer-name" placeholder="Full name">
        </div>
        <div class="form-group">
          <label>Contact Number *</label>
          <input type="text" id="add-contact" placeholder="09xx-xxx-xxxx">
        </div>
      </div>

      <hr class="divider">

      <!-- Order details -->
      <h4 class="mb-1">Order Details</h4>
      <div class="form-group">
        <label>Due Date *</label>
        <input type="date" id="add-due-date">
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea id="add-notes" placeholder="e.g. Birthday cake — write Happy Birthday Ana!"></textarea>
      </div>

      <hr class="divider">

      <!-- Product items -->
      <div class="flex items-center gap-2 mb-1">
        <h4>Products *</h4>
      </div>

      <div class="order-items-builder" id="items-builder">
        <!-- rows injected by JS -->
      </div>
      <div style="margin-top:.5rem;">
        <button class="btn btn-ghost btn-sm" onclick="addItemRow()">+ Add Product</button>
      </div>
      <div class="order-total-bar" id="add-total-bar">
        Total: <span id="add-total">₱0.00</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
      <button class="btn btn-primary" onclick="submitAdd()">Create Order</button>
    </div>
  </div>
</div>

<!-- ══ ORDER DETAIL MODAL ════════════════════════════════════ -->
<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3 id="detail-title">Order Details</h3>
      <button class="modal-close" onclick="closeModal('modal-detail')">✕</button>
    </div>
    <div class="modal-body" id="detail-body">
      <!-- Injected by JS -->
    </div>
    <div class="modal-footer" id="detail-footer">
      <!-- Injected by JS -->
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── STATE ─────────────────────────────────────────────────────
let allOrders      = [];
let allCustomers   = [];
let allProducts    = [];
let customerMode   = 'existing';
let itemRowCount   = 0;

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadOrders();
  loadFormData();
  // Set min due date to today
  document.getElementById('add-due-date').min = new Date().toISOString().split('T')[0];
});

// ── LOAD DATA ─────────────────────────────────────────────────
async function loadOrders() {
  const data = await apiGet('../api/orders.php?action=list');
  if (!data.success) return;
  allOrders = data.data;
  renderTable(allOrders);
}

async function loadFormData() {
  const [custData, prodData] = await Promise.all([
    apiGet('../api/orders.php?action=customers'),
    apiGet('../api/orders.php?action=products'),
  ]);
  if (custData.success) {
    allCustomers = custData.data;
    const sel = document.getElementById('add-customer-id');
    allCustomers.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.customer_id;
      opt.textContent = `${c.name} — ${c.contact_number}`;
      sel.appendChild(opt);
    });
  }
  if (prodData.success) {
    allProducts = prodData.data;
    addItemRow(); // Start with one empty row
  }
}

// ── RENDER TABLE ──────────────────────────────────────────────
function renderTable(orders) {
  const tbody = document.getElementById('orders-tbody');
  if (orders.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="7">
        <div class="empty-state">
          <div class="empty-icon">🛒</div>
          <p>No orders yet. Create your first order to get started.</p>
        </div>
      </td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(o => {
    const isOverdue = o.status !== 'Completed' && o.status !== 'Cancelled'
                   && new Date(o.due_date) < new Date();
    const dueBadge = isOverdue
      ? `<span style="color:var(--clr-danger);font-weight:600;">⚠ ${fmtDate(o.due_date)}</span>`
      : fmtDate(o.due_date);

    return `
      <tr>
        <td class="text-muted text-sm">#${o.order_id}</td>
        <td><strong>${escHtml(o.customer_name)}</strong></td>
        <td class="text-muted text-sm">${escHtml(o.contact_number)}</td>
        <td>${statusBadgeHtml(o.status)}</td>
        <td>${dueBadge}</td>
        <td>₱${parseFloat(o.order_total).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
        <td>
          <div class="flex gap-1">
            <button class="btn btn-outline btn-sm" onclick="viewDetail(${o.order_id})">View</button>
            ${o.status === 'Pending' || o.status === 'In Progress'
              ? `<button class="btn btn-primary btn-sm" onclick="advanceStatus(${o.order_id})">
                   ${o.status === 'Pending' ? 'Start' : 'Complete'}
                 </button>`
              : ''}
            ${o.status === 'Pending' || o.status === 'In Progress'
              ? `<button class="btn btn-ghost btn-sm" onclick="cancelOrder(${o.order_id})">Cancel</button>`
              : ''}
            ${o.status === 'Cancelled'
              ? `<button class="btn btn-danger btn-sm" onclick="deleteOrder(${o.order_id})">Delete</button>`
              : ''}
          </div>
        </td>
      </tr>`;
  }).join('');
}

// ── FILTER ────────────────────────────────────────────────────
function filterTable() {
  const search  = document.getElementById('search-input').value.toLowerCase();
  const statusF = document.getElementById('status-filter').value;

  const filtered = allOrders.filter(o => {
    const matchSearch  = o.customer_name.toLowerCase().includes(search)
                      || String(o.order_id).includes(search);
    const matchStatus  = !statusF || o.status === statusF;
    return matchSearch && matchStatus;
  });
  renderTable(filtered);
}

// ── CUSTOMER MODE TOGGLE ──────────────────────────────────────
function setCustomerMode(mode) {
  customerMode = mode;
  document.getElementById('section-existing').style.display = mode === 'existing' ? '' : 'none';
  document.getElementById('section-new').style.display      = mode === 'new'      ? '' : 'none';
  document.getElementById('btn-existing').classList.toggle('active', mode === 'existing');
  document.getElementById('btn-new').classList.toggle('active', mode === 'new');
}

// ── ITEM ROWS ─────────────────────────────────────────────────
function addItemRow() {
  itemRowCount++;
  const id      = itemRowCount;
  const builder = document.getElementById('items-builder');

  const row = document.createElement('div');
  row.className = 'order-item-row';
  row.id        = `item-row-${id}`;

  const opts = allProducts.map(p =>
    `<option value="${p.product_id}" data-price="${p.price}">${p.name} — ₱${parseFloat(p.price).toLocaleString('en-PH',{minimumFractionDigits:2})}</option>`
  ).join('');

  row.innerHTML = `
    <select onchange="recalcTotal()" id="item-prod-${id}">
      <option value="">Select product…</option>
      ${opts}
    </select>
    <input type="number" id="item-qty-${id}" value="1" min="1"
           oninput="recalcTotal()" style="text-align:center;">
    <span id="item-sub-${id}" style="font-size:.85rem;color:var(--clr-text-muted);text-align:right;">₱0.00</span>
    <button class="remove-item-btn" onclick="removeItemRow(${id})" title="Remove">✕</button>
  `;

  builder.appendChild(row);
  recalcTotal();
}

function removeItemRow(id) {
  const row = document.getElementById(`item-row-${id}`);
  if (row) row.remove();
  recalcTotal();
}

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('.order-item-row').forEach(row => {
    const sel = row.querySelector('select');
    const qty = row.querySelector('input[type="number"]');
    const sub = row.querySelector('[id^="item-sub-"]');
    if (!sel || !qty || !sub) return;

    const opt   = sel.options[sel.selectedIndex];
    const price = opt ? parseFloat(opt.dataset.price || 0) : 0;
    const q     = parseInt(qty.value) || 0;
    const s     = price * q;
    total      += s;
    sub.textContent = `₱${s.toLocaleString('en-PH',{minimumFractionDigits:2})}`;
  });
  document.getElementById('add-total').textContent =
    `₱${total.toLocaleString('en-PH',{minimumFractionDigits:2})}`;
}

// ── OPEN ADD MODAL ────────────────────────────────────────────
function openAddModal() {
  document.getElementById('add-error').style.display = 'none';
  document.getElementById('add-due-date').value      = '';
  document.getElementById('add-notes').value         = '';
  document.getElementById('items-builder').innerHTML = '';
  document.getElementById('add-customer-id').value   = '';
  document.getElementById('add-customer-name').value = '';
  document.getElementById('add-contact').value       = '';
  setCustomerMode('existing');
  itemRowCount = 0;
  addItemRow();
  openModal('modal-add');
}

// ── SUBMIT NEW ORDER ──────────────────────────────────────────
async function submitAdd() {
  const errBox = document.getElementById('add-error');
  errBox.style.display = 'none';

  // Collect items from the builder rows
  const rows  = document.querySelectorAll('.order-item-row');
  const items = [];
  let valid   = true;

  rows.forEach(row => {
    const sel = row.querySelector('select');
    const qty = row.querySelector('input[type="number"]');
    if (!sel || !qty) return;
    if (!sel.value) { valid = false; return; }
    items.push({ product_id: sel.value, quantity: qty.value });
  });

  if (!valid || items.length === 0) {
    errBox.textContent   = 'Please select a product for every row, or remove empty rows.';
    errBox.style.display = 'block';
    return;
  }

  const payload = {
    action        : 'create',
    customer_mode : customerMode,
    due_date      : document.getElementById('add-due-date').value,
    notes         : document.getElementById('add-notes').value,
    items         : JSON.stringify(items),
  };

  if (customerMode === 'existing') {
    payload.customer_id = document.getElementById('add-customer-id').value;
  } else {
    payload.customer_name  = document.getElementById('add-customer-name').value;
    payload.contact_number = document.getElementById('add-contact').value;
  }

  const data = await apiPost('../api/orders.php', payload);

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  closeModal('modal-add');
  showToast(data.message, 'success');
  loadOrders();
}

// ── VIEW ORDER DETAIL ─────────────────────────────────────────
async function viewDetail(orderId) {
  const data = await apiGet(`../api/orders.php?action=detail&id=${orderId}`);
  if (!data.success) { showToast('Could not load order.', 'error'); return; }

  const o   = data.data;
  const canAdvance  = o.status === 'Pending' || o.status === 'In Progress';
  const canCancel   = o.status === 'Pending' || o.status === 'In Progress';
  const canDelete   = o.status === 'Cancelled';

  document.getElementById('detail-title').textContent = `Order #${o.order_id}`;

  const itemRows = o.items.map(i => `
    <tr>
      <td>${escHtml(i.product_name)}</td>
      <td style="text-align:center;">${i.quantity}</td>
      <td style="text-align:right;">₱${parseFloat(i.unit_price).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
      <td style="text-align:right;font-weight:600;">₱${parseFloat(i.subtotal).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
    </tr>`).join('');

  document.getElementById('detail-body').innerHTML = `
    <div class="detail-grid">
      <div>
        <div class="detail-label">Customer</div>
        <div class="detail-value">${escHtml(o.customer_name)}</div>
      </div>
      <div>
        <div class="detail-label">Contact</div>
        <div class="detail-value">${escHtml(o.contact_number)}</div>
      </div>
      <div>
        <div class="detail-label">Status</div>
        <div class="detail-value">${statusBadgeHtml(o.status)}</div>
      </div>
      <div>
        <div class="detail-label">Due Date</div>
        <div class="detail-value">${fmtDate(o.due_date)}</div>
      </div>
      <div>
        <div class="detail-label">Created</div>
        <div class="detail-value">${fmtDate(o.created_at)}</div>
      </div>
      <div>
        <div class="detail-label">Last Updated</div>
        <div class="detail-value">${fmtDate(o.updated_at)}</div>
      </div>
    </div>
    ${o.notes ? `<div class="detail-label">Notes</div><div class="detail-value">${escHtml(o.notes)}</div>` : ''}
    <hr class="divider">
    <h4 class="mb-1">Items Ordered</h4>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Subtotal</th></tr></thead>
        <tbody>${itemRows}</tbody>
        <tfoot>
          <tr style="background:var(--clr-surface-alt);">
            <td colspan="3" style="text-align:right;font-weight:600;padding:.75rem 1rem;">Order Total</td>
            <td style="text-align:right;font-weight:600;padding:.75rem 1rem;">
              ₱${parseFloat(o.order_total).toLocaleString('en-PH',{minimumFractionDigits:2})}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>`;

  document.getElementById('detail-footer').innerHTML = `
    <button class="btn btn-ghost" onclick="closeModal('modal-detail')">Close</button>
    ${canCancel  ? `<button class="btn btn-ghost"   onclick="cancelOrder(${o.order_id},true)">Cancel Order</button>` : ''}
    ${canDelete  ? `<button class="btn btn-danger"  onclick="deleteOrder(${o.order_id},true)">Delete</button>` : ''}
    ${canAdvance ? `<button class="btn btn-primary" onclick="advanceStatus(${o.order_id},true)">
      ${o.status === 'Pending' ? 'Mark In Progress' : 'Mark Completed'}
    </button>` : ''}`;

  openModal('modal-detail');
}

// ── ADVANCE STATUS ────────────────────────────────────────────
async function advanceStatus(orderId, fromDetail = false) {
  const data = await apiPost('../api/orders.php', {
    action   : 'update_status',
    order_id : orderId,
  });
  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) {
    if (fromDetail) closeModal('modal-detail');
    loadOrders();
  }
}

// ── CANCEL ORDER ──────────────────────────────────────────────
async function cancelOrder(orderId, fromDetail = false) {
  if (!confirm(`Cancel order #${orderId}? This cannot be undone.`)) return;
  const data = await apiPost('../api/orders.php', { action: 'cancel', order_id: orderId });
  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) {
    if (fromDetail) closeModal('modal-detail');
    loadOrders();
  }
}

// ── DELETE ORDER ──────────────────────────────────────────────
async function deleteOrder(orderId, fromDetail = false) {
  if (!confirm(`Permanently delete order #${orderId}?`)) return;
  const data = await apiPost('../api/orders.php', { action: 'delete', order_id: orderId });
  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) {
    if (fromDetail) closeModal('modal-detail');
    loadOrders();
  }
}

// ── HELPERS ───────────────────────────────────────────────────
function statusBadgeHtml(status) {
  const map = {
    'Pending'     : 'badge-pending',
    'In Progress' : 'badge-progress',
    'Completed'   : 'badge-completed',
    'Cancelled'   : 'badge-cancelled',
  };
  return `<span class="badge ${map[status] || 'badge-cancelled'}">${status}</span>`;
}

function fmtDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
</script>
</body>
</html>