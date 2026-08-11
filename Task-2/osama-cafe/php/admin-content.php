<?php
require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

$pdo = osama_cafe_db();

$faqs = $pdo->query('SELECT * FROM faqs ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$galleryItems = $pdo->query('SELECT * FROM gallery_items ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);

$editFaq = null;
if (isset($_GET['edit_faq'])) {
    $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit_faq']]);
    $editFaq = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$editTestimonial = null;
if (isset($_GET['edit_testimonial'])) {
    $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit_testimonial']]);
    $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$editGallery = null;
if (isset($_GET['edit_gallery'])) {
    $stmt = $pdo->prepare('SELECT * FROM gallery_items WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit_gallery']]);
    $editGallery = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** A field label with a hover-only "ⓘ" tooltip explaining what it controls. */
function field_label(string $text, string $hint): string
{
    return '<label class="field-label">' . h($text)
        . ' <span class="hint-icon" tabindex="0">&#9432;<span class="hint-tooltip">' . h($hint) . '</span></span></label>';
}

/** Renders a rating (0–5, half-steps) as Font Awesome stars — full, half, then outline for the rest. */
function render_star_rating(float $rating): string
{
    $rating = max(0, min(5, $rating));
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('<i class="fa-solid fa-star"></i>', $full)
        . ($half ? '<i class="fa-solid fa-star-half-stroke"></i>' : '')
        . str_repeat('<i class="fa-regular fa-star"></i>', $empty);
}

$status = $_GET['status'] ?? null;

if (is_ajax()) {
    require __DIR__ . '/partials/content-content.php';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site Content — Osama Café</title>
<link rel="stylesheet" href="../CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
<link rel="stylesheet" href="../CSS/admin.css?v=<?= asset_version('CSS/admin.css') ?>">
</head>
<body class="admin-body">

<?php render_admin_nav('content'); ?>

<div class="admin-content" id="admin-content" data-page-path="<?= h($_SERVER['SCRIPT_NAME']) ?>">
<?php require __DIR__ . '/partials/content-content.php'; ?>
</div>

<script src="../JS/admin.js?v=<?= asset_version('JS/admin.js') ?>"></script>
</body>
</html>
