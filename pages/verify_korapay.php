<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../api/Korapay.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    redirect('pages/dashboard.php');
}

$transaction = new Transaction();
$user = new User();

$tx = $transaction->getTransactionByRef($reference);

if (!$tx || $tx['status'] !== 'pending') {
    redirect('pages/dashboard.php');
}

$gateway = getPaymentGateway('korapay');
$secretKey = $gateway['secret_key'] ?? '';

if (!$secretKey) {
    $_SESSION['error_msg'] = "Korapay not configured.";
    redirect('pages/dashboard.php');
}

$korapay = new Korapay($secretKey);
$response = $korapay->verifyCharge($reference);

$status = $response['data']['status'] ?? '';

if (!empty($response['status']) && $status === 'success') {

    $transaction->updateTransactionStatus($reference, 'success', json_encode($response));

    $amount = (float)($response['data']['amount'] ?? $tx['amount']);
    $new_balance = $user->updateWalletBalance($tx['user_id'], $amount, 'credit');

    $transaction->logWalletActivity(
        $tx['user_id'],
        $tx['id'],
        $amount,
        'credit',
        'Wallet Deposit via Korapay',
        $new_balance
    );

    $_SESSION['success_msg'] = "Payment verified successfully!";

} elseif ($status === 'failed') {

    $transaction->updateTransactionStatus($reference, 'failed', json_encode($response));
    $_SESSION['error_msg'] = "Payment failed.";

} else {
    $_SESSION['error_msg'] = "Payment not completed yet. If you were charged, it will reflect shortly.";
}

if (isset($_SESSION['pending_purchase'])) {
    redirect('complete_purchase.php');
}

redirect('pages/dashboard.php');
