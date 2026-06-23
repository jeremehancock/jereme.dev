<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo (defined('BLUDIT_PRO') ? $site->title() : 'BLUDIT') ?> - Login</title>
  <meta charset="<?php echo CHARSET ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="robots" content="noindex,nofollow">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo HTML_PATH_ADMIN_THEME . 'img/favicon.png?version=' . BLUDIT_VERSION ?>">

  <!-- CSS -->
  <?php
  echo Theme::cssBootstrap();
  echo Theme::css(array(
    'bludit.css',
    'bludit.bootstrap.css'
  ), DOMAIN_ADMIN_THEME_CSS);
  ?>

  <style>
    :root {
      --jd-bg: #0a0c10;
      --jd-bg-elev: #14181f;
      --jd-bg-soft: #1a1e26;
      --jd-border: #232831;
      --jd-border-strong: #353a44;
      --jd-text: #ebedf2;
      --jd-text-soft: #b1b6c0;
      --jd-text-mute: #828893;
      --jd-accent: #34d399;
      --jd-accent-hover: #6ee7b7;
      --jd-accent-ink: #0a0c10;
      --jd-danger: #ef4444;
      --jd-success: #34d399;
    }

    body.login {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background:
        radial-gradient(ellipse 80% 50% at 50% 0%, rgba(52, 211, 153, 0.10) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 50% 100%, rgba(52, 211, 153, 0.05) 0%, transparent 60%),
        var(--jd-bg);
      color: var(--jd-text);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      padding: 20px;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
    }

    .login-card {
      background: var(--jd-bg-elev);
      border: 1px solid var(--jd-border);
      border-radius: 16px;
      box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(52, 211, 153, 0.04);
      padding: 40px;
      position: relative;
      overflow: hidden;
      animation: fadeInUp 0.5s ease-out;
    }

    .login-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--jd-accent) 0%, rgba(52, 211, 153, 0.4) 100%);
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .login-logo .logo-icon {
      width: 70px;
      height: 70px;
      background: var(--jd-accent);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      box-shadow: 0 8px 24px rgba(52, 211, 153, 0.35);
    }

    .login-logo .logo-icon img {
      width: 36px;
      height: 36px;
      filter: brightness(0);
    }

    .login-logo .logo-icon.custom-logo {
      background: transparent;
      box-shadow: none;
      width: auto;
      height: auto;
      max-width: 150px;
      max-height: 80px;
    }

    .login-logo .logo-icon.custom-logo img {
      width: auto;
      height: auto;
      max-width: 150px;
      max-height: 80px;
      filter: brightness(0) invert(1);
      border-radius: 12px;
    }

    .login-logo h1 {
      font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--jd-text);
      letter-spacing: -0.025em;
      margin: 0;
      display: none;
    }

    .login-logo p {
      color: var(--jd-text-mute);
      font-size: 0.9rem;
      margin-top: 5px;
    }

    .login-card .form-control {
      border: 1px solid var(--jd-border);
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      background-color: var(--jd-bg-soft);
      color: var(--jd-text);
    }

    .login-card .form-control:focus {
      border-color: var(--jd-accent);
      box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18);
      background-color: var(--jd-bg);
      color: var(--jd-text);
    }

    .login-card .form-control::placeholder {
      color: var(--jd-text-mute);
    }

    .login-card .form-group {
      margin-bottom: 20px;
    }

    .login-card .form-group label {
      font-weight: 600;
      color: var(--jd-text-soft);
      margin-bottom: 10px;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .login-card .btn-login {
      background: var(--jd-accent);
      border: none;
      border-radius: 10px;
      padding: 12px 18px;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--jd-accent-ink);
      width: 100%;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(52, 211, 153, 0.3);
    }

    .login-card .btn-login:hover {
      background: var(--jd-accent-hover);
      color: var(--jd-accent-ink);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(52, 211, 153, 0.4);
    }

    .login-card .btn-login:active {
      transform: translateY(0);
    }

    .login-card .form-check {
      margin-bottom: 25px;
    }

    .login-card .form-check-input {
      width: 18px;
      height: 18px;
      margin-top: 0;
      border: 1px solid var(--jd-border-strong);
      border-radius: 4px;
      background-color: var(--jd-bg-soft);
    }

    .login-card .form-check-input:checked {
      background-color: var(--jd-accent);
      border-color: var(--jd-accent);
    }

    .login-card .form-check-label {
      color: var(--jd-text-soft);
      font-size: 0.9rem;
      padding-left: 8px;
    }

    .login-footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid var(--jd-border);
    }

    .login-footer p {
      color: var(--jd-text-mute);
      font-size: 0.85rem;
      margin: 0;
    }

    .login-footer a {
      color: var(--jd-accent);
      text-decoration: none;
    }

    .login-footer a:hover {
      color: var(--jd-accent-hover);
    }

    /* Alert styles for login page */
    .login-alert {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1050;
      min-width: 300px;
      max-width: 90%;
      border-radius: 10px;
      padding: 12px 20px;
      font-weight: 600;
      font-size: 0.9rem;
      animation: slideDown 0.4s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
      }
    }

    .login-alert.alert-danger {
      background: var(--jd-danger);
      color: #ffffff;
      border: none;
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    }

    .login-alert.alert-success {
      background: var(--jd-success);
      color: var(--jd-accent-ink);
      border: none;
      box-shadow: 0 4px 15px rgba(52, 211, 153, 0.35);
    }

    /* Input icons */
    .input-icon-wrapper {
      position: relative;
    }

    .input-icon-wrapper .form-control {
      padding-left: 40px;
    }

    .input-icon-wrapper .input-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--jd-text-mute);
      pointer-events: none;
    }

    .input-icon-wrapper .form-control:focus + .input-icon,
    .input-icon-wrapper .form-control:not(:placeholder-shown) + .input-icon {
      color: var(--jd-accent);
    }
  </style>

  <!-- Javascript -->
  <?php
  echo Theme::jquery();
  echo Theme::jsBootstrap();
  ?>

  <!-- Plugins -->
  <?php Theme::plugins('loginHead') ?>
</head>

<body class="login">

  <!-- Plugins -->
  <?php Theme::plugins('loginBodyBegin') ?>

  <!-- Alert -->
  <?php if (Alert::defined()): ?>
  <div id="login-alert" class="login-alert alert <?php echo (Alert::status() == ALERT_STATUS_FAIL) ? 'alert-danger' : 'alert-success' ?>">
    <?php echo Alert::get() ?>
  </div>
  <script>
    setTimeout(function() {
      document.getElementById('login-alert').style.display = 'none';
    }, <?php echo ALERT_DISAPPEAR_IN * 1000 ?>);
  </script>
  <?php endif; ?>

  <div class="login-container">
    <div class="login-card">
      <?php
      if (Sanitize::pathFile(PATH_ADMIN_VIEWS, $layout['view'] . '.php')) {
        include(PATH_ADMIN_VIEWS . $layout['view'] . '.php');
      }
      ?>
    </div>
  </div>

  <!-- Plugins -->
  <?php Theme::plugins('loginBodyEnd') ?>

</body>

</html>
