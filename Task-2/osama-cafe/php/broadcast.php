<?php
/**
 * POST /php/broadcast.php — admin only.
 * Sends { subject, body } to every newsletter subscriber, one real email
 * each via SMTP. Triggered from the "Send Newsletter" form in admin.php.
 */

require_once __DIR__ . '/admin-common.php';
require_once __DIR__ . '/mailer.php';

admin_session_start();
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-messages.php');
    exit;
}

require_csrf();

if (!mail_is_configured()) {
    if (is_ajax()) {
        ajax_json(['ok' => false, 'type' => 'warn', 'message' => "SMTP isn't configured yet — see the note below the subscriber list."]);
    }
    header('Location: admin-messages.php?broadcast=not_configured');
    exit;
}

$subject = trim((string)($_POST['subject'] ?? ''));
$body = trim((string)($_POST['body'] ?? ''));

if ($subject === '' || $body === '') {
    if (is_ajax()) {
        ajax_json(['ok' => false, 'type' => 'warn', 'message' => 'Please fill in both a subject and a message before sending.']);
    }
    header('Location: admin-messages.php?broadcast=empty');
    exit;
}

$pdo = osama_cafe_db();
$subscribers = $pdo->query('SELECT email FROM subscribers')->fetchAll(PDO::FETCH_COLUMN);

$sent = 0;
$failed = 0;
foreach ($subscribers as $subscriberEmail) {
    if (send_mail($subscriberEmail, $subscriberEmail, $subject, $body)) {
        $sent++;
    } else {
        $failed++;
    }
}

$resultMessage = "Newsletter sent — $sent delivered" . ($failed > 0 ? ", $failed failed (check the PHP error log)" : '') . '.';
if (is_ajax()) {
    ajax_json(['ok' => true, 'type' => 'success', 'message' => $resultMessage]);
}
header('Location: admin-messages.php?broadcast=done&sent=' . $sent . '&failed=' . $failed);
exit;
