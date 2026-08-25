<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

$pdo = db();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit("Invalid JSON");
}

$headers = getallheaders();
$receivedSignature = $headers['x-korapay-signature'] ?? $headers['X-Korapay-Signature'] ?? '';

$gateway = getPaymentGateway('korapay');
$secret = $gateway['secret_key'] ?? '';

if (!$secret) {
    http_response_code(500);
    exit("Gateway not configured");
}

// Korapay signs the "data" object with HMAC-SHA256 using your secret key
$eventData = $data['data'] ?? [];
$calculatedSignature = hash_hmac('sha256', json_encode($eventData), $secret);

if (!hash_equals($calculatedSignature, $receivedSignature)) {
    http_response_code(403);
    exit("Invalid signature");
}

$reference = $eventData['reference'] ?? null;
$status    = $eventData['status'] ?? null;

if (!$reference) {
    exit("Missing reference");
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

if ($status === 'success') {

    $amount = (float)($eventData['amount'] ?? $tx['amount']);

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
        VALUES (?, ?, ?, 'credit', 'Wallet Deposit via Korapay (webhook)', ?)
    ")->execute([$tx['user_id'], $tx['id'], $amount, $newBalance]);

} elseif ($status === 'failed') {
    $pdo->prepare("UPDATE transactions SET status='failed', gateway_response=? WHERE reference=?")
        ->execute([$raw, $reference]);
}

http_response_code(200);
echo "OK";
