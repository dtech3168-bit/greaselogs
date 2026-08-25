<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../NowPayments.php';

$pdo = db();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit("Invalid JSON");
}

$headers = getallheaders();
$receivedSignature = $headers['x-nowpayments-sig'] ?? $headers['X-Nowpayments-Sig'] ?? '';

$gateway = getPaymentGateway('nowpayments');
$ipnSecret = $gateway['extra_key'] ?? '';

if (!$ipnSecret || !NowPayments::verifyIpnSignature($raw, $receivedSignature, $ipnSecret)) {
    http_response_code(403);
    exit("Invalid signature");
}

$reference = $data['order_id'] ?? null;
$status    = $data['payment_status'] ?? null;

if (!$reference) {
    exit("Missing order_id");
}

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ?");
$stmt->execute([$reference]);
$tx = $stmt->fetch();

if (!$tx) {
    exit("Transaction not found");
}

if ($tx['status'] === 'success') {
    http_response_code(200);
    exit("Already processed");
}

// NOWPayments statuses: waiting, confirming, confirmed, sending, finished, failed, refunded, expired
if (in_array($status, ['finished', 'confirmed'], true)) {

    $amount = (float)$tx['amount'];

    $pdo->prepare("UPDATE transactions SET status='success', gateway_response=? WHERE reference=?")
        ->execute([$raw, $reference]);

    $userStmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $userStmt->execute([$tx['user_id']]);
    $currentBalance = (float)$userStmt->fetchColumn();
    $newBalance = $currentBalance + $amount;

    $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")
        ->execute([$newBalance, $tx['user_id']]);

    $pdo->prepare("
        INSERT INTO wallet_logs (user_id, transaction_id, amount, type, description, balance_after)
        VALUES (?, ?, ?, 'credit', 'Wallet Deposit via NowPayments', ?)
    ")->execute([$tx['user_id'], $tx['id'], $amount, $newBalance]);

} elseif (in_array($status, ['failed', 'expired', 'refunded'], true)) {
    $pdo->prepare("UPDATE transactions SET status='failed', gateway_response=? WHERE reference=?")
        ->execute([$raw, $reference]);
}

http_response_code(200);
echo "OK";
