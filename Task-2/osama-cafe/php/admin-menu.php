<?php
require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

$pdo = osama_cafe_db();
$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit']]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** A field label with a hover-only "ⓘ" tooltip explaining what it controls. */
function field_label(string $text, string $hint): string
{
    return '<label class="field-label">' . h($text)
        . ' <span class="hint-icon" tabindex="0">&#9432;<span class="hint-tooltip">' . h($hint) . '</span></span></label>';
}

$status = $_GET['status'] ?? null;

// --- Search + category filter + pagination over the item list ---
const ITEMS_PER_PAGE = 10;

$search = trim((string)($_GET['q'] ?? ''));
$categoryFilter = (int)($_GET['cat'] ?? 0);

$where = [];
$params = [];
if ($search !== '') {
    $where[] = 'menu_items.title LIKE :q';
    $params['q'] = '%' . $search . '%';
}
if ($categoryFilter > 0) {
    $where[] = 'menu_items.category_id = :cat';
    $params['cat'] = $categoryFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();

$pag = paginate_params($totalItems, ITEMS_PER_PAGE, 'ipage');

$listStmt = $pdo->prepare("
    SELECT menu_items.*, categories.name AS category_name
    FROM menu_items
    JOIN categories ON categories.id = menu_items.category_id
    $whereSql
    ORDER BY menu_items.sort_order, menu_items.id
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue('limit', $pag['perPage'], PDO::PARAM_INT);
$listStmt->bindValue('offset', $pag['offset'], PDO::PARAM_INT);
$listStmt->execute();
$items = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$filterBaseParams = [];
if ($search !== '') {
    $filterBaseParams['q'] = $search;
}
if ($categoryFilter > 0) {
    $filterBaseParams['cat'] = $categoryFilter;
}

// An AJAX request from admin.js only wants the part of the page that can
// change — not the surrounding <html>/<nav>/<script> — so it can swap it in
// without a full reload. See partials/menu-content.php.
if (is_ajax()) {
    require __DIR__ . '/partials/menu-content.php';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Menu — Osama Café</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

<?php render_admin_nav('menu'); ?>

<div class="admin-content" id="admin-content" data-page-path="<?= h($_SERVER['SCRIPT_NAME']) ?>">
<?php require __DIR__ . '/partials/menu-content.php'; ?>
</div>

<script src="../JS/admin.js?v=<?= asset_version('JS/admin.js') ?>"></script>
</body>
</html>
