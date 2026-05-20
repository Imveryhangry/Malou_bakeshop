<?php
// ============================================================
//  pages/users.php
//  Admin-only page to manage system user accounts.
//  Accessible only when logged in.
// ============================================================
require_once __DIR__ . '/../config/session.php';
$current_page = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Accounts — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">

  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <header class="topbar">
    <span class="topbar-title">User Accounts</span>
    <div class="topbar-user">
      <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
  </header>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h2>User Accounts</h2>
        <p>Manage who has access to this system.</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('modal-add')">+ Add Account</button>
    </div>

    <!-- NOTICE BANNER -->
    <div class="alert alert-warning mb-2">
      ⚠ Each account has full access to the entire system.
    </div>

    <!-- USERS TABLE -->
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Date Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="users-tbody">
            <tr><td colspan="4">
              <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- ══ ADD ACCOUNT MODAL ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New Account</h3>
      <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <div class="modal-body">
      <div id="add-error" class="alert alert-error" style="display:none;"></div>

      <div class="form-group">
        <label>Username *</label>
        <input type="text" id="add-username" placeholder="e.g. malou_staff"
               autocomplete="off">
        <p class="text-sm text-muted mt-1">Minimum 3 characters. No spaces.</p>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" id="add-password" placeholder="Minimum 6 characters"
               autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Confirm Password *</label>
        <input type="password" id="add-password-confirm" placeholder="Repeat password"
               autocomplete="new-password">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
      <button class="btn btn-primary" onclick="submitAdd()">Create Account</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let allUsers = [];
const currentUserId = <?= (int)$_SESSION['user_id'] ?>;

document.addEventListener('DOMContentLoaded', loadUsers);

// ── LOAD USERS ────────────────────────────────────────────────
async function loadUsers() {
  const data = await apiGet('../api/register.php?action=list');
  if (!data.success) return;
  allUsers = data.data;
  renderTable(allUsers);
}

// ── RENDER TABLE ──────────────────────────────────────────────
function renderTable(users) {
  const tbody = document.getElementById('users-tbody');

  if (users.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4">
      <div class="empty-state"><div class="empty-icon">👤</div>
      <p>No accounts found.</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = users.map(u => {
    const isYou    = u.user_id == currentUserId;
    const created  = new Date(u.created_at).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'short', day: 'numeric'
    });

    return `
      <tr>
        <td class="text-muted text-sm">#${u.user_id}</td>
        <td>
          <strong>${escHtml(u.username)}</strong>
          ${isYou ? '<span class="badge badge-completed" style="margin-left:.5rem;">You</span>' : ''}
        </td>
        <td class="text-muted text-sm">${created}</td>
        <td>
          ${isYou
            ? '<span class="text-sm text-muted">Cannot delete own account</span>'
            : `<button class="btn btn-danger btn-sm"
                onclick="deleteUser(${u.user_id}, '${escHtml(u.username)}')">
                Delete
               </button>`
          }
        </td>
      </tr>`;
  }).join('');
}

// ── ADD ACCOUNT ───────────────────────────────────────────────
async function submitAdd() {
  const errBox = document.getElementById('add-error');
  errBox.style.display = 'none';

  const data = await apiPost('../api/register.php', {
    action          : 'create',
    username        : document.getElementById('add-username').value,
    password        : document.getElementById('add-password').value,
    password_confirm: document.getElementById('add-password-confirm').value,
  });

  if (!data.success) {
    errBox.textContent   = data.message;
    errBox.style.display = 'block';
    return;
  }

  // Clear fields
  document.getElementById('add-username').value         = '';
  document.getElementById('add-password').value         = '';
  document.getElementById('add-password-confirm').value = '';

  closeModal('modal-add');
  showToast(data.message, 'success');
  loadUsers();
}

// ── DELETE ACCOUNT ────────────────────────────────────────────
async function deleteUser(userId, username) {
  if (!confirmDelete(`the account "${username}"`)) return;

  const data = await apiPost('../api/register.php', {
    action  : 'delete',
    user_id : userId,
  });

  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) loadUsers();
}

// ── HELPER ────────────────────────────────────────────────────
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
</script>
</body>
</html>