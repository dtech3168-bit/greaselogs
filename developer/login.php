<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isDeveloperLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND role = 'developer' LIMIT 1");
        $stmt->execute([$email]);
        $dev = $stmt->fetch();

        $lockedSeconds = getLockoutSecondsRemaining($dev);

        if ($lockedSeconds > 0) {

            $error = "Too many failed attempts. Try again in " . ceil($lockedSeconds / 60) . " minute(s).";

        } elseif ($dev && password_verify($password, $dev['password'])) {

            clearFailedLogins('admin', $dev['id']);
            session_regenerate_id(true);

            $_SESSION['developer_id'] = $dev['id'];
            $_SESSION['developer_username'] = $dev['username'];

            header('Location: index.php');
            exit();

        } else {

            if ($dev) {
                registerFailedLogin('admin', $dev['id']);
            }
            $error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Developer Access // Greaselogs</title>
<style>
:root {
    --neon-cyan: #00f0ff;
    --neon-purple: #a855f7;
    --bg-deep: #05060a;
    --panel: rgba(15, 18, 28, 0.75);
}
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Courier New', monospace;
    background:
        radial-gradient(circle at 20% 20%, rgba(0,240,255,0.08), transparent 40%),
        radial-gradient(circle at 80% 80%, rgba(168,85,247,0.08), transparent 40%),
        var(--bg-deep);
    background-attachment: fixed;
    color: #e6f7f9;
    overflow: hidden;
    position: relative;
}
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,240,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,240,255,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}
.login-card {
    position: relative;
    width: 360px;
    padding: 40px 32px;
    background: var(--panel);
    border: 1px solid rgba(0,240,255,0.25);
    border-radius: 14px;
    backdrop-filter: blur(14px);
    box-shadow: 0 0 40px rgba(0,240,255,0.12), inset 0 0 40px rgba(168,85,247,0.06);
    text-align: center;
    z-index: 1;
}
.login-card h1 {
    font-size: 20px;
    letter-spacing: 3px;
    margin: 0 0 6px;
    color: var(--neon-cyan);
    text-shadow: 0 0 12px rgba(0,240,255,0.6);
}
.login-card p.sub {
    font-size: 12px;
    color: #7d93a3;
    margin-bottom: 25px;
    letter-spacing: 1px;
}
.field { text-align: left; margin-bottom: 16px; }
.field label { display:block; font-size: 11px; color: var(--neon-cyan); letter-spacing: 1px; margin-bottom: 6px; }
.field input {
    width: 100%;
    padding: 11px 12px;
    background: rgba(0,0,0,0.35);
    border: 1px solid rgba(0,240,255,0.2);
    border-radius: 8px;
    color: #e6f7f9;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: 0.2s;
}
.field input:focus {
    border-color: var(--neon-cyan);
    box-shadow: 0 0 12px rgba(0,240,255,0.35);
}
button {
    width: 100%;
    padding: 12px;
    margin-top: 8px;
    background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
    border: none;
    border-radius: 8px;
    color: #05060a;
    font-weight: bold;
    letter-spacing: 1px;
    cursor: pointer;
    transition: 0.2s;
}
button:hover { filter: brightness(1.15); box-shadow: 0 0 20px rgba(0,240,255,0.4); }
.error-box {
    background: rgba(255,60,60,0.12);
    border: 1px solid rgba(255,60,60,0.4);
    color: #ff8080;
    font-size: 12px;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.status-dot {
    width: 8px; height: 8px; border-radius: 50%; background: var(--neon-cyan);
    display: inline-block; margin-right: 6px; box-shadow: 0 0 8px var(--neon-cyan);
    animation: blink 1.6s infinite;
}
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.3;} }
</style>
</head>
<body>

<div class="login-card">
    <h1>&lt;DEV_ACCESS/&gt;</h1>
    <p class="sub"><span class="status-dot"></span>SYSTEM MONITOR AUTHENTICATION</p>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="field">
            <label>EMAIL</label>
            <input type="email" name="email" required autocomplete="off">
        </div>
        <div class="field">
            <label>PASSWORD</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">AUTHENTICATE</button>
    </form>
</div>

</body>
</html>
