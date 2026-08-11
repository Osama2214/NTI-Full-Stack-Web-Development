<?php
/**
 * POST /php/contact.php
 * Body: { name, email, message, website (honeypot — must stay empty) }
 * Saves the message to the database and, optionally, forwards it by email.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

require_post();
$input = read_input();

// Honeypot: real visitors never fill this hidden field. If it's filled,
// a bot did — pretend success so it doesn't learn to retry.
if (!empty($input['website'])) {
    json_response(['ok' => true]);
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$message = trim((string)($input['message'] ?? ''));

$errors = [];
if (mb_strlen($name) < 2) {
    $errors['name'] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (mb_strlen($message) < 5) {
    $errors['message'] = 'Please write a short message.';
}
if ($errors) {
    json_response(['ok' => false, 'errors' => $errors], 422);
}

try {
    $pdo = osama_cafe_db();
    $stmt = $pdo->prepare('INSERT INTO messages (name, email, message) VALUES (:name, :email, :message)');
    $stmt->execute(['name' => $name, 'email' => $email, 'message' => $message]);
} catch (Throwable $e) {
    error_log('contact.php insert failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Something went wrong saving your message. Please try again.'], 500);
}

// Best-effort only, and only if SMTP is configured (see mail_is_configured()
// in mailer.php) — the message is already safely saved above regardless of
// whether either of these succeeds.
if (mail_is_configured()) {
    send_mail(MAIL_TO, 'Osama Café', "New message from $name", "$message\n\n— $name ($email)");
    send_mail(
        $email,
        $name,
        'We got your message — Osama Café',
        "Hi $name,\n\nThanks for reaching out to Osama Café! We received your message and will get back to you soon.\n\nYour message:\n$message\n\n— Osama Café"
    );
}

json_response(['ok' => true]);
