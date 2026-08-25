<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../api/acctshop.php';

if (!isLoggedIn()) {
    die("Login required");
}

if (!isset($_SESSION['pending_purchase'])) {
    redirect('dashboard.php');
}

$data = $_SESSION['pending_purchase'];
$product_id = $data['product_id'];

unset($_SESSION['pending_purchase']);

// =======================
// 🔍 GET PRODUCT FROM SESSION
// =======================
$products = $_SESSION['marketplace_products'] ?? [];

$product = null;

foreach ($products as $p) {
    if ((string)$p['id'] === (string)$product_id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    die("Product not found");
}

// =======================
// 🔥 HYBRID PRICING SYSTEM
// =======================
$settings = getSettings();
$productPricing = getProductPricing($product_id);

$basePrice = (float)$product['price'];
$markup = 0;
$price = 0;

// PRIORITY 1 → custom price
if (!empty($productPricing['custom_price'])) {
    $price = (float)$productPricing['custom_price'];
    $markup = $price - $basePrice;
}

// PRIORITY 2 → custom markup
elseif (!empty($productPricing['custom_markup'])) {
    $markup = (float)$productPricing['custom_markup'];
    $price = $basePrice + $markup;
}

// DEFAULT → global markup
else {
    $markup = (float)($settings['markup'] ?? 0);
    $price = $basePrice + $markup;
}

// =======================
// 💾 DATABASE (PDO)
// =======================
$pdo = db();

// check wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found");
}

if ($user['wallet_balance'] < $price) {
    die("Insufficient balance");
}

try {

    $pdo->beginTransaction();

    // =======================
    // 💸 DEDUCT WALLET
    // =======================
    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet_balance = wallet_balance - ?
        WHERE id = ?
    ");
    $stmt->execute([$price, $_SESSION['user_id']]);

    // =======================
    // 🔌 API PURCHASE
    // =======================
    $api = new AcctShopAPI();
    $buy = $api->buyProduct($product_id, 1);

    if (!isset($buy['status']) || $buy['status'] !== 'success') {
        throw new Exception("API failed: " . json_encode($buy));
    }

    // =======================
    // 🧾 SAVE TRANSACTION
    // =======================
    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (user_id, product_id, amount, markup, status)
        VALUES (?, ?, ?, ?, 'success')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $product_id,
        $price,
        $markup
    ]);

    $pdo->commit();

    redirect('dashboard.php?success=auto_purchase');

} catch (Exception $e) {

    $pdo->rollBack();

    // Optional: log error instead of showing raw message in production
    echo "Transaction failed: " . $e->getMessage();
} 
