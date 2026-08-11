<?php
/**
 * Copy this file to config.php and fill in your own values.
 * config.php is git-ignored on purpose — never commit real secrets.
 */

// Password for /php/admin.php (view submitted messages & subscribers).
// Stored as a bcrypt hash, not plaintext — never put a real password directly
// here. Generate your own with:
//   php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
// and paste the result below.
define('ADMIN_PASSWORD_HASH', '$2y$10$replace.this.with.a.real.hash.generated.by.the.command.above');

// Set to true once SMTP_* below is filled in, to turn on fully-automatic
// email sending: a copy of every contact-form message, an auto-reply to
// the visitor, and one-click newsletter broadcasts from the admin page.
// Leave it false and everything else still works — messages and
// subscribers are always saved to the database regardless of this.
define('MAIL_ENABLED', false);

define('MAIL_TO', 'osamaahmed.dev00@gmail.com');       // where contact-form copies go
define('MAIL_FROM_NAME', 'Osama Café');

// --- SMTP (only needed if MAIL_ENABLED is true) ---
// For Gmail: SMTP_USERNAME is your full Gmail address, and SMTP_PASSWORD
// must be a 16-character "App Password" — NOT your normal Gmail password.
// Generate one at https://myaccount.google.com/apppasswords (requires
// 2-Step Verification to be turned on for the account first).
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
