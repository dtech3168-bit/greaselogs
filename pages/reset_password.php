<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

/* =========================
   VALIDATE TOKEN
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE token = ?
    AND expires_at > ?
    LIMIT 1
");

$stmt->execute([$token, date('Y-m-d H:i:s')]);
$reset = $stmt->fetch();

if (!$reset) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Link Expired</title>
    <style>body{font-family:Arial;background:#f5f7fb;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
    .card{background:#fff;padding:30px;width:350px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);text-align:center;}
    a{display:inline-block;margin-top:15px;background:#5097A4;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;}</style>
    </head><body><div class="card"><h2>Link Expired</h2>
    <p>This password reset link is invalid or has expired.</p>
    <a href="forgot_password.php">Request a new code</a></div></body></html>';
    exit();
}

/* =========================
   HANDLE PASSWORD RESET
========================= */
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // update password
        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE email = ?
        ");
        $stmt->execute([$hash, $reset['email']]);

        // delete reset record
        $pdo->prepare("
            DELETE FROM password_resets
            WHERE email = ?
        ")->execute([$reset['email']]);

        // redirect safely
        header("Location: login.php?reset=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>

<style>
body {
    font-family: Arial;
    background: #f5f7fb;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.card {
    background: #fff;
    padding: 30px;
    width: 350px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
}
input {
    width: 100%;
    padding: 12px;
    margin: 15px 0;
    border: 1px solid #ddd;
    border-radius: 8px;
}
button {
    width: 100%;
    padding: 12px;
    background: #5097A4;
    border: none;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
}
.error {
    color: red;
    font-size: 14px;
}
</style>
</head>

<body>

<div class="card">

    <h2>Reset Password</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <input type="password" name="password" placeholder="New password" required>

        <button type="submit">Update Password</button>

    </form>

</div>

</body>
</html> 
