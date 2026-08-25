<?php
/**
 * DeluxeSocial - User Login Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $userRow = $stmt->fetch();

        $lockedSeconds = getLockoutSecondsRemaining($userRow);

        if ($lockedSeconds > 0) {

            $error = "Too many failed attempts. Try again in " . ceil($lockedSeconds / 60) . " minute(s).";

        } else {

            $user = new User();
            $user_data = $user->login($email, $password);

            if ($user_data) {

                if (empty($user_data['email_verified_at'])) {
                    $error = "Please activate your account from the link sent to your email before logging in.";
                } else {
                    clearFailedLogins('users', $user_data['id']);

                    // Prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['username'] = $user_data['username'];
                    $_SESSION['email'] = $user_data['email'];

                    redirect('pages/dashboard.php');
                }

            } else {

                if ($userRow) {
                    registerFailedLogin('users', $userRow['id']);
                }

                $error = "Invalid email or password.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DeluxeSocial</title>
    <link rel="icon" type="image/png" href="/admin/assets/images/logo.png" sizes="32x32">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Greaselogs</h1>
                <p>Login to your elite account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <p style="font-size:13px;color:#6B8285;margin-bottom:18px;">New accounts must be activated from the email link before dashboard access is enabled.</p>
            <form action="login.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot_password.php">Forgot password?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
