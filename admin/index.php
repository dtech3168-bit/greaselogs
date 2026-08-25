<?php
/**
* Greaselogs - Admin Dashboard (PRO VERSION)
* Features:
* - Profit per order (markup tracking)
* - Revenue analytics (daily/weekly)
* - Live AJAX updates
*/

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../api/acctshop.php';

/* =========================
   AUTH CHECK
========================= */
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$pdo = db();

/* =========================
   ADMIN INFO
========================= */
$admin = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
} catch (Exception $e) {
    $admin = null;
}

/* =========================
   DEFAULT VALUES
========================= */
$total_users = 0;
$total_orders = 0;
$total_revenue = 0;
$total_profit = 0;
$total_listings = 0;
$recent_orders = [];
$daily_revenue = [];

/* =========================
   CORE STATS
========================= */
try {

    $total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $total_orders = (int)$pdo->query("
        SELECT COUNT(*) FROM transactions WHERE status='success'
    ")->fetchColumn();

    $total_revenue = (float)$pdo->query("
        SELECT COALESCE(SUM(amount),0)
        FROM transactions
        WHERE status='success'
    ")->fetchColumn();

    /* =========================
       PROFIT (REAL MARKUP BASED)
    ========================= */
    $total_profit = (float)$pdo->query("
        SELECT COALESCE(SUM(markup),0)
        FROM transactions
        WHERE status='success'
    ")->fetchColumn();

    /* =========================
       RECENT ORDERS
    ========================= */
    $stmt = $pdo->query("
        SELECT
            t.id,
            t.user_id,
            t.product_id,
            t.amount,
            t.markup,
            (t.amount - COALESCE(t.markup,0)) AS cost,
            t.created_at,
            u.username
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.status='success'
        ORDER BY t.id DESC
        LIMIT 10
    ");

    $recent_orders = $stmt->fetchAll();

    /* =========================
       DAILY REVENUE (7 DAYS)
    ========================= */
    $stmt = $pdo->query("
        SELECT DATE(created_at) as day, SUM(amount) as total
        FROM transactions
        WHERE status='success'
        GROUP BY DATE(created_at)
        ORDER BY day DESC
        LIMIT 7
    ");

    $daily_revenue = $stmt->fetchAll();

} catch (Exception $e) {
    $recent_orders = [];
    $daily_revenue = [];
}

/* =========================
   API LISTINGS COUNT
========================= */
$total_listings = 0;

try {
    $api = new AcctShopAPI();
    $response = $api->getCategories();

    foreach ($response['categories'] ?? [] as $cat) {
        foreach ($cat['products'] ?? [] as $p) {
            $total_listings++;
        }
    }

} catch (Exception $e) {
    $total_listings = 0;
}
?> 


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard - Greaselogs</title>
<link rel="icon" type="image/png" href="/../assets/images/logo.png" sizes="32x32">

<link rel="stylesheet" href="/../assets/css/index.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.dashboard-card {
    background: #fff;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.balance {
    font-size: 26px;
    color: #5097A4;
    margin-top: 10px;
    font-weight: bold;
}

.mt-40 { margin-top: 40px; }

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.text-center {
    text-align: center;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container nav-wrapper">

        <a href="/../" class="logo">
            <img src="/../assets/images/logo.png" alt="Logo">
        </a>

        <div class="hamburger">☰</div>

        <ul class="nav-links" id="navMenu" >
            <li><a href="index.php" class="active">Dashboard</a></li>
            <li><a href="listings.php">Listings</a></li>
            
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>

    </div>
</nav>

<!-- DASHBOARD -->
<section class="dashboard">
<div class="container">

    <div class="dashboard-header">
        <h1>Admin <span>Overview</span></h1>
        <p>Monitor and manage your marketplace.</p>
    </div>

    <!-- STATS -->
    <div class="dashboard-grid">

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Users</h3>
                <i class="fas fa-users"></i>
            </div>
            <h2 class="balance"><?= number_format($total_users) ?></h2>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Orders</h3>
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2 class="balance"><?= number_format($total_orders) ?></h2>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Revenue</h3>
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h2 class="balance">₦<?= number_format($total_revenue, 2) ?></h2>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Listings</h3>
                <i class="fas fa-list"></i>
            </div>
            <h2 class="balance"><?= number_format($total_listings) ?></h2>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Profit</h3>
                <i class="fas fa-chart-line"></i>
            </div>
            <h2 class="balance">₦<?= number_format($total_profit, 2) ?></h2>
        </div>

    </div>

    <!-- RECENT ORDERS -->
    <div class="dashboard-card mt-40">

        <div class="card-header">
            <h3>Recent Orders</h3>
            <i class="fas fa-history"></i>
        </div>

        <div class="table-responsive">

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <!-- <th>Product</th> -->
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['username'] ?? 'N/A') ?></td>
                            <!-- <td><?= htmlspecialchars($order['product_name'] ?? 'N/A') ?></td>  -->
                            <td>₦<?= number_format($order['amount'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No orders found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>
</section>
<canvas id="revenueChart" height="100"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('revenueChart');

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($daily_revenue, 'day')) ?>,
        datasets: [{
            label: 'Daily Revenue (₦)',
            data: <?= json_encode(array_column($daily_revenue, 'total')) ?>,
            borderColor: '#5097A4',
            tension: 0.3
        }]
    }
});
 



function refreshStats() {
    fetch('dashboard_live.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('users').innerText = data.users;
            document.getElementById('orders').innerText = data.orders;
            document.getElementById('revenue').innerText = data.revenue;
        });
}

// refresh every 10 seconds
setInterval(refreshStats, 10000);
</script>


    <script src="/../assets/js/main.js"></script>
</body>
</html>
