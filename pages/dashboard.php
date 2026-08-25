<?php
/**
 * Greaselogs - User Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Transaction.php';

if (!isLoggedIn()) {
    redirect('pages/login.php');
}

$userObj = new User();
$transObj = new Transaction();

$user_id = $_SESSION['user_id'];
$user_data = $userObj->getUserById($user_id);
$transactions = $transObj->getUserTransactions($user_id);

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Greaselogs</title>
    <link rel="icon" type="image/png" href="/../assets/images/logo.png" sizes="32x32">
    <link rel="stylesheet" href="/../assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
   <!-- Navbar -->
<nav class="navbar">
    <div class="container nav-wrapper">

        <!-- LOGO (IMAGE SUPPORT) -->
        <a href="/../index.php" class="logo">
            <img src="/../assets/images/logo.png" alt="DeluxeSocial">
        </a>

        <!-- HAMBURGER -->
        <div class="hamburger">
    ☰
</div> 


        <!-- MENU -->
        <ul class="nav-links" id="navMenu">
            <li><a href="listings.php">Marketplace</a></li>

            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                
                <!--<li><a href="login.php" onclick="return confirn('Logout?')">Login</a></li>-->
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>

    </div>
</nav> 



    <!-- Dashboard Content -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome, <span><?php echo $user_data['username']; ?></span></h1>
                <p>Manage your elite account and transactions.</p>
            </div>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Wallet Card -->
                <div class="dashboard-card wallet-card">
                    <div class="card-header">
                        <h3>Wallet Balance</h3>
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="balance"><?php echo formatCurrency($user_data['wallet_balance']); ?></h2>
                        <a href="select_payment.php" class="btn btn-primary btn-block mt-20">Fund Wallet</a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3>Quick Stats</h3>
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="card-body">
                        <div class="stat-row">
                            <span>Total Orders</span>
                            <strong>0</strong>
                        </div>
                        <div class="stat-row">
                            <span>Total Spent</span>
                            <strong>₦0.00</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="dashboard-card mt-40">
                <div class="card-header">
                    <h3>Recent Transactions</h3>
                    <i class="fas fa-history"></i>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transactions)): ?>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo $trans['reference']; ?></td>
                                            <td><?php echo ucfirst($trans['type']); ?></td>
                                            <td><?php echo formatCurrency($trans['amount']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $trans['status']; ?>">
                                                    <?php echo ucfirst($trans['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($trans['transaction_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Greaselogs. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="/../assets/js/main.js"></script>
</body>
</html>
