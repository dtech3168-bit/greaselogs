<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$reference = $_GET['reference'] ?? '';
$transaction = new Transaction();
$tx = $reference ? $transaction->getTransactionByRef($reference) : null;

$status = $tx['status'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Received - Greaselogs</title>
<link rel="stylesheet" href="../assets/css/index.css">
</head>
<body class="dark-theme">
<div class="auth-container">
    <div class="auth-card" style="text-align:center;">
        <h1 class="logo">Greaselogs</h1>
        <?php if ($status === 'success'): ?>
            <p>Payment confirmed! Your wallet has been credited.</p>
        <?php else: ?>
            <p>We've received your payment and are waiting for network confirmation. This can take a few minutes for crypto payments — your wallet will be credited automatically once confirmed.</p>
        <?php endif; ?>
        <p><a href="dashboard.php" class="btn btn-primary btn-block">Go to Dashboard</a></p>
    </div>
</div>
</body>
</html>
