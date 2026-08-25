<?php
/**
 * DeluxeSocial - Checkout Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Listing.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Transaction.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$listing_id = $_POST['listing_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$userObj = new User();
$listingObj = new Listing();
$orderObj = new Order();
$transObj = new Transaction();

$user_data = $userObj->getUserById($user_id);
$listing = $listingObj->getListingById($listing_id);

if (!$listing || $listing['status'] !== 'available') {
    $_SESSION['error_msg'] = "Listing is no longer available.";
    redirect('pages/listings.php');
}

$error = '';
$success = '';

if (isset($_POST['confirm_purchase'])) {
    $amount = $listing['price'];
    
    if ($user_data['wallet_balance'] < $amount) {
        $error = "Insufficient wallet balance. Please fund your wallet.";
    } else {
        // Start Transaction
        $db = new Database();
        $db->beginTransaction();
        
        try {
            // 1. Deduct from wallet
            $new_balance = $userObj->updateWalletBalance($user_id, $amount, 'debit');
            
            // 2. Create Transaction Log
            $reference = generateTransactionRef();
            $trans_id = $transObj->createTransaction($user_id, 'purchase', $amount, $reference, 'Wallet');
            $transObj->updateTransactionStatus($reference, 'success', 'Wallet Purchase');
            $transObj->logWalletActivity($user_id, $trans_id, $amount, 'debit', 'Purchase: ' . $listing['title'], $new_balance);
            
            // 3. Create Order
            $order_id = $orderObj->createOrder($user_id, $listing_id, $amount);
            
            // 4. Mark Listing as Sold
            $listingObj->markAsSold($listing_id);
            
            $db->commit();
            
            $_SESSION['success_msg'] = "Purchase successful! Your account details are ready.";
            redirect('pages/order_success.php?id=' . $order_id);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "An error occurred during purchase. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DeluxeSocial</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Confirm<span>Purchase</span></h1>
                <p>Review your order details</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="checkout-details">
                <div class="checkout-item">
                    <span>Account:</span>
                    <strong><?php echo $listing['title']; ?></strong>
                </div>
                <div class="checkout-item">
                    <span>Platform:</span>
                    <strong><?php echo $listing['platform']; ?></strong>
                </div>
                <div class="checkout-item">
                    <span>Price:</span>
                    <strong class="price"><?php echo formatCurrency($listing['price']); ?></strong>
                </div>
                <hr>
                <div class="checkout-item">
                    <span>Your Balance:</span>
                    <strong><?php echo formatCurrency($user_data['wallet_balance']); ?></strong>
                </div>
            </div>

            <form action="checkout.php" method="POST" class="auth-form mt-20">
                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                <button type="submit" name="confirm_purchase" class="btn btn-primary btn-block">Confirm & Pay</button>
            </form>
            
            <div class="auth-footer">
                <p><a href="listing.php?id=<?php echo $listing['id']; ?>">Cancel & Go Back</a></p>
            </div>
        </div>
    </div>
</body>
</html>
