<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = floatval($_POST['amount']);

    if ($amount < 100) {
        $error = "Minimum deposit is ₦100.";
    } else {

        $user_id = $_SESSION['user_id'];
        $email   = $_SESSION['email'] ?? '';

        // 🔴 Validate email (VERY IMPORTANT)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid user email. Please login again.";
        } else {

            $reference = generateTransactionRef();

            $transaction = new Transaction();
            $transaction_id = $transaction->createTransaction(
                $user_id,
                'deposit',
                $amount,
                $reference,
                'paystack'
            );

            if (!$transaction_id) {
                $error = "Failed to create transaction.";
            } else {

                // 🔥 PAYSTACK REQUEST (FIXED)
                $payload = [
                    "email" => $email,
                    "amount" => (int)($amount * 100),
                    "reference" => $reference,
                    "callback_url" => SITE_URL . "/pages/verify_payment.php"
                ];

                $paystack = getPaymentGateway('paystack');
$secretKey = $paystack['secret_key'] ?? '';

if (!$secretKey) {
    $error = "Payment gateway not configured.";
} else {

    $url = "https://api.paystack.co/transaction/initialize";

    $fields = [
        'email' => $email,
        'amount' => $amount * 100,
        'reference' => $reference,
        'callback_url' => SITE_URL . '/pages/verify_payment.php'
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $secretKey,
            "Cache-Control: no-cache"
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
    } else {
        $response = json_decode($result, true);

        if (!empty($response['status'])) {
            header('Location: ' . $response['data']['authorization_url']);
            exit();
        } else {
            $error = $response['message'] ?? "Payment initialization failed";
        }
    }

    curl_close($ch);
} 
            }
        }
    }
}
?>

<!-- UI -->
<?php if (isset($_GET['pending'])): ?>
    <div class="alert alert-info">
        You need to fund your wallet to complete your purchase.
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Funds - DeluxeSocial</title>
    <link rel="icon" type="image/png" href="/admin/assets/images/logo.png" sizes="32x32">
    
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>.alert-info {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}


</style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Fund<span>Wallet</span></h1>
                <p>Securely add funds to your DeluxeSocial wallet</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="deposit.php" method="POST" class="auth-form">
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
