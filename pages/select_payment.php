<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$paystack    = getPaymentGateway('paystack');
$cryptomus   = getPaymentGateway('cryptomus');
$korapay     = getPaymentGateway('korapay');
$nowpayments = getPaymentGateway('nowpayments');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Select Payment Method</title>

<link rel="stylesheet" href="../assets/css/index.css">

<style>
.payment-wrapper {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 80px;
    flex-wrap: wrap;
}

.payment-card {
    width: 220px;
    padding: 25px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
    cursor: pointer;
    transition: 0.3s ease;
}

.payment-card:hover {
    transform: translateY(-5px);
}

.payment-card img {
    width: 120px;
    height: auto;
    margin-bottom: 15px;
}

.payment-card h3 {
    margin: 0;
    font-size: 18px;
}
.payment-card {
    position: relative;
}

.payment-card::after {
    content: "Click to continue";
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    opacity: 0;
    transition: 0.3s;
}

.payment-card:hover::after {
    opacity: 0.6;
} 
h3 a {
    text-decoration: none;
}
</style>

</head>
<body>

<h2 style="text-align:center; margin-top:40px;">Choose Payment Method</h2>

<div class="payment-wrapper">

    <?php if (!empty($paystack['enabled'])): ?>
    <a href="deposit.php" class="payment-card">
        <img src="/../assets/images/paystack.jpg" alt="Paystack">
        <h3>Paystack</h3>
    </a>
    <?php endif; ?>

    <?php if (!empty($cryptomus['enabled'])): ?>
    <a href="deposit_cryptomus.php" class="payment-card">
        <img src="/../assets/images/cryptomus.jpg" alt="Cryptomus">
        <h3>Cryptomus</h3>
    </a>
    <?php endif; ?>

    <?php if (!empty($korapay['enabled'])): ?>
    <a href="deposit_korapay.php" class="payment-card">
        <h3>Korapay</h3>
        <p style="font-size:13px;color:#777;">Card / Bank Transfer</p>
    </a>
    <?php endif; ?>

    <?php if (!empty($nowpayments['enabled'])): ?>
    <a href="deposit_nowpayments.php" class="payment-card">
        <h3>NowPayments</h3>
        <p style="font-size:13px;color:#777;">Pay with Crypto</p>
    </a>
    <?php endif; ?>

</div>

</body>
</html>
