<?php
/**
* Greaselogs - Clean Configuration File
*/

/* =========================
   DATABASE CONFIG
========================= */
define('DB_HOST', 'localhost');
define('DB_USER', 'DB_USER');
define('DB_PASS', 'DB_PASS');
define('DB_NAME', 'DB_NAME');


/* =========================
   TIMEZONE (kept consistent between PHP and MySQL
   so OTP/token expiry checks never drift)
========================= */
date_default_timezone_set('Africa/Lagos');
define('SITE_NAME', 'Greaselog');
define('SITE_URL', '');
define('SITE_EMAIL', '');


/* =========================
   API CONFIG (Fallback)
========================= */
define('ACCTSHOP_API_BASE', 'https://acctshop.com/api/');


/* =========================
   PAYMENT CONFIG (Fallback ONLY)
   → Real values should come from DB
========================= */
define('CRYPTOMUS_API_KEY', 'null');
define('CRYPTOMUS_MERCHANT_ID', 'null'); // if required 

define('MAIL_MODE', 'smtp'); // 'local' = log OTPs instead of emailing (dev). Set to 'smtp' in production.

/* =========================
   SMTP CONFIG (used when MAIL_MODE = 'smtp')
   → Fill these in with your real mailbox / SMTP provider details
========================= */
define('SMTP_HOST', 'mail.example.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');

define('SMTP_USER', 'support@example.com');
define('SMTP_PASS', 'greaselogs');

define('MAIL_FROM_EMAIL', 'support@example.com');
define('MAIL_FROM_NAME', 'Greaselogs');

/* =========================
   PAYMENT CONFIG (Fallback ONLY)
   → Real values should come from DB
========================= */
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxx');
define('PAYSTACK_SECRET_KEY', 'sk_test_xxx');


/* =========================
   SECURITY SETTINGS
========================= */
define('SESSION_LIFETIME', 3600); // 1 hour
define('OTP_EXPIRY', 600);        // 10 minutes
// Email activation links remain valid for 24 hours.
define('ACTIVATION_EXPIRY', 86400);

/* =========================
   BRUTE-FORCE / LOCKOUT SETTINGS
========================= */
define('MAX_LOGIN_ATTEMPTS', 5);      // failed attempts before lockout
define('LOGIN_LOCKOUT_MINUTES', 15);  // how long an account stays locked
define('MAX_OTP_ATTEMPTS', 5);        // wrong OTP guesses before it's invalidated
define('OTP_REQUEST_COOLDOWN', 60);   // seconds between forgot-password requests for same email


/* =========================
   ENVIRONMENT
   Set to 'production' on the live site. Only 'development'
   shows raw PHP errors on screen.
========================= */
define('APP_ENV', 'production');

/* =========================
   ERROR REPORTING
========================= */
error_reporting(E_ALL);

if (APP_ENV === 'production') {

    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    // error_log destination is already set outside the web root via .user.ini
    // (cPanel > MultiPHP INI Editor). Leaving it untouched here.

    set_exception_handler(function ($e) {
        error_log($e);
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Something went wrong</title>
        <style>body{font-family:Arial;background:#f5f7fb;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
        .card{background:#fff;padding:30px;width:380px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);text-align:center;}
        a{display:inline-block;margin-top:15px;background:#5097A4;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;}</style>
        </head><body><div class="card"><h2>Something went wrong</h2>
        <p>We hit an unexpected error. Please try again in a moment.</p>
        <a href="' . rtrim(SITE_URL, '/') . '/pages/dashboard.php">Go to Dashboard</a></div></body></html>';
        exit();
    });

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        error_log("PHP Error [$severity]: $message in $file on line $line");
        // Let fatal-level errors fall through to the exception handler's
        // shutdown path instead of crashing with a raw trace.
        if (in_array($severity, [E_ERROR, E_USER_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
        return true;
    });

} else {
    ini_set('display_errors', 1);
}


/* =========================
   SESSION SECURITY + START
========================= */
if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // cookie only sent over HTTPS in production
        'httponly' => true,       // JS can't read the session cookie (XSS mitigation)
        'samesite' => 'Lax',      // CSRF mitigation
    ]);

    session_start();
} 
