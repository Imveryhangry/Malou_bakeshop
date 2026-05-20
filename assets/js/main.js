
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  // Remove after 3 seconds (matches CSS animation)
  setTimeout(() => toast.remove(), 3100);
}

// ── MODAL HELPERS ─────────────────────────────────────────────
// Usage: openModal('my-modal')  |  closeModal('my-modal')

function openModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) overlay.classList.add('open');
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) overlay.classList.remove('open');
}

// Close modal when clicking the dark overlay background
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open')
            .forEach(m => m.classList.remove('open'));
  }
});



async function apiPost(url, bodyObj) {
  const form = new FormData();
  for (const [key, val] of Object.entries(bodyObj)) {
    form.append(key, val ?? '');
  }
  const res  = await fetch(url, { method: 'POST', body: form });
  return res.json();
}

async function apiGet(url) {
  const res = await fetch(url);
  return res.json();
}

// ── FORMAT HELPERS ────────────────────────────────────────────

// Format number as Philippine Peso
function formatPeso(amount) {
  return '₱' + parseFloat(amount).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

// Format a date string to readable form (e.g. "May 8, 2026")
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Return a status badge HTML string
function statusBadge(status) {
  const map = {
    'Pending'     : 'badge-pending',
    'In Progress' : 'badge-progress',
    'Completed'   : 'badge-completed',
    'Cancelled'   : 'badge-cancelled',
  };
  const cls = map[status] || 'badge-cancelled';
  return `<span class="badge ${cls}">${status}</span>`;
}

// Return a stock badge HTML string
function stockBadge(qty, reorder) {
  if (parseFloat(qty) <= parseFloat(reorder)) {
    return `<span class="badge badge-lowstock">⚠ Low Stock</span>`;
  }
  return `<span class="badge badge-safe">✓ OK</span>`;
}

function confirmDelete(itemName = 'this item') {
  return confirm(`Are you sure you want to delete ${itemName}? This cannot be undone.`);
}