<?php
/**
 * POST /php/settings_admin.php — admin only.
 * Handles site-text settings and branch (location) CRUD from
 * admin-settings.php, and redirects back with a status message.
 */

require_once __DIR__ . '/admin-common.php';

admin_session_start();
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-settings.php');
    exit;
}

require_csrf();

$pdo = osama_cafe_db();
$action = $_POST['action'] ?? '';

function back(string $status): void
{
    admin_action_back('admin-settings.php', SETTINGS_STATUS_MESSAGES, $status);
}

const EDITABLE_SETTINGS = [
    'site_email', 'site_phone', 'whatsapp_number',
    'hero_subtitle', 'hero_title', 'hero_description',
    'about_title', 'about_text', 'footer_about_text',
];

switch ($action) {

    case 'update_settings': {
        $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        foreach (EDITABLE_SETTINGS as $key) {
            if (!isset($_POST[$key])) {
                continue;
            }
            $value = trim((string)$_POST[$key]);
            $stmt->execute(['key' => $key, 'value' => $value]);
        }
        back('settings_saved');
    }

    case 'add_branch':
    case 'update_branch': {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $lat = $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
        $lng = $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;

        $mapsUrl = trim((string)($_POST['maps_url'] ?? ''));
        if ($mapsUrl !== '') {
            $scheme = parse_url($mapsUrl, PHP_URL_SCHEME);
            if (!filter_var($mapsUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                back('invalid_maps_url');
            }
        } else {
            $mapsUrl = null;
        }

        if ($name === '' || $address === '' || $phone === '') {
            back('invalid_branch');
        }

        if ($action === 'add_branch') {
            $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM branches')->fetchColumn();
            $stmt = $pdo->prepare('
                INSERT INTO branches (name, address, phone, lat, lng, maps_url, is_primary, sort_order)
                VALUES (:name, :address, :phone, :lat, :lng, :maps_url, 0, :sort_order)
            ');
            $stmt->execute([
                'name' => $name, 'address' => $address, 'phone' => $phone,
                'lat' => $lat, 'lng' => $lng, 'maps_url' => $mapsUrl, 'sort_order' => $maxOrder + 1,
            ]);
            back('branch_added');
        }

        if ($id <= 0) {
            back('invalid_branch');
        }
        $stmt = $pdo->prepare('
            UPDATE branches SET name = :name, address = :address, phone = :phone, lat = :lat, lng = :lng, maps_url = :maps_url
            WHERE id = :id
        ');
        $stmt->execute([
            'name' => $name, 'address' => $address, 'phone' => $phone,
            'lat' => $lat, 'lng' => $lng, 'maps_url' => $mapsUrl, 'id' => $id,
        ]);
        back('branch_updated');
    }

    case 'set_primary_branch': {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        $pdo->exec('UPDATE branches SET is_primary = 0');
        $pdo->prepare('UPDATE branches SET is_primary = 1 WHERE id = :id')->execute(['id' => $id]);
        $pdo->commit();
        back('branch_primary_set');
    }

    case 'delete_branch': {
        $id = (int)($_POST['id'] ?? 0);
        $total = (int)$pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn();
        if ($total <= 1) {
            back('branch_last_one');
        }

        $stmt = $pdo->prepare('SELECT is_primary FROM branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $wasPrimary = (bool)$stmt->fetchColumn();

        $pdo->prepare('DELETE FROM branches WHERE id = :id')->execute(['id' => $id]);

        if ($wasPrimary) {
            // Promote the next branch so there's always exactly one primary.
            $nextId = $pdo->query('SELECT id FROM branches ORDER BY sort_order, id LIMIT 1')->fetchColumn();
            if ($nextId) {
                $pdo->prepare('UPDATE branches SET is_primary = 1 WHERE id = :id')->execute(['id' => $nextId]);
            }
        }
        back('branch_deleted');
    }

    default:
        back('unknown_action');
}
