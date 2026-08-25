<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$pdo = db();
$orderId = (int)($_GET['order_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('pages/dashboard.php');
}

// product_id on a manual-listing order stores the raw listings.id
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? LIMIT 1");
$stmt->execute([$order['product_id']]);
$listing = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Successful - Greaselogs</title>
<link rel="stylesheet" href="../assets/css/index.css">
<style>
.receipt-card { max-width: 480px; margin: 60px auto; background:#fff; border-radius:16px; padding:30px; box-shadow:0 10px 25px rgba(0,0,0,0.08); }
.receipt-card h2 { color:#1f9d55; }
.cred-box { background:#f5f7fb; border-radius:10px; padding:15px; margin-top:15px; text-align:left; }
.cred-box p { margin:6px 0; word-break: break-all; }
.cred-box strong { color:#5097A4; }
</style>
</head>
<body>
<div class="receipt-card" style="text-align:center;">
    <h2>✔ Purchase Successful</h2>

    <?php if ($listing): ?>
        <p><?= htmlspecialchars($listing['title']) ?></p>

        <div class="cred-box">
            <p><strong>Username:</strong> <?= htmlspecialchars($listing['account_username'] ?? 'N/A') ?></p>
            <p><strong>Password:</strong> <?= htmlspecialchars($listing['account_password'] ?? 'N/A') ?></p>
            <?php if (!empty($listing['account_email'])): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($listing['account_email']) ?></p>
            <?php endif; ?>
        </div>

        <p style="font-size:13px;color:#999;margin-top:15px;">
            Save these details now — please change the account password immediately after login.
        </p>
    <?php else: ?>
        <p>Your order was recorded. Check your dashboard for details.</p>
    <?php endif; ?>

    <a href="dashboard.php" class="btn" style="display:inline-block;margin-top:20px;background:#5097A4;color:#fff;padding:12px 25px;border-radius:10px;text-decoration:none;">Go to Dashboard</a>
</div>
</body>
</html>
