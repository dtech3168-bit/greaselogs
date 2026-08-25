<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../api/Cryptomus.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    redirect('dashboard.php');
}

$pdo = db();

/**
* Get transaction
*/
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference=?");
$stmt->execute([$reference]);
$tx = $stmt->fetch();

if (!$tx || $tx['status'] === 'success') {
    redirect('dashboard.php');
}

/**
* Get API config
*/
$gateway = getPaymentGateway('cryptomus');

$crypto = new Cryptomus(
    $gateway['secret_key'],
    "https://api.cryptomus.com"
);

/**
* Verify payment
*/
$response = $crypto->getInvoice([
    "order_id" => $reference
]);

if (!empty($response['result']['payment_status']) &&
    $response['result']['payment_status'] === 'paid') {

    // update transaction
    $stmt = $pdo->prepare("
        UPDATE transactions SET status='success'
        WHERE reference=?
    ");
    $stmt->execute([$reference]);

    // credit wallet
    $stmt = $pdo->prepare("
        UPDATE users SET wallet = wallet + ?
        WHERE id = ?
    ");
    $stmt->execute([$tx['amount'], $tx['user_id']]);

    $_SESSION['success_msg'] = "Payment verified successfully!";
} else {
    $_SESSION['error_msg'] = "Payment not completed yet.";
}

redirect('pages/dashboard.php'); 
