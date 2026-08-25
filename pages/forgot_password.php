<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Mailer.php';

$pdo = db();

$email = '';
$error = '';
$success = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email = sanitize($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        // Rate-limit: don't allow a new OTP request for this email
        // more than once every OTP_REQUEST_COOLDOWN seconds.
        $stmt = $pdo->prepare("SELECT created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
        $lastRequest = $stmt->fetchColumn();

        if ($lastRequest && (time() - strtotime($lastRequest)) < OTP_REQUEST_COOLDOWN) {
            $error = "Please wait a moment before requesting another code.";
        } else {

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $token = bin2hex(random_bytes(32));
                $now = date('Y-m-d H:i:s');
                $expires = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

                $pdo->prepare("DELETE FROM password_resets WHERE email=?")
                    ->execute([$email]);

                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (email, otp, token, expires_at, created_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$email, $otp, $token, $expires, $now]);

                $bodyHtml = "
                    <p>Hi,</p>
                    <p>Your " . htmlspecialchars(SITE_NAME) . " password reset code is:</p>
                    <h2 style='letter-spacing:4px;'>{$otp}</h2>
                    <p>This code expires in " . (int)(OTP_EXPIRY / 60) . " minutes. If you didn't request this, you can ignore this email.</p>
                ";
                sendMail($email, "Your password reset code", $bodyHtml);
            }

            // Always show the same behavior whether or not the email exists,
            // so this form can't be used to discover registered emails.
            $_SESSION['reset_email'] = $email;
            header("Location: verify_otp.php?email=" . urlencode($email));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>

<link rel="stylesheet" href="../assets/css/index.css">

<style>
body {
    font-family: Inter, sans-serif;
    background: #f5f7fb;
    margin: 0;
}

.container {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    width: 380px;
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
}

h2 {
    color: #5097A4;
}

input {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    border-radius: 10px;
    border: 1px solid #ddd;
}

button {
    width: 100%;
    margin-top: 15px;
    padding: 12px;
    background: #5097A4;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

.alert {
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 8px;
}

.error { background: #ffe5e5; }
.success { background: #e6f7f8; }
</style>

</head>
<body>

<div class="container">
    <div class="card">

        <h2>Forgot Password</h2>
        <p>Enter your email to continue</p>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <input type="email" name="email" placeholder="Email address" value="<?= htmlspecialchars($email) ?>" required>

            <button type="submit">Send OTP</button>

        </form>

    </div>
</div>

</body>
</html>
