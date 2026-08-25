<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/User.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    redirect('pages/dashboard.php');
}

$transaction = new Transaction();
$user = new User();

$trans_data = $transaction->getTransactionByRef($reference);

if (!$trans_data || $trans_data['status'] !== 'pending') {
    redirect('pages/dashboard.php');
}

/**
* 🔥 GET PAYSTACK SECRET FROM DB (NOT CONSTANT)
*/
$paystack = getPaymentGateway('paystack');
$secretKey = $paystack['secret_key'] ?? '';

if (!$secretKey) {
    $_SESSION['error_msg'] = "Paystack not configured.";
    redirect('pages/dashboard.php');
}

/**
* 🔥 VERIFY WITH PAYSTACK
*/
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $secretKey,
        "Cache-Control: no-cache",
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$result = curl_exec($ch);
$httpError = curl_error($ch);
curl_close($ch);

if ($httpError) {
    $_SESSION['error_msg'] = "Network error: " . $httpError;
    redirect('pages/dashboard.php');
}

$response = json_decode($result, true);

if (
    !$response ||
    !isset($response['status']) ||
    !$response['status'] ||
    ($response['data']['status'] ?? '') !== 'success'
) {
    $transaction->updateTransactionStatus($reference, 'failed', $result);

    $_SESSION['error_msg'] = $response['message'] ?? "Payment verification failed.";
    redirect('pages/dashboard.php');
}

/**
* 🔥 SUCCESS PAYMENT
*/
$amount = $response['data']['amount'] / 100;
$user_id = $trans_data['user_id'];

$transaction->updateTransactionStatus($reference, 'success', $result);

$new_balance = $user->updateWalletBalance($user_id, $amount, 'credit');

$transaction->logWalletActivity(
    $user_id,
    $trans_data['id'],
    $amount,
    'credit',
    'Wallet Deposit via Paystack',
    $new_balance
);

$_SESSION['success_msg'] = "Wallet funded successfully!";

/**
* If purchase pending, continue flow
*/
if (isset($_SESSION['pending_purchase'])) {
    redirect('complete_purchase.php');
}

redirect('pages/dashboard.php'); 
