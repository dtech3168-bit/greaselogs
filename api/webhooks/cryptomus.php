<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pdo = db();

/**
* Get raw body
*/
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

/**
* Get signature from header
*/
$headers = getallheaders();
$receivedSign = $headers['sign'] ?? $headers['Sign'] ?? '';

/**
* Get secret key from DB
*/
$gateway = getPaymentGateway('cryptomus');
$secret = $gateway['secret_key'] ?? '';

/**
* Generate our own signature
*/
$calculatedSign = md5(base64_encode($raw) . $secret);

/**
* VERIFY SIGNATURE
*/
if ($calculatedSign !== $receivedSign) {
    http_response_code(403);
    exit("Invalid signature");
} 

/**
* Extract values
*/
$order_id = $data['order_id'] ?? null;
$status   = $data['status'] ?? null;
$paid_amount = (float)($data['amount'] ?? 0);

/**
* Find transaction
*/
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ?");
$stmt->execute([$order_id]);
$tx = $stmt->fetch();

if (!$tx) {
    exit("Transaction not found");
}

/**
* Validate amount
*/
if ($paid_amount < $tx['amount']) {
    exit("Amount mismatch");
} 

/**
* Log for debugging (VERY IMPORTANT)
*/
file_put_contents(__DIR__ . '/log.txt', $raw . PHP_EOL, FILE_APPEND);

/**
* Basic validation
*/
if (!$data) {
    http_response_code(400);
    exit("Invalid JSON");
}

$order_id = $data['order_id'] ?? null;
$status   = $data['status'] ?? null;

/**
* Must have reference
*/
if (!$order_id) {
    exit("Missing order_id");
}

/**
* Find transaction
*/
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ?");
$stmt->execute([$order_id]);
$tx = $stmt->fetch();

if (!$tx) {
    exit("Transaction not found");
}

/**
* Prevent double processing
*/
if ($tx['status'] === 'success') {
    exit("Already processed");
}

/**
* SUCCESS PAYMENT
*/
if ($status === 'paid' || $status === 'success') {

    /**
     * Update transaction
     */
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET status = 'success', gateway_response = ?
        WHERE reference = ?
    ");
    $stmt->execute([$raw, $order_id]);

    /**
     * Credit wallet
     */
    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet = wallet + ?
        WHERE id = ?
    ");
    $stmt->execute([
        $tx['amount'],
        $tx['user_id']
    ]);
}

http_response_code(200);
echo "OK"; 


if ($status === 'paid' || $status === 'success') {

    if ($tx['status'] === 'success') {
        exit("Already processed");
    }

    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status='success', gateway_response=? 
        WHERE reference=?
    ");
    $stmt->execute([$raw, $order_id]);

    $stmt = $pdo->prepare("
        UPDATE users 
        SET wallet = wallet + ?
        WHERE id = ?
    ");
    $stmt->execute([$tx['amount'], $tx['user_id']]);
}
