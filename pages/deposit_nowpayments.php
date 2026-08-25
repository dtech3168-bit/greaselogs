<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../api/NowPayments.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $amount = floatval($_POST['amount'] ?? 0);

    if ($amount < 100) {
        $error = "Minimum deposit is ₦100.";
    } else {

        $user_id = $_SESSION['user_id'];

        $gateway = getPaymentGateway('nowpayments');
        $apiKey = $gateway['secret_key'] ?? '';

        if (!$apiKey) {
            $error = "NowPayments is not configured yet.";
        } else {

            $reference = generateTransactionRef();

            $transaction = new Transaction();
            $transaction->createTransaction($user_id, 'deposit', $amount, $reference, 'nowpayments');

            $now = new NowPayments($apiKey);

            // NOWPayments prices invoices in fiat (usd); the customer
            // picks their crypto of choice on the hosted invoice page.
            $usdAmount = round(convertCurrency($amount, 'NGN', 'USD'), 2);

            $response = $now->createInvoice([
                'price_amount'      => $usdAmount > 0 ? $usdAmount : $amount,
                'price_currency'    => 'usd',
                'order_id'          => $reference,
                'order_description' => 'Greaselogs wallet funding',
                'ipn_callback_url'  => SITE_URL . '/api/webhooks/nowpayments.php',
                'success_url'       => SITE_URL . '/pages/nowpayments_return.php?reference=' . urlencode($reference),
                'cancel_url'        => SITE_URL . '/pages/dashboard.php',
            ]);

            if (!empty($response['invoice_url'])) {
                header('Location: ' . $response['invoice_url']);
                exit();
            }

            $error = $response['message'] ?? "Payment initialization failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NowPayments Deposit - Greaselogs</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Fund<span>Wallet</span></h1>
                <p>Pay with crypto via NowPayments</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="amount">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" required placeholder="Enter amount" min="100">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Proceed to Payment</button>
            </form>

            <div class="auth-footer">
                <p><a href="dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
