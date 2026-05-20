<?php

require_once __DIR__ . '/../config/session.php';
$current_page = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">

  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <header class="topbar">
    <span class="topbar-title">Inventory</span>
    <div class="topbar-user">
      <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
  </header>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h2>Inventory</h2>
        <p>Track all ingredients and supplies. Items below their reorder level are flagged automatically.</p>
      </div>
      <button class="btn btn-primary" onclick="openAddModal()">+ Add Item</button>
    </div>

    <!-- LOW STOCK ALERT BANNER (shown only when items are low) -->
    <div id="low-stock-banner" class="alert alert-error" style="display:none;"></div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <input type="text" id="search-input" placeholder="Search items…" oninput="filterTable()">
      <select id="stock-filter" onchange="filterTable()">
        <option value="">All Items</option>
        <option value="low">Low Stock Only</option>
        <option value="ok">Sufficient Stock Only</option>
      </select>
    </div>

    <!-- INVENTORY TABLE -->
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Item Name</th>
              <th>Current Stock</th>
              <th>Unit</th>
              <th>Reorder Level</th>
              <th>Status</th>
              <th>Last Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="inventory-tbody">
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-icon">⏳</div>
                  <p>Loading inventory…</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- ══ ADD ITEM MODAL ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3>Add Inventory Item</h3>
      <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <div class="modal-body">
      <div id="add-error" class="alert alert-error" style="display:none;"></div>

      <div class="form-group">
        <label>Item Name *</label>
        <input type="text" id="add-name" placeholder="e.g. All-Purpose Flour">
      </div>
      <div class="form-group">
        <label>Current Quantity *</label>
        <input type="number" id="add-quantity" placeholder="0" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label>Unit *</label>
        <select id="add-unit">
          <option value="">Select unit…</option>
          <option value="kg">kg</option>
          <option value="g">g</option>
          <option value="liters">liters</option>
          <option value="ml">ml</option>
          <option value="pcs">pcs</option>
          <option value="boxes">boxes</option>
          <option value="packs">packs</option>
          <option value="cups">cups</option>
          <option value="tbsp">tbsp</option>
          <option value="tsp">tsp</option>
        </select>
      </div>
      <div class="form-group">
        <label>Reorder Level *</label>
        <input type="number" id="add-reorder" placeholder="e.g. 10" min="0" step="0.01">
        <p class="text-sm text-muted mt-1">The system will flag this item as low stock when quantity reaches or falls below this number.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
      <button class="btn btn-primary" onclick="submitAdd()">Save Item</button>
    </div>
  </div>
</div>

<!-- ══ EDIT ITEM MODAL ════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Item Details</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
    </div>
    <div class="modal-body">
      <div id="edit-error" class="alert alert-error" style="display:none;"></div>

      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label>Item Name *</label>
        <input type="text" id="edit-name">
      </div>
      <div class="form-group">
        <label>Unit *</label>
        <select id="edit-unit">
          <option value="kg">kg</option>
          <option value="g">g</option>
          <option value="liters">liters</option>
          <option value="ml">ml</option>
          <option value="pcs">pcs</option>
          <option value="boxes">boxes</option>
          <option value="packs">packs</option>
          <option value="cups">cups</option>
          <option value="tbsp">tbsp</option>
          <option value="tsp">tsp</option>
        </select>
      </div>
      <div class="form-group">
        <label>Reorder Level *</label>
        <input type="number" id="edit-reorder" min="0" step="0.01">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-edit')">Cancel</button>
      <button class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ══ RESTOCK MODAL ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-restock">
  <div class="modal">
    <div class="modal-header">
      <h3>Update Stock Level</h3>
      <button class="modal-close" onclick="closeModal('modal-restock')">✕</button>
    </div>
    <div class="modal-body">
      <div id="restock-error" class="alert alert-error" style="display:none;"></div>

      <input type="hidden" id="restock-id">
      <div class="form-group">
        <label>Item</label>
        <input type="text" id="restock-name" disabled style="opacity:.6;">
      </div>
      <div class="form-group">
        <label>Current Stock</label>
        <input type="text" id="restock-current" disabled style="opacity:.6;">
      </div>
      <div class="form-group">
        <label>New Quantity *</label>
        <input type="number" id="restock-quantity" min="0" step="0.01"
               placeholder="Enter the updated total quantity">
        <p class="text-sm text-muted mt-1">Enter the new total — not the amount added. For example if you had 5 kg and added 10 kg, enter 15.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-restock')">Cancel</button>
      <button class="btn btn-primary" onclick="submitRestock()">Update Stock</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let allItems = [];

document.addEventListener('DOMContentLoaded', loadInventory);

// ── LOAD ─────────────────────────────────────────────────────
async function loadInventory() {
  const data = await apiGet('../api/inventory.php?action=list');
  if (!data.success) return;

  allItems = data.data;
  renderTable(allItems);
  renderLowStockBanner(allItems);
}

// ── RENDER TABLE ─────────────────────────────────────────────
function renderTable(items) {
  const tbody = document.getElementById('inventory-tbody');

  if (items.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="8">
        <div class="empty-state">
          <div class="empty-icon">📦</div>
          <p>No inventory items found. Add your first ingredient to get started.</p>
        </div>
      </td></tr>`;
    return;
  }

  tbody.innerHTML = items.map(item => {
    const isLow   = parseInt(item.is_low_stock) === 1;
    const rowStyle = isLow ? 'background: var(--clr-danger-lt);' : '';
    const updated  = new Date(item.updated_at).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'short', day: 'numeric'
    });

    return `
      <tr style="${rowStyle}">
        <td class="text-muted text-sm">#${item.item_id}</td>
        <td><strong>${escHtml(item.name)}</strong></td>
        <td><strong>${parseFloat(item.quantity).toLocaleString('en-PH', {maximumFractionDigits:2})}</strong></td>
        <td class="text-muted">${escHtml(item.unit)}</td>
        <td class="text-muted">${parseFloat(item.reorder_level).toLocaleString('en-PH', {maximumFractionDigits:2})}</td>
        <td>
          ${isLow
            ? '<span class="badge badge-lowstock">⚠ Low Stock</span>'
            : '<span class="badge badge-safe">✓ OK</span>'
          }
        </td>
        <td class="text-muted text-sm">${updated}</td>
        <td>
          <div class="flex gap-1">
            <button class="btn btn-outline btn-sm" onclick="openRestockModal(${item.item_id})">Restock</button>
            <button class="btn btn-ghost btn-sm"   onclick="openEditModal(${item.item_id})">Edit</button>
            <button class="btn btn-danger btn-sm"  onclick="deleteItem(${item.item_id}, '${escHtml(item.name)}')">Delete</button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

// ── LOW STOCK BANNER ─────────────────────────────────────────
function renderLowStockBanner(items) {
  const banner   = document.getElementById('low-stock-banner');
  const lowItems = items.filter(i => parseInt(i.is_low_stock) === 1);

  if (lowItems.length === 0) {
    banner.style.display = 'none';
    return;
  }

  const names = lowItems.map(i => `<strong>${escHtml(i.name)}</strong>`).join(', ');
  banner.innerHTML     = `⚠ ${lowItems.length} item(s) are running low: ${names}. Please restock soon.`;
  banner.style.display = 'block';
}

// ── FILTER TABLE ─────────────────────────────────────────────
function filterTable() {
  const search  = document.getElementById('search-input').value.toLowerCase();
  const stockF  = document.getElementById('stock-filter').value;

  const filtered = allItems.filter(item => {
    const matchSearch = item.name.toLowerCase().includes(search);
    const matchStock  = !stockF
      || (stockF === 'low' && parseInt(item.is_low_stock) === 1)
      || (stockF === 'ok'  && parseInt(item.is_low_stock) === 0);
    return matchSearch && matchStock;
  });

  renderTable(filtered);
}

// ── ADD MODAL ────────────────────────────────────────────────
function openAddModal() {
  document.getElementById('add-name').value     = '';
  document.getElementById('add-quantity').value = '';
  document.getElementById('add-unit').value     = '';
  document.getElementById('add-reorder').value  = '';
  document.getElementById('add-error').style.display = 'none';
  openModal('modal-add');
}

async function submitAdd() {
  const errBox = document.getElementById('add-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/inventory.php', {
    action        : 'create',
    name          : document.getElementById('add-name').value,
    quantity      : document.getElementById('add-quantity').value,
    unit          : document.getElementById('add-unit').value,
    reorder_level : document.getElementById('add-reorder').value,
  });

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  closeModal('modal-add');
  showToast(data.message, 'success');
  loadInventory();
}

// ── EDIT MODAL ───────────────────────────────────────────────
function openEditModal(itemId) {
  const item = allItems.find(x => x.item_id == itemId);
  if (!item) return;

  document.getElementById('edit-id').value      = item.item_id;
  document.getElementById('edit-name').value    = item.name;
  document.getElementById('edit-unit').value    = item.unit;
  document.getElementById('edit-reorder').value = item.reorder_level;
  document.getElementById('edit-error').style.display = 'none';
  openModal('modal-edit');
}

async function submitEdit() {
  const errBox = document.getElementById('edit-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/inventory.php', {
    action        : 'update',
    item_id       : document.getElementById('edit-id').value,
    name          : document.getElementById('edit-name').value,
    unit          : document.getElementById('edit-unit').value,
    reorder_level : document.getElementById('edit-reorder').value,
  });

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  closeModal('modal-edit');
  showToast(data.message, 'success');
  loadInventory();
}

// ── RESTOCK MODAL ─────────────────────────────────────────────
function openRestockModal(itemId) {
  const item = allItems.find(x => x.item_id == itemId);
  if (!item) return;

  document.getElementById('restock-id').value       = item.item_id;
  document.getElementById('restock-name').value     = item.name;
  document.getElementById('restock-current').value  = `${parseFloat(item.quantity).toLocaleString('en-PH', {maximumFractionDigits:2})} ${item.unit}`;
  document.getElementById('restock-quantity').value = '';
  document.getElementById('restock-error').style.display = 'none';
  openModal('modal-restock');
}

async function submitRestock() {
  const errBox = document.getElementById('restock-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/inventory.php', {
    action   : 'restock',
    item_id  : document.getElementById('restock-id').value,
    quantity : document.getElementById('restock-quantity').value,
  });

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  closeModal('modal-restock');
  showToast(data.message, 'success');
  loadInventory();
}

// ── DELETE ───────────────────────────────────────────────────
async function deleteItem(itemId, itemName) {
  if (!confirmDelete(`"${itemName}"`)) return;

  const data = await apiPost('../api/inventory.php', {
    action  : 'delete',
    item_id : itemId,
  });

  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) loadInventory();
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
</script>
</body>
</html>