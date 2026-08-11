<?php
require_once __DIR__ . '/admin-common.php';
require_once __DIR__ . '/mailer.php';

admin_session_start();
require_admin_auth();

$pdo = osama_cafe_db();

$totalMessages = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$weekMessages = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE created_at >= datetime('now', '-7 days')")->fetchColumn();
$totalSubscribers = (int)$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
$weekSubscribers = (int)$pdo->query("SELECT COUNT(*) FROM subscribers WHERE created_at >= datetime('now', '-7 days')")->fetchColumn();
$totalItems = (int)$pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
$totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalBranches = (int)$pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn();

$recentMessages = $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$recentSubscribers = $pdo->query('SELECT * FROM subscribers ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

$mailOn = mail_is_configured();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Overview — Osama Café Admin</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

<?php render_admin_nav('overview'); ?>

<div class="admin-content">

  <div class="admin-header">
    <div>
      <h2>Overview</h2>
      <p>A quick snapshot of what's happening at Osama Café.</p>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-value"><?= $totalMessages ?></div>
      <div class="stat-label">Messages</div>
      <div class="stat-sub"><?= $weekMessages ?> in the last 7 days</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $totalSubscribers ?></div>
      <div class="stat-label">Subscribers</div>
      <div class="stat-sub"><?= $weekSubscribers ?> in the last 7 days</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $totalItems ?></div>
      <div class="stat-label">Menu Items</div>
      <div class="stat-sub"><?= $totalCategories ?> categor<?= $totalCategories === 1 ? 'y' : 'ies' ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $totalBranches ?></div>
      <div class="stat-label">Branch<?= $totalBranches === 1 ? '' : 'es' ?></div>
      <div class="stat-sub">location<?= $totalBranches === 1 ? '' : 's' ?> live on the site</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="font-size:1.3rem;"><?= $mailOn ? 'On' : 'Off' ?></div>
      <div class="stat-label">Auto Email</div>
      <div class="stat-sub"><?= $mailOn ? 'SMTP configured' : 'set up in config.php' ?></div>
    </div>
  </div>

  <div class="quick-links">
    <a href="admin-messages.php" class="btn btn-secondary">Messages &amp; Subscribers</a>
    <a href="admin-menu.php" class="btn btn-secondary">Manage Menu</a>
    <a href="admin-settings.php" class="btn btn-secondary">Site Settings</a>
  </div>

  <div class="dash-columns">
    <div class="dash-panel">
      <h3>Recent Messages <a href="admin-messages.php">View all &rarr;</a></h3>
      <?php if (!$recentMessages): ?>
        <p class="empty-note">No messages yet.</p>
      <?php else: ?>
        <ul class="dash-list">
          <?php foreach ($recentMessages as $m): ?>
            <li>
              <div class="dash-list-title"><?= h($m['name']) ?> &mdash; <?= h($m['email']) ?></div>
              <div class="dash-list-meta"><?= h(mb_strimwidth($m['message'], 0, 90, '…')) ?></div>
              <div class="dash-list-meta"><?= h($m['created_at']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="dash-panel">
      <h3>Recent Subscribers <a href="admin-messages.php">View all &rarr;</a></h3>
      <?php if (!$recentSubscribers): ?>
        <p class="empty-note">No subscribers yet.</p>
      <?php else: ?>
        <ul class="dash-list">
          <?php foreach ($recentSubscribers as $s): ?>
            <li>
              <div class="dash-list-title"><?= h($s['email']) ?></div>
              <div class="dash-list-meta"><?= h($s['created_at']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

</div>

<script src="../JS/admin.js?v=<?= asset_version('JS/admin.js') ?>"></script>
</body>
</html>
