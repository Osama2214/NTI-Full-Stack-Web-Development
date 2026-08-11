<?php
/**
 * POST /php/menu_admin.php — admin only.
 * Handles every menu-editing action (categories + items) from
 * admin-menu.php and redirects back with a status message.
 */

require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-menu.php');
    exit;
}

require_csrf();

$pdo = osama_cafe_db();
$action = $_POST['action'] ?? '';

function back(string $status): void
{
    admin_action_back('admin-menu.php', MENU_STATUS_MESSAGES, $status);
}

function slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'category-' . substr(md5((string)microtime()), 0, 6);
}

const ALLOWED_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5MB

/** Saves an uploaded image into images/menu/ and returns its filename, or null if no file was sent. */
function handle_image_upload(string $field): ?string
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
    $filename = 'item-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destDir = __DIR__ . '/../images/menu';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
        back('upload_error');
    }
    return 'menu/' . $filename;
}

/** Deletes a previously-uploaded item image, but only if it lives under images/menu/ (never touches the original seed photos). */
function delete_item_image(?string $image): void
{
    if ($image && str_starts_with($image, 'menu/')) {
        $path = __DIR__ . '/../images/' . $image;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

switch ($action) {

    case 'add_category': {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            back('empty_category_name');
        }
        $slug = slugify($name);
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM categories')->fetchColumn();
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (:name, :slug, :sort_order)');
            $stmt->execute(['name' => $name, 'slug' => $slug, 'sort_order' => $maxOrder + 1]);
        } catch (Throwable $e) {
            back('category_exists');
        }
        back('category_added');
    }

    case 'rename_category': {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($id <= 0 || $name === '') {
            back('empty_category_name');
        }
        $stmt = $pdo->prepare('UPDATE categories SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
        back('category_renamed');
    }

    case 'delete_category': {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            back('category_not_empty');
        }
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
        back('category_deleted');
    }

    case 'add_item':
    case 'update_item': {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $linkLabel = trim((string)($_POST['link_label'] ?? '')) ?: 'Order Now';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($title === '' || $description === '' || $categoryId <= 0) {
            back('invalid_item');
        }

        $uploadedImage = handle_image_upload('image');

        if ($action === 'add_item') {
            if ($uploadedImage === null) {
                back('image_required');
            }
            $stmt = $pdo->prepare('
                INSERT INTO menu_items (category_id, title, description, price, image, link_label, sort_order)
                VALUES (:category_id, :title, :description, :price, :image, :link_label, :sort_order)
            ');
            $stmt->execute([
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'price' => $price,
                'image' => $uploadedImage,
                'link_label' => $linkLabel,
                'sort_order' => $sortOrder,
            ]);
            back('item_added');
        }

        // update_item
        if ($id <= 0) {
            back('invalid_item');
        }
        $stmt = $pdo->prepare('SELECT image FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            back('invalid_item');
        }

        $image = $uploadedImage ?? $existing['image'];
        if ($uploadedImage !== null) {
            delete_item_image($existing['image']);
        }

        $stmt = $pdo->prepare('
            UPDATE menu_items
            SET category_id = :category_id, title = :title, description = :description,
                price = :price, image = :image, link_label = :link_label, sort_order = :sort_order
            WHERE id = :id
        ');
        $stmt->execute([
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'image' => $image,
            'link_label' => $linkLabel,
            'sort_order' => $sortOrder,
            'id' => $id,
        ]);
        back('item_updated');
    }

    case 'delete_item': {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT image FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            delete_item_image($existing['image']);
            $pdo->prepare('DELETE FROM menu_items WHERE id = :id')->execute(['id' => $id]);
        }
        back('item_deleted');
    }

    default:
        back('unknown_action');
}
