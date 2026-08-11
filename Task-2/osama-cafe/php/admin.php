<?php
require_once __DIR__ . '/admin-common.php';

admin_session_start();

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authed']);
    header('Location: admin.php');
    exit;
}

$lockoutRemaining = login_lockout_remaining();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    require_csrf();

    if ($lockoutRemaining > 0) {
        $loginError = 'Too many wrong attempts. Try again in ' . (int)ceil($lockoutRemaining / 60) . ' minute(s).';
    } elseif (password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
        clear_login_failures();
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['admin_authed'] = true;
        header('Location: admin-overview.php');
        exit;
    } else {
        record_login_failure();
        $lockoutRemaining = login_lockout_remaining();
        $loginError = $lockoutRemaining > 0
            ? 'Too many wrong attempts. Try again in ' . (int)ceil($lockoutRemaining / 60) . ' minute(s).'
            : 'Wrong password.';
    }
}

$authed = !empty($_SESSION['admin_authed']);

if ($authed) {
    header('Location: admin-overview.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Osama Café</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

  <div class="admin-content">
    <div class="admin-login">
      <img src="../images/logo-1.png" alt="Osama Café" class="admin-login-logo">
      <h2>Admin Login</h2>
      <?php if (!empty($loginError)): ?><p class="admin-error"><?= h($loginError) ?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="password" name="password" placeholder="Admin password" autofocus required <?= $lockoutRemaining > 0 ? 'disabled' : '' ?>>
        <button type="submit" class="btn btn-primary" style="width:100%;" <?= $lockoutRemaining > 0 ? 'disabled' : '' ?>>Log In</button>
      </form>
    </div>
  </div>

</body>
</html>
