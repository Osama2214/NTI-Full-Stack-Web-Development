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

// --- Database (optional) ---
// By default the site uses a self-contained SQLite file at
// data/osama_cafe.sqlite — nothing to configure, works out of the box.
// On hosting with no SQLite/SSH support (e.g. most free shared hosts),
// uncomment these and fill in the MySQL database details from your
// host's control panel instead:
// define('DB_DRIVER', 'mysql');
// define('DB_HOST', 'sql123.example.com');
// define('DB_PORT', 3306);
// define('DB_NAME', 'your_db_name');
// define('DB_USER', 'your_db_user');
// define('DB_PASS', 'your_db_password');
