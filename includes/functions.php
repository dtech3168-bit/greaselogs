<?php

/* =========================
   DATABASE HELPERS (PDO)
========================= */

require_once __DIR__ . '/db.php';

            //SANITIZE
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}


//ADMIN LOGIN
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirect($page) {
    header('Location: ' . SITE_URL . '/' . $page);
    exit();
}





//USER LOGIN
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Never treat an unverified account as authenticated.
    // This also invalidates old sessions created before activation was added.
    try {
        $stmt = db()->prepare("SELECT email_verified_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $verifiedAt = $stmt->fetchColumn();

        if (!$verifiedAt) {
            unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email']);
            return false;
        }

        return true;
    } catch (Throwable $e) {
        error_log("AUTH CHECK ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * OTP generator
 */
function generateOTP($length = 6) {
    return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}


/**
* Get PDO instance
*/

/**
* Get full system settings row
*/
function getSettings() {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $stmt->execute();

    return $stmt->fetch();
}

/**
* Get single setting (optional usage)
*/
function getSetting($key) {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);

    $row = $stmt->fetch();
    return $row['setting_value'] ?? null;
}

/**
* Get payment gateway config
*/
function getPaymentGateway($gateway) {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT * FROM payment_settings WHERE gateway = ? LIMIT 1");
    $stmt->execute([$gateway]);

    return $stmt->fetch();
} 

        //PRICING HELPER

function getProductPricing($product_id) {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT * FROM product_pricing WHERE product_id = ? LIMIT 1");
    $stmt->execute([$product_id]);

    return $stmt->fetch();
} 

/**
* Format currency (Naira or default)
*/
function formatCurrency($amount) {
    return '₦' . number_format((float)$amount, 2);
} 

/**
* Generate unique transaction reference
*/
function generateTransactionRef() {
    return 'TXN-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
} 



function getGatewayConfig($gateway) {
    $data = getPaymentGateway($gateway);

    if ($data && !empty($data['secret_key'])) {
        return $data;
    }

    // fallback to config
    return [
        'public_key' => null,
        'secret_key' => null
    ];
} 

function convertCurrency($amount, $from = 'USD', $to = 'NGN') {
    //Safe fallback rates (update periodically, or swap for a live FX API)
    $rates = [
        'USD_NGN' => 1500,
        'NGN_USD' => 1 / 1500,
    ];
    $key = $from . '_' . $to;
    return $amount * ($rates[$key] ?? 1);
}

//token

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Session Expired</title>
        <style>body{font-family:Arial;background:#f5f7fb;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
        .card{background:#fff;padding:30px;width:350px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);text-align:center;}
        a{display:inline-block;margin-top:15px;background:#5097A4;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;}</style>
        </head><body><div class="card"><h2>Session Expired</h2>
        <p>Your session expired or the form was submitted incorrectly. Please try again.</p>
        <a href="javascript:history.back()">Go Back</a></div></body></html>';
        exit();
    }
}

/* =========================
   ACCOUNT LOCKOUT HELPERS
   (used by both user login and admin login to stop brute-forcing)
========================= */

/**
 * Check whether a row (from `users` or `admin`) is currently locked out.
 * Returns the number of seconds remaining if locked, or 0 if not locked.
 */
function getLockoutSecondsRemaining($row): int {
    if (!$row || !is_array($row) || empty($row['locked_until'])) {
        return 0;
    }
    $remaining = strtotime($row['locked_until']) - time();
    return $remaining > 0 ? $remaining : 0;
}

/**
 * Record a failed login attempt for the given table ('users' or 'admin')
 * and lock the account if MAX_LOGIN_ATTEMPTS is reached.
 */
function registerFailedLogin(string $table, int $id): void {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT failed_attempts FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    $current = (int)($stmt->fetchColumn() ?: 0);

    $attempts = $current + 1;

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $lockUntil = date('Y-m-d H:i:s', time() + (LOGIN_LOCKOUT_MINUTES * 60));
        $stmt = $pdo->prepare("UPDATE `$table` SET failed_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->execute([$attempts, $lockUntil, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE `$table` SET failed_attempts = ? WHERE id = ?");
        $stmt->execute([$attempts, $id]);
    }
}

/**
 * Reset failed-attempt counters after a successful login.
 */
function clearFailedLogins(string $table, int $id): void {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE `$table` SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$id]);
} 
/**
 * Safely handle an uploaded listing image.
 * Returns the relative path to store in DB on success, or null if no
 * file was uploaded / upload was invalid.
 * Throws an Exception with a user-friendly message on validation failure.
 */
function handleListingImageUpload($fileField, $uploadDirRelative = 'assets/uploads/listings') {

    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed. Please try again.");
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception("Image must be smaller than 5MB.");
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new Exception("Only JPG, PNG, or WEBP images are allowed.");
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    $uploadDirAbsolute = __DIR__ . '/../' . $uploadDirRelative;
    if (!is_dir($uploadDirAbsolute)) {
        mkdir($uploadDirAbsolute, 0755, true);
    }

    $destination = $uploadDirAbsolute . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Could not save uploaded image.");
    }

    return $uploadDirRelative . '/' . $filename;
}

/* =========================
   DEVELOPER AUTH
========================= */
function isDeveloperLoggedIn() {
    return isset($_SESSION['developer_id']);
}

/**
 * Best-effort read of the tail of the PHP error log.
 * Tries the ini-configured error_log first, then a couple of common
 * cPanel fallback locations. Returns an array of lines (newest last),
 * or an empty array if nothing could be read.
 */
function getRecentErrorLogLines($maxLines = 50) {

    $candidates = [];

    $iniLog = ini_get('error_log');
    if ($iniLog) {
        $candidates[] = $iniLog;
    }

    // Common cPanel layout: /home/<user>/logs/php.error.log
    $home = getenv('HOME');
    if ($home) {
        $candidates[] = rtrim($home, '/') . '/logs/php.error.log';
    }

    foreach ($candidates as $path) {

        if (!$path || !is_readable($path)) {
            continue;
        }

        $size = filesize($path);
        $chunk = min($size, 200 * 1024); // last 200KB is plenty for ~50 lines

        $fh = fopen($path, 'r');
        if (!$fh) {
            continue;
        }

        fseek($fh, -$chunk, SEEK_END);
        $data = fread($fh, $chunk);
        fclose($fh);

        $lines = explode("\n", trim($data));

        return array_slice($lines, -$maxLines);
    }

    return [];
}

/**
 * Ping a list of external APIs concurrently and report status + latency.
 * $targets: ['Label' => 'https://url-to-ping']
 */
function checkApiHealth(array $targets) {

    $mh = curl_multi_init();
    $handles = [];

    foreach ($targets as $label => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,   // HEAD-like, we only care about reachability
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$label] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $results = [];

    foreach ($handles as $label => $ch) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);

        $results[$label] = [
            'ok'        => $httpCode > 0 && $httpCode < 500,
            'http_code' => $httpCode,
            'latency_ms'=> round($totalTime * 1000),
            'error'     => $error ?: null,
        ];

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    return $results;
}
