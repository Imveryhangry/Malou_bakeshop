<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Malou Bakes Dvo — Login</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

  <div class="login-card">
    <div class="login-brand">
      <h1>Malou Bakes <span>Dvo</span></h1>
      <p>Sales & Inventory System</p>
    </div>

    <!-- Error message area -->
    <div id="login-error" class="alert alert-error" style="display:none;"></div>

    <!-- First-time setup banner (shown when no users exist) -->
    <div id="no-users-banner" class="alert alert-warning" style="display:none;">
      No accounts found. Please register the first admin account.
    </div>

    <form id="login-form">
      <div class="form-group">
        <label for="username">Username</label>
        <!-- value prefilled for convenience -->
        <input type="text" id="username" name="username"
               value="admin" placeholder="Enter username"
               required autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <!-- value prefilled for convenience -->
        <input type="password" id="password" name="password"
               value="admin123" placeholder="Enter password"
               required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-full" id="login-btn">
        Login
      </button>
    </form>

    <!-- Register link — always visible -->
    <div style="text-align:center; margin-top:1rem;">
      <span class="text-sm text-muted">Need an account? </span>
      <a href="register.php"
         style="font-size:.875rem; color:var(--clr-primary); font-weight:600;">
        Register here
      </a>
    </div>
  </div>

  <script>
    // Check if any users exist — show banner if none
    fetch('api/auth.php?action=check_users')
      .then(r => r.json())
      .then(data => {
        if (data.no_users) {
          document.getElementById('no-users-banner').style.display = 'block';
        }
      })
      .catch(() => {});

    document.getElementById('login-form').addEventListener('submit', async function(e) {
      e.preventDefault();

      const btn   = document.getElementById('login-btn');
      const error = document.getElementById('login-error');
      btn.textContent = 'Logging in…';
      btn.disabled    = true;
      error.style.display = 'none';

      const formData = new FormData();
      formData.append('username', document.getElementById('username').value);
      formData.append('password', document.getElementById('password').value);

      try {
        const res  = await fetch('api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          window.location.href = 'dashboard.php';
        } else {
          error.textContent   = data.message || 'Invalid credentials.';
          error.style.display = 'block';
          btn.textContent     = 'Login';
          btn.disabled        = false;
        }
      } catch (err) {
        error.textContent   = 'Server error. Please try again.';
        error.style.display = 'block';
        btn.textContent     = 'Login';
        btn.disabled        = false;
      }
    });
  </script>
</body>
</html>