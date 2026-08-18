<?php
/**
 * Cloud-deploy helper — NOT used for local/XAMPP dev, where you still just
 * copy config.example.php to config.php by hand as usual.
 *
 * On a platform like Railway/Render, secrets are set as environment
 * variables in the dashboard rather than committed to a file. This script
 * runs once at container startup (see Dockerfile's CMD) and, only if
 * php/config.php doesn't already exist, generates it from those env vars —
 * so every existing require_once __DIR__.'/config.php' call in the app
 * keeps working completely unchanged.
 *
 * Safe by construction: values are placed with var_export(), not string
 * interpolation, so a bcrypt hash's literal '$' characters (e.g.
 * '$2y$10$...') can never be misread as shell/PHP variables.
 */

$configPath = __DIR__ . '/config.php';

if (file_exists($configPath)) {
    // A real config.php was provided (e.g. mounted in) — leave it alone.
    exit;
}

function env_str(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function env_bool(string $name, bool $default = false): bool
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }
    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function env_int(string $name, int $default): int
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : (int)$value;
}

$lines = [];
$lines[] = '<?php';
$lines[] = '// Auto-generated from environment variables at container startup by bootstrap-config.php.';
$lines[] = '// Do not edit by hand — edit the platform\'s environment variables instead.';
$lines[] = '';
$lines[] = 'define(\'ADMIN_PASSWORD_HASH\', ' . var_export(
    env_str('ADMIN_PASSWORD_HASH', '$2y$10$replace.this.with.a.real.hash.generated.by.the.command.above'),
    true
) . ');';
$lines[] = '';
$lines[] = 'define(\'MAIL_ENABLED\', ' . var_export(env_bool('MAIL_ENABLED', false), true) . ');';
$lines[] = 'define(\'MAIL_TO\', ' . var_export(env_str('MAIL_TO'), true) . ');';
$lines[] = 'define(\'MAIL_FROM_NAME\', ' . var_export(env_str('MAIL_FROM_NAME', 'Osama Café'), true) . ');';
$lines[] = '';
$lines[] = 'define(\'SMTP_HOST\', ' . var_export(env_str('SMTP_HOST', 'smtp.gmail.com'), true) . ');';
$lines[] = 'define(\'SMTP_PORT\', ' . var_export(env_int('SMTP_PORT', 587), true) . ');';
$lines[] = 'define(\'SMTP_SECURE\', ' . var_export(env_str('SMTP_SECURE', 'tls'), true) . ');';
$lines[] = 'define(\'SMTP_USERNAME\', ' . var_export(env_str('SMTP_USERNAME'), true) . ');';
$lines[] = 'define(\'SMTP_PASSWORD\', ' . var_export(env_str('SMTP_PASSWORD'), true) . ');';

// Only wire up MySQL if DB_HOST was actually provided — otherwise db.php's
// own default (self-contained SQLite file) applies, same as local dev.
if (env_str('DB_HOST') !== '') {
    $lines[] = '';
    $lines[] = 'define(\'DB_DRIVER\', \'mysql\');';
    $lines[] = 'define(\'DB_HOST\', ' . var_export(env_str('DB_HOST'), true) . ');';
    $lines[] = 'define(\'DB_PORT\', ' . var_export(env_int('DB_PORT', 3306), true) . ');';
    $lines[] = 'define(\'DB_NAME\', ' . var_export(env_str('DB_NAME'), true) . ');';
    $lines[] = 'define(\'DB_USER\', ' . var_export(env_str('DB_USER'), true) . ');';
    $lines[] = 'define(\'DB_PASS\', ' . var_export(env_str('DB_PASS'), true) . ');';
}

$lines[] = '';

file_put_contents($configPath, implode("\n", $lines));
echo "Generated php/config.php from environment variables.\n";
