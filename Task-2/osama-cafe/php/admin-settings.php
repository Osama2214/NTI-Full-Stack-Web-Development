<?php
require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

$pdo = osama_cafe_db();
$settings = get_settings($pdo);
$branches = $pdo->query('SELECT * FROM branches ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);

$editBranch = null;
if (isset($_GET['edit_branch'])) {
    $stmt = $pdo->prepare('SELECT * FROM branches WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit_branch']]);
    $editBranch = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** A field label with a hover-only "ⓘ" tooltip explaining what it controls. */
function field_label(string $text, string $hint): string
{
    return '<label class="field-label">' . h($text)
        . ' <span class="hint-icon" tabindex="0">&#9432;<span class="hint-tooltip">' . h($hint) . '</span></span></label>';
}

$status = $_GET['status'] ?? null;

if (is_ajax()) {
    require __DIR__ . '/partials/settings-content.php';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site Settings — Osama Café</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

<?php render_admin_nav('settings'); ?>

<div class="admin-content" id="admin-content" data-page-path="<?= h($_SERVER['SCRIPT_NAME']) ?>">
<?php require __DIR__ . '/partials/settings-content.php'; ?>
</div>

<script src="../JS/admin.js?v=<?= asset_version('JS/admin.js') ?>"></script>
</body>
</html>
