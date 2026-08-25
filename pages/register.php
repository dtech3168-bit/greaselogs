<?php
/**
 * DeluxeSocial - User Registration Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/Mailer.php';

$error = '';
$success = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $user = new User();
        $user_id = $user->register($username, $email, $password);
        
        if ($user_id) {
            try {
                $activationToken = $user->createActivationToken($user_id);
                $activationLink = rtrim(SITE_URL, '/') . '/pages/activate.php?token=' . urlencode($activationToken);

                $bodyHtml = "
                    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:30px;background:#f7fafb;color:#2d3e40;'>
                        <h2 style='color:#5097A4;margin-top:0;'>Welcome to " . htmlspecialchars(SITE_NAME) . "</h2>
                        <p>Hi " . htmlspecialchars($username) . ",</p>
                        <p>Your account has been created successfully. Please activate your email address before signing in.</p>
                        <p style='margin:30px 0;'>
                            <a href='" . htmlspecialchars($activationLink) . "' style='display:inline-block;background:#5097A4;color:#fff;text-decoration:none;padding:13px 22px;border-radius:9px;font-weight:700;'>
                                Activate My Account
                            </a>
                        </p>
                        <p>This activation link expires in 24 hours.</p>
                        <p style='font-size:13px;color:#6b8285;'>If you did not create this account, you can safely ignore this email.</p>
                    </div>
                ";

                if (sendMail($email, "Activate your " . SITE_NAME . " account", $bodyHtml)) {
                    $success = "Registration successful! Check your email and click the activation link before logging in.";
                } else {
                    $error = "Your account was created, but we could not send the activation email. Please contact support before trying to log in.";
                }
            } catch (Throwable $e) {
                error_log("ACTIVATION EMAIL ERROR: " . $e->getMessage());
                $error = "Your account was created, but the activation email could not be prepared. Please contact support.";
            }
        } else {
            $error = "Registration failed. Username or email might already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Greaselogs</title>
    <link rel="icon" type="image/png" href="/admin/assets/images/logo.png" sizes="32x32">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Greaselogs</span></h1>
                <p>Create your elite account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
