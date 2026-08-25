<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../api/Korapay.php';

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
        $email   = $_SESSION['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid user email. Please login again.";
        } else {

            $gateway = getPaymentGateway('korapay');
            $secretKey = $gateway['secret_key'] ?? '';

            if (!$secretKey) {
                $error = "Korapay is not configured yet.";
            } else {

                $reference = generateTransactionRef();

                $transaction = new Transaction();
                $transaction->createTransaction($user_id, 'deposit', $amount, $reference, 'korapay');

                $korapay = new Korapay($secretKey);

                $response = $korapay->initializeCharge([
                    'amount'           => $amount,
                    'currency'         => 'NGN',
                    'reference'        => $reference,
                    'customer_email'   => $email,
                    'customer_name'    => $_SESSION['username'] ?? $email,
                    'redirect_url'     => SITE_URL . '/pages/verify_korapay.php?reference=' . urlencode($reference),
                    'notification_url' => SITE_URL . '/api/webhooks/korapay.php',
                ]);

                if (!empty($response['status']) && !empty($response['data']['checkout_url'])) {
                    header('Location: ' . $response['data']['checkout_url']);
                    exit();
                }

                $error = $response['message'] ?? "Payment initialization failed";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Korapay Deposit - Greaselogs</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Fund<span>Wallet</span></h1>
                <p>Pay via Korapay (card / bank transfer)</p>
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
