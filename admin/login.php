<?php
/**
* DeluxeSocial - Admin Login Page (CLEAN & SAFE)
*/

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

/* =========================
   HANDLE LOGIN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // 🔐 CSRF CHECK (ONLY ON POST)
        verifyCSRFToken($_POST['csrf_token'] ?? '');

        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "All fields are required.";
        } else {

            $pdo = db();

            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            $admin = $stmt->fetch();

            $lockedSeconds = getLockoutSecondsRemaining($admin);

            if ($lockedSeconds > 0) {

                $error = "Too many failed attempts. Try again in " . ceil($lockedSeconds / 60) . " minute(s).";

            } elseif ($admin && password_verify($password, $admin['password'])) {

                clearFailedLogins('admin', $admin['id']);

                // Prevent session fixation
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                redirect('admin/index.php');

            } else {

                if ($admin) {
                    registerFailedLogin('admin', $admin['id']);
                }

                $error = "Invalid admin credentials.";
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/* =========================
   CSRF TOKEN (ALWAYS READY)
========================= */
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DeluxeSocial</title>

    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body class="dark-theme">

<div class="auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <h1>Admin<span>Panel</span></h1>
            <p>Login to manage system</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form method="POST" class="auth-form">

            <!-- CSRF TOKEN (MUST BE INSIDE FORM) -->
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Enter admin email">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Login
            </button>

        </form>

        <div class="auth-footer">
            <a href="/admin/index.php">Back to site</a>
        </div>

    </div>
</div>

</body>
</html> 
