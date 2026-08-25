<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = db();
$success = '';
$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $username === '') {
        $error = "Please provide a valid username, email, and a password of at least 6 characters.";
    } else {

        $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);

        if ($stmt->fetch()) {
            $error = "That username or email is already in use.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin (role, username, email, password) VALUES ('developer', ?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $success = "Developer account created.";
        }
    }
}

$developers = $pdo->query("SELECT * FROM admin WHERE role = 'developer' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Accounts - Greaselogs Admin</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo"><img src="/assets/images/logo.png" alt="Greaselogs Admin"></a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="listings.php">Listings</a></li>
                <li><a href="developers.php" class="active">Developers</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Developer <span>Accounts</span></h1>
                <p>Create logins for the <a href="../developer/login.php" style="color:#00f0ff;">Developer Console</a> (system monitoring, API health, error logs).</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="dashboard-card mb-40">
                <div class="card-header"><h3>Add Developer</h3></div>
                <div class="card-body">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <div class="form-row" style="display:flex;gap:15px;flex-wrap:wrap;">
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label>Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label>Password</label>
                                <input type="password" name="password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Developer Account</button>
                    </form>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header"><h3>Existing Developers</h3></div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Username</th><th>Email</th><th>Created</th></tr></thead>
                        <tbody>
                            <?php if ($developers): foreach ($developers as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['username']) ?></td>
                                    <td><?= htmlspecialchars($d['email']) ?></td>
                                    <td><?= htmlspecialchars($d['created_at']) ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" class="text-center">No developer accounts yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
