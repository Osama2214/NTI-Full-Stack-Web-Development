<?php
/**
 * POST /php/content_admin.php — admin only.
 * Handles every FAQ / testimonial / gallery-photo editing action from
 * admin-content.php and redirects back with a status message (or responds
 * with JSON for an AJAX request — see admin_action_back()).
 */

require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-content.php');
    exit;
}

require_csrf();

$pdo = osama_cafe_db();
$action = $_POST['action'] ?? '';

function back(string $status): void
{
    admin_action_back('admin-content.php', CONTENT_STATUS_MESSAGES, $status);
}

const ALLOWED_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5MB

/** Saves an uploaded image into images/gallery/ and returns its filename, or null if no file was sent. */
function handle_gallery_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        back('upload_error');
    }
    if ($file['size'] > MAX_IMAGE_BYTES) {
        back('image_too_large');
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!isset(ALLOWED_IMAGE_TYPES[$mime])) {
        back('invalid_image_type');
    }
    $ext = ALLOWED_IMAGE_TYPES[$mime];
    $filename = 'photo-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destDir = __DIR__ . '/../images/gallery';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
        back('upload_error');
    }
    return 'gallery/' . $filename;
}

/** Deletes a previously-uploaded gallery image, but only if it lives under images/gallery/ (never touches the original site photos). */
function delete_gallery_image(?string $image): void
{
    if ($image && str_starts_with($image, 'gallery/')) {
        $path = __DIR__ . '/../images/' . $image;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

switch ($action) {

    // --- FAQ -------------------------------------------------------------

    case 'add_faq':
    case 'update_faq': {
        $id = (int)($_POST['id'] ?? 0);
        $question = trim((string)($_POST['question'] ?? ''));
        $answer = trim((string)($_POST['answer'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($question === '' || $answer === '') {
            back('invalid_faq');
        }

        if ($action === 'add_faq') {
            $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM faqs')->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order) VALUES (:question, :answer, :sort_order)');
            $stmt->execute(['question' => $question, 'answer' => $answer, 'sort_order' => $sortOrder ?: $maxOrder + 1]);
            back('faq_added');
        }

        if ($id <= 0) {
            back('invalid_faq');
        }
        $stmt = $pdo->prepare('UPDATE faqs SET question = :question, answer = :answer, sort_order = :sort_order WHERE id = :id');
        $stmt->execute(['question' => $question, 'answer' => $answer, 'sort_order' => $sortOrder, 'id' => $id]);
        back('faq_updated');
    }

    case 'delete_faq': {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM faqs WHERE id = :id')->execute(['id' => $id]);
        back('faq_deleted');
    }

    // --- Testimonials ------------------------------------------------------

    case 'add_testimonial':
    case 'update_testimonial': {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['author_name'] ?? ''));
        $role = trim((string)($_POST['author_role'] ?? ''));
        $quote = trim((string)($_POST['quote'] ?? ''));
        $rating = (float)($_POST['rating'] ?? 5);
        $rating = max(0.5, min(5, round($rating * 2) / 2)); // clamp to 0.5–5 in half-star steps
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($name === '' || $role === '' || $quote === '') {
            back('invalid_testimonial');
        }

        if ($action === 'add_testimonial') {
            $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM testimonials')->fetchColumn();
            $stmt = $pdo->prepare('
                INSERT INTO testimonials (author_name, author_role, quote, rating, sort_order)
                VALUES (:author_name, :author_role, :quote, :rating, :sort_order)
            ');
            $stmt->execute([
                'author_name' => $name, 'author_role' => $role, 'quote' => $quote,
                'rating' => $rating, 'sort_order' => $sortOrder ?: $maxOrder + 1,
            ]);
            back('testimonial_added');
        }

        if ($id <= 0) {
            back('invalid_testimonial');
        }
        $stmt = $pdo->prepare('
            UPDATE testimonials SET author_name = :author_name, author_role = :author_role,
                quote = :quote, rating = :rating, sort_order = :sort_order
            WHERE id = :id
        ');
        $stmt->execute([
            'author_name' => $name, 'author_role' => $role, 'quote' => $quote,
            'rating' => $rating, 'sort_order' => $sortOrder, 'id' => $id,
        ]);
        back('testimonial_updated');
    }

    case 'delete_testimonial': {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM testimonials WHERE id = :id')->execute(['id' => $id]);
        back('testimonial_deleted');
    }

    // --- Gallery -----------------------------------------------------------

    case 'add_gallery':
    case 'update_gallery': {
        $id = (int)($_POST['id'] ?? 0);
        $caption = trim((string)($_POST['caption'] ?? ''));
        $altText = trim((string)($_POST['alt_text'] ?? '')) ?: $caption;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($caption === '') {
            back('invalid_gallery');
        }

        $uploadedImage = handle_gallery_upload('image');

        if ($action === 'add_gallery') {
            if ($uploadedImage === null) {
                back('image_required');
            }
            $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_items')->fetchColumn();
            $stmt = $pdo->prepare('
                INSERT INTO gallery_items (image, caption, alt_text, sort_order)
                VALUES (:image, :caption, :alt_text, :sort_order)
            ');
            $stmt->execute([
                'image' => $uploadedImage, 'caption' => $caption, 'alt_text' => $altText,
                'sort_order' => $sortOrder ?: $maxOrder + 1,
            ]);
            back('gallery_added');
        }

        if ($id <= 0) {
            back('invalid_gallery');
        }
        $stmt = $pdo->prepare('SELECT image FROM gallery_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            back('invalid_gallery');
        }

        $image = $uploadedImage ?? $existing['image'];
        if ($uploadedImage !== null) {
            delete_gallery_image($existing['image']);
        }

        $stmt = $pdo->prepare('
            UPDATE gallery_items SET image = :image, caption = :caption, alt_text = :alt_text, sort_order = :sort_order
            WHERE id = :id
        ');
        $stmt->execute(['image' => $image, 'caption' => $caption, 'alt_text' => $altText, 'sort_order' => $sortOrder, 'id' => $id]);
        back('gallery_updated');
    }

    case 'delete_gallery': {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT image FROM gallery_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            delete_gallery_image($existing['image']);
            $pdo->prepare('DELETE FROM gallery_items WHERE id = :id')->execute(['id' => $id]);
        }
        back('gallery_deleted');
    }

    default:
        back('unknown_action');
}
