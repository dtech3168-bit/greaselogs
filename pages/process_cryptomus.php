<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../api/Cryptomus.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$amount = floatval($_POST['amount'] ?? 0);

if ($amount < 100) {
    die("Minimum deposit is ₦100");
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$reference = generateTransactionRef();

/**
* 1. Save transaction FIRST
*/
$tx = new Transaction();
$tx->createTransaction(
    $user_id,
    'deposit',
    $amount,
    $reference,
    'cryptomus'
);

/**
* 2. Init Cryptomus
*/
$cryptomusSettings = getPaymentGateway('cryptomus');

if (!$cryptomusSettings || !$cryptomusSettings['secret_key']) {
    die("Cryptomus API not configured");
}

$cryptomus = new Cryptomus(
    $cryptomusSettings['secret_key'],
    "https://api.cryptomus.com"
); 


/**
* 3. Create invoice
*/
$response = $cryptomus->createInvoice([
    "amount" => (string)$amount,
    "currency" => "NGN",
    "order_id" => $reference,
    "url_callback" => SITE_URL . "/pages/webhook_cryptomus.php",
    "url_return" => SITE_URL . "/pages/dashboard.php",
]);

/**
* 4. Redirect user
*/
if (!empty($response['result']['url'])) {
    header("Location: " . $response['result']['url']);
    exit();
    
}

die("Payment initialization failed"); 

