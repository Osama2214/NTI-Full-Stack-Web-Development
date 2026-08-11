<?php
require_once __DIR__ . '/admin-common.php';
require_once __DIR__ . '/mailer.php';

admin_session_start();
require_admin_auth();

$pdo = osama_cafe_db();

const PER_PAGE = 10;

// --- Messages: search + pagination ---
$mq = trim((string)($_GET['q'] ?? ''));
if ($mq !== '') {
    $totalMessages = (int)(function () use ($pdo, $mq) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE name LIKE :q OR email LIKE :q OR message LIKE :q');
        $stmt->execute(['q' => '%' . $mq . '%']);
        return $stmt->fetchColumn();
    })();
} else {
    $totalMessages = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
}
$mPag = paginate_params($totalMessages, PER_PAGE, 'mpage');
if ($mq !== '') {
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE name LIKE :q OR email LIKE :q OR message LIKE :q ORDER BY id DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue('q', '%' . $mq . '%');
} else {
    $stmt = $pdo->prepare('SELECT * FROM messages ORDER BY id DESC LIMIT :limit OFFSET :offset');
}
$stmt->bindValue('limit', $mPag['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $mPag['offset'], PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Subscribers: search + pagination ---
$sq = trim((string)($_GET['sq'] ?? ''));
if ($sq !== '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscribers WHERE email LIKE :q');
    $stmt->execute(['q' => '%' . $sq . '%']);
    $totalSubscribers = (int)$stmt->fetchColumn();
} else {
    $totalSubscribers = (int)$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
}
$sPag = paginate_params($totalSubscribers, PER_PAGE, 'spage');
if ($sq !== '') {
    $stmt = $pdo->prepare('SELECT * FROM subscribers WHERE email LIKE :q ORDER BY id DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue('q', '%' . $sq . '%');
} else {
    $stmt = $pdo->prepare('SELECT * FROM subscribers ORDER BY id DESC LIMIT :limit OFFSET :offset');
}
$stmt->bindValue('limit', $sPag['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $sPag['offset'], PDO::PARAM_INT);
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For the broadcast / "email all" actions, always target the full unfiltered subscriber list, not the current search/page.
$allSubscriberEmails = $pdo->query('SELECT email FROM subscribers ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
$allSubscriberCount = count($allSubscriberEmails);

if (is_ajax()) {
    require __DIR__ . '/partials/messages-content.php';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages &amp; Subscribers — Osama Café Admin</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

<?php render_admin_nav('messages'); ?>

<div class="admin-content" id="admin-content" data-page-path="<?= h($_SERVER['SCRIPT_NAME']) ?>">
<?php require __DIR__ . '/partials/messages-content.php'; ?>
</div>

<script src="../JS/admin.js?v=<?= asset_version('JS/admin.js') ?>"></script>
</body>
</html>
