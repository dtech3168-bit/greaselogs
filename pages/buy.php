<?php
/**
 * Greaselogs - Unified purchase handler
 * Handles both AcctShop API products (numeric ID) and
 * admin-uploaded manual listings (ID prefixed with 'm').
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../api/acctshop.php';
require_once __DIR__ . '/../classes/Listing.php';
require_once __DIR__ . '/../classes/Transaction.php';

/* =========================
   LOGIN CHECK
========================= */
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect('pages/login.php');
    exit;
}

/* =========================
   INPUT VALIDATION
========================= */
if (!isset($_POST['product_id'])) {
    header('Location: listings.php');
    exit;
}

$product_id = sanitize($_POST['product_id']);
$pdo = db();
$user_id = $_SESSION['user_id'];

/* =========================
   MANUAL (ADMIN-UPLOADED) LISTING PURCHASE
========================= */
if (substr($product_id, 0, 1) === 'm') {

    $listingId = (int) substr($product_id, 1);
    $listingObj = new Listing();
    $listing = $listingObj->getListingById($listingId);

    if (!$listing || $listing['status'] !== 'available') {
        $_SESSION['error_msg'] = "This listing is no longer available.";
        redirect('pages/listings.php');
        exit;
    }

    $price = (float)$listing['price'];

    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $balance = (float)$stmt->fetchColumn();

    if ($balance < $price) {
        $_SESSION['pending_purchase'] = ['product_id' => $product_id];
        redirect('pages/deposit.php?pending=1');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock the listing row to prevent two users buying it at once
        $stmt = $pdo->prepare("SELECT status FROM listings WHERE id = ? FOR UPDATE");
        $stmt->execute([$listingId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus !== 'available') {
            throw new Exception("This listing was just purchased by someone else.");
        }

        // Deduct wallet
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
            ->execute([$price, $user_id]);

        // Mark listing sold
        $pdo->prepare("UPDATE listings SET status = 'sold' WHERE id = ?")
            ->execute([$listingId]);

        // Record order with delivered credentials
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, product_id, amount, status, created_at)
            VALUES (?, ?, ?, 'completed', NOW())
        ");
        $stmt->execute([$user_id, $listingId, $price]);
        $orderId = $pdo->lastInsertId();

        $reference = generateTransactionRef();
        $transaction = new Transaction();
        $txId = $transaction->createTransaction($user_id, 'purchase', $price, $reference, 'wallet');
        $transaction->updateTransactionStatus($reference, 'success', json_encode([
            'listing_id' => $listingId,
            'title' => $listing['title'],
        ]));

        $newBalance = $balance - $price;
        $transaction->logWalletActivity($user_id, $txId, $price, 'debit', 'Purchase: ' . $listing['title'], $newBalance);

        $pdo->commit();

        $_SESSION['success_msg'] = "Purchase successful! Your account details are below.";
        $_SESSION['last_order_id'] = $orderId;

        redirect('pages/order_success.php?order_id=' . $orderId);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Manual listing purchase failed: " . $e->getMessage());
        $_SESSION['error_msg'] = $e->getMessage() ?: "Purchase failed. Please try again.";
        redirect('pages/listings.php');
        exit;
    }
}

/* =========================
   ACCTSHOP API PRODUCT PURCHASE
========================= */
$product_id = (int) $product_id;
$api = new AcctShopAPI();
$response = $api->getProduct($product_id);

if (!$response || !isset($response['product'])) {
    $_SESSION['error_msg'] = "Product not found or temporarily unavailable.";
    redirect('pages/listings.php');
    exit;
}

$product = $response['product'];

$settings = getSettings();
$global_markup = (float)($settings['markup'] ?? 0);

$basePrice = $product['price'] ?? $product['cost'] ?? $product['amount'] ?? 0;
$price = (float)$basePrice + $global_markup;
$product_name = $product['name'] ?? ('Product #' . $product_id);

$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = (float)$stmt->fetchColumn();

if ($balance < $price) {
    $_SESSION['pending_purchase'] = ['product_id' => $product_id];
    redirect('pages/deposit.php?pending=1');
    exit;
}

try {
    $pdo->beginTransaction();

    // Deduct wallet up front
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
        ->execute([$price, $user_id]);

    // Call AcctShop to actually fulfil the order
    $buy = $api->buyProduct($product_id, 1);

    if (!$buy || !isset($buy['status']) || $buy['status'] !== 'success') {
        throw new Exception($buy['message'] ?? 'Supplier could not fulfil this order right now.');
    }

    $reference = generateTransactionRef();
    $transaction = new Transaction();
    $txId = $transaction->createTransaction($user_id, 'purchase', $price, $reference, 'wallet');
    $transaction->updateTransactionStatus($reference, 'success', json_encode($buy));

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, product_id, amount, status, created_at)
        VALUES (?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$user_id, $product_id, $price]);
    $orderId = $pdo->lastInsertId();

    $newBalance = $balance - $price;
    $transaction->logWalletActivity($user_id, $txId, $price, 'debit', 'Purchase: ' . $product_name, $newBalance);

    $pdo->commit();

    redirect('pages/dashboard.php?success=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("API product purchase failed: " . $e->getMessage());
    $_SESSION['error_msg'] = "⚠️ Purchase temporarily unavailable. Please contact support or try again later.";
    redirect('pages/listings.php');
    exit;
}
