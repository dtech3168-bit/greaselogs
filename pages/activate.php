<?php
/**
 * Greaselogs - Email Activation
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

$token = trim($_GET['token'] ?? '');
$activated = false;
$error = '';

if ($token !== '') {
    try {
        $user = new User();
        $activated = $user->activateByToken($token);
        if (!$activated) {
            $error = "This activation link is invalid, expired, or has already been used.";
        }
    } catch (Throwable $e) {
        error_log("ACTIVATION ERROR: " . $e->getMessage());
        $error = "We could not activate your account right now. Please try again later.";
    }
} else {
    $error = "No activation token was provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Greaselogs</h1>
                <?php if ($activated): ?>
                    <p>Your email has been verified successfully.</p>
                <?php else: ?>
                    <p>We couldn't activate your account.</p>
                <?php endif; ?>
            </div>

            <?php if ($activated): ?>
                <div class="alert alert-success">
                    Your account is now active. You can log in and access your dashboard.
                </div>
                <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
            <?php else: ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <a href="login.php" class="btn btn-outline btn-block">Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
