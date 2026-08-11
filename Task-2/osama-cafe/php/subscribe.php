<?php
/**
 * POST /php/subscribe.php
 * Body: { email }
 * Saves a newsletter subscriber. Re-subscribing with the same email is
 * treated as a success (no duplicate-email error surfaced to the user).
 */

require_once __DIR__ . '/helpers.php';

require_post();
$input = read_input();

$email = trim((string)($input['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}

try {
    $pdo = osama_cafe_db();
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (:email)');
    $stmt->execute(['email' => $email]);
} catch (Throwable $e) {
    error_log('subscribe.php insert failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}

json_response(['ok' => true]);
