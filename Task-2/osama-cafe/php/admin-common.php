<?php
/**
 * Shared helpers for every admin-* page: session bootstrap, auth guard,
 * CSRF protection, login rate limiting, pagination, and the shared
 * header/nav markup. Keeping this in one place means the five admin
 * pages no longer each redeclare h() and duplicate the same nav HTML.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/** Starts the session with hardened cookie params. Call this instead of session_start() directly. */
function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Redirects to the login page unless the current session is authenticated. Call at the top of every protected page. */
function require_admin_auth(): void
{
    if (!empty($_SESSION['admin_authed'])) {
        return;
    }
    if (is_ajax()) {
        // A fragment/action request from an expired session — tell the client
        // to do a full reload to the login page, rather than splicing the
        // login form's HTML into whatever slot was expecting a table/panel.
        ajax_json(['ok' => false, 'authRequired' => true]);
    }
    header('Location: admin.php');
    exit;
}

// --- AJAX dashboard requests ----------------------------------------------
// The admin dashboard fetches page fragments and posts actions via
// JavaScript instead of doing full page reloads for every search, filter,
// or form submit (see admin.js). Every such request carries this header.

/** True when the current request came from the dashboard's own fetch() calls, not a normal browser navigation. */
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

/** Sends a JSON response and ends the request. Used for both action results and error states. */
function ajax_json(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * The single source of truth for every status message admin-menu.php /
 * menu_admin.php can show — used both to render the toast after a normal
 * (non-JS) redirect, and to build the JSON message an AJAX POST returns.
 */
const MENU_STATUS_MESSAGES = [
    'category_added' => ['success', 'Category added.'],
    'category_renamed' => ['success', 'Category renamed.'],
    'category_deleted' => ['success', 'Category deleted.'],
    'category_not_empty' => ['warn', 'That category still has menu items — move or delete them first.'],
    'category_exists' => ['warn', 'A category with that name already exists.'],
    'empty_category_name' => ['warn', 'Category name can\'t be empty.'],
    'item_added' => ['success', 'Menu item added.'],
    'item_updated' => ['success', 'Menu item updated.'],
    'item_deleted' => ['success', 'Menu item deleted.'],
    'invalid_item' => ['warn', 'Please fill in all required fields.'],
    'image_required' => ['warn', 'Please choose a photo for the new item.'],
    'image_too_large' => ['warn', 'That image is too large (max 5MB).'],
    'invalid_image_type' => ['warn', 'Please upload a JPG, PNG, or WEBP image.'],
    'upload_error' => ['warn', 'The image upload failed — please try again.'],
    'unknown_action' => ['warn', 'Something went wrong — please try again.'],
];

/** Same idea as MENU_STATUS_MESSAGES, for admin-settings.php / settings_admin.php. */
const SETTINGS_STATUS_MESSAGES = [
    'settings_saved' => ['success', 'Settings saved.'],
    'branch_added' => ['success', 'Branch added.'],
    'branch_updated' => ['success', 'Branch updated.'],
    'branch_deleted' => ['success', 'Branch deleted.'],
    'branch_primary_set' => ['success', 'Primary branch updated.'],
    'branch_last_one' => ['warn', "Can't delete your only branch — add another one first."],
    'invalid_branch' => ['warn', 'Please fill in name, address, and phone.'],
    'invalid_maps_url' => ['warn', 'That Google Maps link doesn\'t look right — paste the full link, starting with http:// or https://.'],
    'unknown_action' => ['warn', 'Something went wrong — please try again.'],
];

/** Same idea as MENU_STATUS_MESSAGES, for admin-content.php / content_admin.php. */
const CONTENT_STATUS_MESSAGES = [
    'faq_added' => ['success', 'FAQ added.'],
    'faq_updated' => ['success', 'FAQ updated.'],
    'faq_deleted' => ['success', 'FAQ deleted.'],
    'testimonial_added' => ['success', 'Testimonial added.'],
    'testimonial_updated' => ['success', 'Testimonial updated.'],
    'testimonial_deleted' => ['success', 'Testimonial deleted.'],
    'gallery_added' => ['success', 'Photo added to the gallery.'],
    'gallery_updated' => ['success', 'Gallery photo updated.'],
    'gallery_deleted' => ['success', 'Gallery photo removed.'],
    'invalid_faq' => ['warn', 'Please fill in both the question and the answer.'],
    'invalid_testimonial' => ['warn', 'Please fill in the name, role, and quote.'],
    'invalid_gallery' => ['warn', 'Please fill in the caption and choose a photo.'],
    'image_required' => ['warn', 'Please choose a photo for the new gallery item.'],
    'image_too_large' => ['warn', 'That image is too large (max 5MB).'],
    'invalid_image_type' => ['warn', 'Please upload a JPG, PNG, or WEBP image.'],
    'upload_error' => ['warn', 'The image upload failed — please try again.'],
    'unknown_action' => ['warn', 'Something went wrong — please try again.'],
];

/**
 * Ends a POST action handler: responds with JSON {ok, type, message} for an
 * AJAX request, or does the old redirect-with-?status= for a normal form
 * post (so the dashboard still works with JavaScript off). $messages is one
 * of the *_STATUS_MESSAGES maps above; $status is the key within it.
 */
function admin_action_back(string $redirectUrl, array $messages, string $status): void
{
    [$type, $message] = $messages[$status] ?? ['warn', 'Something went wrong — please try again.'];
    if (is_ajax()) {
        ajax_json(['ok' => $type === 'success', 'type' => $type, 'message' => $message]);
    }
    header('Location: ' . $redirectUrl . '?status=' . urlencode($status));
    exit;
}

// --- CSRF protection -------------------------------------------------

/** Returns the current session's CSRF token, generating one on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** A ready-to-echo hidden input carrying the CSRF token, for use inside <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/** Halts the request with a 403 unless the posted csrf_token matches the session's. Call first thing in every POST handler. */
function require_csrf(): void
{
    $posted = (string)($_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    if ($expected !== '' && hash_equals($expected, $posted)) {
        return;
    }
    http_response_code(403);
    $message = 'Your session expired or this form was already submitted. Refresh the page and try again.';
    if (is_ajax()) {
        ajax_json(['ok' => false, 'type' => 'warn', 'message' => $message]);
    }
    exit($message);
}

// --- Login rate limiting ----------------------------------------------
// File-based, keyed by IP, so a fresh session/tab can't be used to bypass
// it. Lives in data/ alongside the sqlite file (same git-ignored folder).

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes
const LOGIN_ATTEMPT_WINDOW = 900;  // failed attempts older than this don't count

function login_attempts_path(): string
{
    return __DIR__ . '/../data/login_attempts.json';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/** Reads the attempts file under a lock, runs $mutator on it, writes it back. */
function with_login_attempts(callable $mutator)
{
    $path = login_attempts_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fh = fopen($path, 'c+');
    if (!$fh) {
        // Fail open — a broken filesystem shouldn't lock everyone out entirely,
        // it just means this request isn't rate-limited.
        return $mutator([]);
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        $data = [];
    }
    $result = $mutator($data);
    $data = is_array($result) && isset($result['__data']) ? $result['__data'] : $data;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

/** Returns remaining lockout seconds (0 if not locked out) for the current IP. */
function login_lockout_remaining(): int
{
    $ip = client_ip();
    return (int)with_login_attempts(function (array $data) use ($ip) {
        $entry = $data[$ip] ?? null;
        if (!$entry) {
            return 0;
        }
        $remaining = ($entry['locked_until'] ?? 0) - time();
        return max(0, $remaining);
    });
}

/** Records a failed login attempt for the current IP, locking it out once the threshold is hit. */
function record_login_failure(): void
{
    $ip = client_ip();
    $now = time();
    with_login_attempts(function (array $data) use ($ip, $now) {
        $entry = $data[$ip] ?? ['count' => 0, 'first_at' => $now, 'locked_until' => 0];
        if ($now - ($entry['first_at'] ?? $now) > LOGIN_ATTEMPT_WINDOW) {
            $entry = ['count' => 0, 'first_at' => $now, 'locked_until' => 0];
        }
        $entry['count']++;
        if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
            $entry['locked_until'] = $now + LOGIN_LOCKOUT_SECONDS;
            $entry['count'] = 0;
            $entry['first_at'] = $now;
        }
        $data[$ip] = $entry;
        return ['__data' => $data];
    });
}

/** Clears the failure count for the current IP after a successful login. */
function clear_login_failures(): void
{
    $ip = client_ip();
    with_login_attempts(function (array $data) use ($ip) {
        unset($data[$ip]);
        return ['__data' => $data];
    });
}

/** Escapes $value for HTML and wraps case-insensitive matches of $needle in <mark>, for search results. */
function highlight(string $value, string $needle): string
{
    $escaped = h($value);
    $needle = trim($needle);
    if ($needle === '') {
        return $escaped;
    }
    $pattern = '/' . preg_quote(h($needle), '/') . '/i';
    return preg_replace($pattern, '<mark>$0</mark>', $escaped);
}

// --- Pagination ---------------------------------------------------------

/**
 * Works out offset/limit/page-count for a GET-param-driven page number.
 * Returns ['page' => int, 'perPage' => int, 'offset' => int, 'totalPages' => int].
 */
function paginate_params(int $totalItems, int $perPage, string $param = 'page'): array
{
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $page = max(1, min($totalPages, (int)($_GET[$param] ?? 1)));
    return [
        'page' => $page,
        'perPage' => $perPage,
        'offset' => ($page - 1) * $perPage,
        'totalPages' => $totalPages,
    ];
}

/**
 * Renders Prev/Next + numbered pagination links. $baseParams are the other
 * GET params to preserve (e.g. search terms) — the page param is added/overwritten per link.
 */
function render_pagination(int $page, int $totalPages, string $pageParam = 'page', array $baseParams = []): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $link = function (int $p) use ($pageParam, $baseParams): string {
        $params = $baseParams;
        $params[$pageParam] = $p;
        return '?' . http_build_query($params);
    };
    $out = '<nav class="pagination" aria-label="Pagination">';
    $out .= $page > 1
        ? '<a href="' . h($link($page - 1)) . '" class="btn btn-secondary btn-small">&larr; Prev</a>'
        : '<span class="btn btn-secondary btn-small btn-disabled">&larr; Prev</span>';
    $out .= '<span class="pagination-status">Page ' . $page . ' of ' . $totalPages . '</span>';
    $out .= $page < $totalPages
        ? '<a href="' . h($link($page + 1)) . '" class="btn btn-secondary btn-small">Next &rarr;</a>'
        : '<span class="btn btn-secondary btn-small btn-disabled">Next &rarr;</span>';
    $out .= '</nav>';
    return $out;
}

// --- Toast notifications -------------------------------------------------

/**
 * Renders a single status toast (top-right). Auto-dismisses after ~8s, with
 * a countdown bar along the bottom (pausing while hovered/focused), or
 * close it early with the × button — see admin.js. $type is 'success' or
 * 'warn'. Call once per page load, right after working out the status
 * message to show (if any).
 *
 * Uses the "admin-toast" class prefix, not plain "toast" — style.css (also
 * loaded on every admin page) already defines its own unrelated .toast /
 * .toast-container for the public site's contact-form feedback, and it
 * starts hidden off-screen (transform: translateX(-120%)) by default. A
 * shared class name meant these rules collided: once this toast's entrance
 * animation finished, the old rule's transform took back over and slid it
 * off-screen — which looked exactly like an early auto-dismiss, but had
 * nothing to do with any timer.
 */
function render_toast(string $type, string $text): void
{
    ?>
    <div class="admin-toast-container">
      <div class="admin-toast <?= h($type) ?>" role="status">
        <span><?= h($text) ?></span>
        <button type="button" class="admin-toast-close" aria-label="Dismiss">&times;</button>
        <div class="admin-toast-progress"></div>
      </div>
    </div>
    <?php
}

/** Looks up $status in a ['key' => [type, text]] map and renders it as a toast, if present. */
function render_status_toast(?string $status, array $messages): void
{
    if ($status === null || !isset($messages[$status])) {
        return;
    }
    [$type, $text] = $messages[$status];
    render_toast($type, $text);
}

// --- Shared header / nav -------------------------------------------------

/**
 * Renders the sticky top bar shared by every admin page, with the current
 * page highlighted. $active is one of: overview, messages, menu, content, settings.
 */
function render_admin_nav(string $active): void
{
    $links = [
        'overview' => ['admin-overview.php', 'Overview'],
        'messages' => ['admin-messages.php', 'Messages &amp; Subscribers'],
        'menu' => ['admin-menu.php', 'Manage Menu'],
        'content' => ['admin-content.php', 'Site Content'],
        'settings' => ['admin-settings.php', 'Site Settings'],
    ];
    ?>
    <header class="admin-topbar">
      <div class="admin-topbar-inner">
        <a href="admin-overview.php" class="admin-brand">
          <img src="../images/logo-1.png" alt="Osama Café" class="admin-brand-logo">
          Osama Café <span>Admin</span>
        </a>
        <button type="button" class="admin-nav-toggle" id="admin-nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="admin-nav">
          <span></span><span></span><span></span>
        </button>
        <nav class="admin-nav" id="admin-nav">
          <?php foreach ($links as $key => [$href, $label]): ?>
            <a href="<?= h($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= $label ?></a>
          <?php endforeach; ?>
          <a href="admin.php?logout=1" class="admin-logout">Log Out</a>
        </nav>
      </div>
    </header>
    <?php
}
