<?php
/**
 * Shared email sending helper, built on PHPMailer + SMTP.
 * Every function here fails soft: if MAIL_ENABLED is off, or SMTP_* isn't
 * filled in, or sending throws for any reason, callers get back `false`
 * instead of a crash — nothing here should ever break a form submission.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function mail_is_configured(): bool
{
    return defined('MAIL_ENABLED') && MAIL_ENABLED
        && defined('SMTP_USERNAME') && SMTP_USERNAME !== ''
        && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '';
}

function new_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_USERNAME, defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Osama Café');
    return $mail;
}

/** Sends one email. Returns true on success, false on any failure. */
function send_mail(string $toEmail, string $toName, string $subject, string $body): bool
{
    if (!mail_is_configured()) {
        return false;
    }
    try {
        $mail = new_mailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('send_mail failed for ' . $toEmail . ': ' . $e->getMessage());
        return false;
    }
}
