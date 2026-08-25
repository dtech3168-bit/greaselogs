<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$pdo = db();

if (!isset($_SESSION['reset_email'])) {
    echo json_encode(["status" => "error", "message" => "No active reset session"]);
    exit;
}

$email = $_SESSION['reset_email'];

/* Rate-limit resend the same as the initial request */
$stmt = $pdo->prepare("SELECT created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$email]);
$lastRequest = $stmt->fetchColumn();

if ($lastRequest && (time() - strtotime($lastRequest)) < OTP_REQUEST_COOLDOWN) {
    echo json_encode(["status" => "error", "message" => "Please wait before requesting another code", "retry_after" => OTP_REQUEST_COOLDOWN - (time() - strtotime($lastRequest))]);
    exit;
}

$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(32));
$now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

/* delete old */
$pdo->prepare("DELETE FROM password_resets WHERE email=?")
    ->execute([$email]);

/* insert new */
$stmt = $pdo->prepare("
    INSERT INTO password_resets (email, otp, token, expires_at, created_at)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$email, $otp, $token, $expires, $now]);

/* send real email via PHPMailer (or log in local mode) */
$bodyHtml = "
    <p>Hi,</p>
    <p>Your " . htmlspecialchars(SITE_NAME) . " password reset code is:</p>
    <h2 style='letter-spacing:4px;'>{$otp}</h2>
    <p>This code expires in " . (int)(OTP_EXPIRY / 60) . " minutes. If you didn't request this, you can ignore this email.</p>
";
sendMail($email, "Your password reset code", $bodyHtml);

/* update session token */
$_SESSION['reset_token'] = $token;

echo json_encode(["status" => "success", "retry_after" => OTP_REQUEST_COOLDOWN]);
