<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cryptomus Deposit</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>



<body class="dark-theme">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Fund<span>Wallet</span></h1>
                <p>Securely add funds to your Greaselog wallet</p>
            </div>
            

            <form action="process_cryptomus.php" method="POST" class="auth-form">
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
