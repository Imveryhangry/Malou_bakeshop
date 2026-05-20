<?php

require_once __DIR__ . '/../config/session.php';
$current_page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">

  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <!-- TOP BAR -->
  <header class="topbar">
    <span class="topbar-title">Products</span>
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
        <h2>Product Catalog</h2>
        <p>Manage all bakeshop products and their pricing.</p>
      </div>
      <button class="btn btn-primary" onclick="openAddModal()">+ Add Product</button>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <input type="text" id="search-input" placeholder="Search products…" oninput="filterTable()">
      <select id="category-filter" onchange="filterTable()">
        <option value="">All Categories</option>
      </select>
      <select id="status-filter" onchange="filterTable()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <!-- PRODUCTS TABLE -->
    <div class="card">
      <div class="table-wrap">
        <table id="products-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="products-tbody">
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <div class="empty-icon">⏳</div>
                  <p>Loading products…</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- ══════════════════════════════════════════════════════════
     ADD PRODUCT MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New Product</h3>
      <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <div class="modal-body">
      <div id="add-error" class="alert alert-error" style="display:none;"></div>

      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" id="add-name" placeholder="e.g. Chocolate Fudge Cake">
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select id="add-category">
          <option value="">Select a category…</option>
        </select>
      </div>
      <div class="form-group">
        <label>Price (₱) *</label>
        <input type="number" id="add-price" placeholder="0.00" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea id="add-description" placeholder="Optional — short product description"></textarea>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="add-status">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
      <button class="btn btn-primary" onclick="submitAdd()">Save Product</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT PRODUCT MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Product</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
    </div>
    <div class="modal-body">
      <div id="edit-error" class="alert alert-error" style="display:none;"></div>

      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" id="edit-name">
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select id="edit-category">
          <option value="">Select a category…</option>
        </select>
      </div>
      <div class="form-group">
        <label>Price (₱) *</label>
        <input type="number" id="edit-price" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea id="edit-description"></textarea>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="edit-status">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-edit')">Cancel</button>
      <button class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── STATE ────────────────────────────────────────────────────
let allProducts   = [];
let allCategories = [];

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadCategories();
  loadProducts();
});

// ── LOAD CATEGORIES (for dropdowns + filter bar) ─────────────
async function loadCategories() {
  const data = await apiGet('../api/products.php?action=categories');
  if (!data.success) return;
  allCategories = data.data;

  // Populate filter bar
  const filterSel = document.getElementById('category-filter');
  allCategories.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.category_id;
    opt.textContent = c.name;
    filterSel.appendChild(opt);
  });

  // Populate add/edit modal dropdowns
  populateCategoryDropdown('add-category');
  populateCategoryDropdown('edit-category');
}

function populateCategoryDropdown(selectId) {
  const sel = document.getElementById(selectId);
  // Keep the first placeholder option, remove the rest
  while (sel.options.length > 1) sel.remove(1);
  allCategories.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.category_id;
    opt.textContent = c.name;
    sel.appendChild(opt);
  });
}

// ── LOAD PRODUCTS ────────────────────────────────────────────
async function loadProducts() {
  const data = await apiGet('../api/products.php?action=list');
  const tbody = document.getElementById('products-tbody');

  if (!data.success) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><p>Failed to load products.</p></div></td></tr>`;
    return;
  }

  allProducts = data.data;
  renderTable(allProducts);
}

// ── RENDER TABLE ─────────────────────────────────────────────
function renderTable(products) {
  const tbody = document.getElementById('products-tbody');

  if (products.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="6">
        <div class="empty-state">
          <div class="empty-icon">🎂</div>
          <p>No products found. Add your first product to get started.</p>
        </div>
      </td></tr>`;
    return;
  }

  tbody.innerHTML = products.map(p => `
    <tr>
      <td class="text-muted text-sm">#${p.product_id}</td>
      <td>
        <strong>${escHtml(p.name)}</strong>
        ${p.description ? `<div class="text-sm text-muted">${escHtml(p.description)}</div>` : ''}
      </td>
      <td>${escHtml(p.category_name)}</td>
      <td>₱${parseFloat(p.price).toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
      <td>
        <span class="badge ${p.status === 'active' ? 'badge-completed' : 'badge-cancelled'}">
          ${p.status === 'active' ? 'Active' : 'Inactive'}
        </span>
      </td>
      <td>
        <div class="flex gap-1">
          <button class="btn btn-ghost btn-sm" onclick="openEditModal(${p.product_id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.product_id}, '${escHtml(p.name)}')">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}

// ── FILTER TABLE (client-side, no extra DB call) ─────────────
function filterTable() {
  const search   = document.getElementById('search-input').value.toLowerCase();
  const catId    = document.getElementById('category-filter').value;
  const statusF  = document.getElementById('status-filter').value;

  const filtered = allProducts.filter(p => {
    const matchSearch = p.name.toLowerCase().includes(search)
                     || (p.description || '').toLowerCase().includes(search);
    const matchCat    = !catId    || String(p.category_id) === catId;
    const matchStatus = !statusF  || p.status === statusF;
    return matchSearch && matchCat && matchStatus;
  });

  renderTable(filtered);
}

// ── OPEN ADD MODAL ───────────────────────────────────────────
function openAddModal() {
  document.getElementById('add-name').value        = '';
  document.getElementById('add-category').value    = '';
  document.getElementById('add-price').value       = '';
  document.getElementById('add-description').value = '';
  document.getElementById('add-status').value      = 'active';
  document.getElementById('add-error').style.display = 'none';
  openModal('modal-add');
}

// ── SUBMIT ADD ───────────────────────────────────────────────
async function submitAdd() {
  const errBox = document.getElementById('add-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/products.php', {
    action      : 'create',
    name        : document.getElementById('add-name').value,
    category_id : document.getElementById('add-category').value,
    price       : document.getElementById('add-price').value,
    description : document.getElementById('add-description').value,
    status      : document.getElementById('add-status').value,
  });

  if (!data.success) {
    errBox.textContent    = data.message;
    errBox.style.display  = 'block';
    return;
  }

  closeModal('modal-add');
  showToast(data.message, 'success');
  loadProducts();
}

// ── OPEN EDIT MODAL ──────────────────────────────────────────
function openEditModal(productId) {
  const p = allProducts.find(x => x.product_id == productId);
  if (!p) return;

  document.getElementById('edit-id').value          = p.product_id;
  document.getElementById('edit-name').value        = p.name;
  document.getElementById('edit-price').value       = p.price;
  document.getElementById('edit-description').value = p.description || '';
  document.getElementById('edit-status').value      = p.status;
  document.getElementById('edit-error').style.display = 'none';

  // Set category dropdown to current value
  const catSel = document.getElementById('edit-category');
  catSel.value = p.category_id;

  openModal('modal-edit');
}

// ── SUBMIT EDIT ──────────────────────────────────────────────
async function submitEdit() {
  const errBox = document.getElementById('edit-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/products.php', {
    action      : 'update',
    product_id  : document.getElementById('edit-id').value,
    name        : document.getElementById('edit-name').value,
    category_id : document.getElementById('edit-category').value,
    price       : document.getElementById('edit-price').value,
    description : document.getElementById('edit-description').value,
    status      : document.getElementById('edit-status').value,
  });

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  closeModal('modal-edit');
  showToast(data.message, 'success');
  loadProducts();
}

// ── DELETE PRODUCT ───────────────────────────────────────────
async function deleteProduct(productId, productName) {
  if (!confirmDelete(`"${productName}"`)) return;

  const data = await apiPost('../api/products.php', {
    action     : 'delete',
    product_id : productId,
  });

  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) loadProducts();
}

// ── HELPER: Escape HTML to prevent XSS ──────────────────────
// XSS = Cross-Site Scripting. If someone types <script> in a
// product name, this prevents it from running as real code.
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
</script>
</body>
</html>