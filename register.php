<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Malou Bakes Dvo</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

  <div class="login-card">
    <div class="login-brand">
      <h1>Malou Bakes <span>Dvo</span></h1>
      <p>Create an Account</p>
    </div>

    <div id="reg-error"   class="alert alert-error"   style="display:none;"></div>
    <div id="reg-success" class="alert alert-success" style="display:none;"></div>

    <form id="reg-form">
      <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" placeholder="e.g. malou_admin"
               autocomplete="off" required>
        <p class="text-sm text-muted mt-1">Minimum 3 characters.</p>
      </div>
      <div class="form-group">
        <label for="password">Password *</label>
        <input type="password" id="password"
               placeholder="Minimum 6 characters"
               autocomplete="new-password" required>
      </div>
      <div class="form-group">
        <label for="password-confirm">Confirm Password *</label>
        <input type="password" id="password-confirm"
               placeholder="Repeat password"
               autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full" id="reg-btn">
        Create Account
      </button>
    </form>

    <div style="text-align:center; margin-top:1rem;">
      <a href="index.php"
         style="font-size:.875rem; color:var(--clr-primary); font-weight:600;">
        ← Back to Login
      </a>
    </div>
  </div>

  <script>
    document.getElementById('reg-form').addEventListener('submit', async function(e) {
      e.preventDefault();

      const btn     = document.getElementById('reg-btn');
      const errBox  = document.getElementById('reg-error');
      const sucBox  = document.getElementById('reg-success');
      errBox.style.display = 'none';
      sucBox.style.display = 'none';
      btn.textContent = 'Creating…';
      btn.disabled    = true;

      const formData = new FormData();
      formData.append('action',           'create');
      formData.append('username',         document.getElementById('username').value);
      formData.append('password',         document.getElementById('password').value);
      formData.append('password_confirm', document.getElementById('password-confirm').value);

      try {
        const res  = await fetch('api/register.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          sucBox.textContent   = data.message + ' Redirecting to login…';
          sucBox.style.display = 'block';
          // Redirect to login after 2 seconds
          setTimeout(() => window.location.href = 'index.php', 2000);
        } else {
          errBox.textContent   = data.message;
          errBox.style.display = 'block';
          btn.textContent      = 'Create Account';
          btn.disabled         = false;
        }
      } catch (err) {
        errBox.textContent   = 'Server error. Please try again.';
        errBox.style.display = 'block';
        btn.textContent      = 'Create Account';
        btn.disabled         = false;
      }
    });
  </script>
</body>
</html>