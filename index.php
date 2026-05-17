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

    <!-- Error message area (shown by JS if login fails) -->
    <div id="login-error" class="alert alert-error" style="display:none;"></div>

    <form id="login-form">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               placeholder="Enter username" required autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter password" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-full" id="login-btn">
        Login
      </button>
    </form>
  </div>

  <script>
    document.getElementById('login-form').addEventListener('submit', async function(e) {
      e.preventDefault();

      const btn   = document.getElementById('login-btn');
      const error = document.getElementById('login-error');
      btn.textContent = 'Logging in…';
      btn.disabled = true;
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
          error.textContent    = data.message || 'Invalid credentials.';
          error.style.display  = 'block';
          btn.textContent      = 'Login';
          btn.disabled         = false;
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