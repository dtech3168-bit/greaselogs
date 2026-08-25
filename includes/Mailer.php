<?php
/**
 * Greaselogs - Mail helper (PHPMailer / SMTP)
 *
 * Wraps PHPMailer so the rest of the app can just call sendMail().
 * Falls back to logging in local/dev mode so you never get stuck
 * testing without a real SMTP account.
 */

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Send an email via SMTP (PHPMailer).
 *
 * In MAIL_MODE 'local', the email is written to error_log instead of
 * being sent, so you can develop without real SMTP credentials.
 *
 * @return bool true on success, false on failure
 */
function sendMail(string $toEmail, string $subject, string $bodyHtml, string $bodyText = ''): bool {

    if (MAIL_MODE === 'local') {
        error_log("[MAIL:local] To: $toEmail | Subject: $subject | Body: " . strip_tags($bodyHtml));
        return true;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION; // 'tls' or 'ssl'
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody  = $bodyText !== '' ? $bodyText : strip_tags($bodyHtml);

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        error_log("MAIL ERROR to $toEmail: " . $mail->ErrorInfo);
        return false;
    }
}
