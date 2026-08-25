<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../api/acctshop.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$pdo = db();

// fetch data
$settings = getSettings();
$paystack = getPaymentGateway('paystack');
$cryptomus = getPaymentGateway('cryptomus');
$korapay = getPaymentGateway('korapay');
$nowpayments = getPaymentGateway('nowpayments');
$csrf_token = generateCSRFToken();

// ================= SAVE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    // SETTINGS
    $stmt = $pdo->prepare("
        UPDATE settings SET
        api_key = ?,
        markup = ?
        WHERE id = 1
    ");

    $stmt->execute([
        $_POST['api_key'],
        $_POST['markup']
    ]);

    // PAYSTACK
    $stmt = $pdo->prepare("
        UPDATE payment_settings
        SET public_key = ?, secret_key = ?, enabled = ?
        WHERE gateway = 'paystack'
    ");

    $stmt->execute([
        $_POST['paystack_public'],
        $_POST['paystack_secret'],
        isset($_POST['paystack_enabled']) ? 1 : 0
    ]);

    // CRYPTOMUS
    $stmt = $pdo->prepare("
        UPDATE payment_settings
        SET public_key = ?, secret_key = ?, enabled = ?
        WHERE gateway = 'cryptomus'
    ");

    $stmt->execute([
        $_POST['cryptomus_public'],
        $_POST['cryptomus_secret'],
        isset($_POST['cryptomus_enabled']) ? 1 : 0
    ]);

    // KORAPAY
    $stmt = $pdo->prepare("
        UPDATE payment_settings
        SET public_key = ?, secret_key = ?, enabled = ?
        WHERE gateway = 'korapay'
    ");

    $stmt->execute([
        $_POST['korapay_public'] ?? '',
        $_POST['korapay_secret'] ?? '',
        isset($_POST['korapay_enabled']) ? 1 : 0
    ]);

    // NOWPAYMENTS
    $stmt = $pdo->prepare("
        UPDATE payment_settings
        SET secret_key = ?, extra_key = ?, enabled = ?
        WHERE gateway = 'nowpayments'
    ");

    $stmt->execute([
        $_POST['nowpayments_api_key'] ?? '',
        $_POST['nowpayments_ipn_secret'] ?? '',
        isset($_POST['nowpayments_enabled']) ? 1 : 0
    ]);

    // PRODUCT PRICING
    if (!empty($_POST['product_id'])) {

        $stmt = $pdo->prepare("
            INSERT INTO product_pricing (product_id, custom_price, custom_markup)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            custom_price = VALUES(custom_price),
            custom_markup = VALUES(custom_markup)
        ");

        $stmt->execute([
            $_POST['product_id'],
            $_POST['custom_price'] ?: null,
            $_POST['custom_markup'] ?: null
        ]);
    }

    $success = "Saved successfully!";
    $settings = getSettings();
    $paystack = getPaymentGateway('paystack');
    $cryptomus = getPaymentGateway('cryptomus');
    $korapay = getPaymentGateway('korapay');
    $nowpayments = getPaymentGateway('nowpayments');
}

// ================= WALLET =================
$wallet_balance = 0;
$wallet_status = 'Not Connected';

try {
    $api = new AcctShopAPI();
    $profile = $api->getProfile();

    if (($profile['status'] ?? '') === 'success') {
        $wallet_balance = $profile['data']['balance'] ?? 0;
        $wallet_status = 'Connected';
    } else {
        $wallet_status = 'API Error';
    }
} catch (Exception $e) {
    $wallet_status = 'Connection Failed';
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>
<link rel="icon" type="image/png" href="/../assets/images/logo.png" sizes="32x32">
    
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
.settings-page {
    padding: 40px 0 70px;
}

.settings-card {
    max-width: 900px;
    margin: 0 auto;
    background: #ffffff;
    border: 1px solid #E1E8E9;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(45, 62, 64, 0.06);
    overflow: hidden;
}

.settings-card-header {
    padding: 24px 28px;
    border-bottom: 1px solid #E1E8E9;
}

.settings-card-header h2 {
    margin: 0;
    color: #2D3E40;
}

.settings-card-body {
    padding: 28px;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.tabs button {
    flex: 1;
    min-width: 120px;
    padding: 11px 14px;
    border: 1px solid #E1E8E9;
    background: #F7FAFB;
    color: #2D3E40;
    cursor: pointer;
    border-radius: 10px;
    font-weight: 700;
    transition: .2s ease;
}

.tabs button:hover,
.tabs button.active {
    background: #5097A4;
    color: #fff;
    border-color: #5097A4;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.settings-card input[type="text"],
.settings-card input[type="number"],
.settings-card input[type="password"] {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 13px;
    margin: 8px 0 18px;
    border-radius: 10px;
    border: 1px solid #E1E8E9;
    background: #F7FAFB;
    color: #2D3E40;
}

.settings-card input:focus {
    outline: none;
    border-color: #5097A4;
    box-shadow: 0 0 0 3px rgba(80,151,164,.12);
}

.settings-card label {
    color: #2D3E40;
    font-weight: 600;
}

.toggle,
.checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 8px 0 18px;
}

.save-btn {
    background: #5097A4;
    color: #fff;
    padding: 13px 18px;
    border: none;
    width: 100%;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
}

.save-btn:hover {
    background: #3E7883;
    transform: translateY(-1px);
}

.settings-alert {
    background: #E8F7EF;
    color: #17663d;
    border: 1px solid #bde5cf;
    padding: 12px 14px;
    margin-bottom: 18px;
    border-radius: 10px;
}

.wallet-card {
    max-width: 900px;
    margin: 0 auto 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #5097A4, #3E7883);
    color: #fff;
    padding: 22px 28px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(80,151,164,0.25);
}

.wallet-card h3,
.wallet-card p {
    margin: 0;
}

.wallet-card .balance {
    font-size: 26px;
    font-weight: 800;
}

@media (max-width: 700px) {
    .settings-card-body {
        padding: 20px;
    }

    .wallet-card {
        margin-left: 20px;
        margin-right: 20px;
        padding: 18px;
    }
}
</style>>
<link rel="stylesheet" href="../assets/css/index.css">

</head>

<body>
<!-- Navbar -->
<nav class="navbar">
    <div class="container nav-wrapper">

        <!-- LOGO (IMAGE SUPPORT) -->
        <a href="/../" class="logo">
            <img src="/../assets/images/logo.png" alt="Logo">
        </a>
        
        <!-- HAMBURGER -->
        <div class="hamburger">
    ☰
</div> 


        <!-- MENU -->
        <ul class="nav-links" id="navMenu">

            <?php if (isAdminLoggedIn()): ?>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="listings.php">Listings</a></li>
                <li><a href="developers.php">Developers</a></li>
                <li><a href="settings.php">Secret Room</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>

    </div>
</nav>
<main class="settings-page">
<div class="container">
<div class="wallet-card">
    <div>
        <h3>AcctShop Wallet</h3>
        <p>Status: <strong><?= htmlspecialchars($wallet_status ?? '_') ?></strong></p>
    </div>

    <div class="balance">
        $<?= number_format((float)($wallet_balance ?? 0),2) ?>
    </div>
</div>


<div class="settings-card">
<div class="settings-card-header"><h2>⚙️ Admin Settings</h2></div>
<div class="settings-card-body">

<?php if (!empty($success)): ?>
<div class="settings-alert">Saved successfully</div>
<?php endif; ?>

<div class="tabs">
    <button onclick="showTab(0)" class="active">API</button>
    <button onclick="showTab(1)">Payments</button>
    <button onclick="showTab(2)">Business</button>
</div>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<!-- API -->
<div class="tab-content active">
    <label>AcctShop API Key</label>
    <input name="api_key" value="<?= $settings['api_key'] ?? '' ?>">

    <div class="toggle">
        <span>Enable API</span>
        <input type="checkbox" name="api_enabled"
        <?= !empty($settings['api_enabled']) ? 'checked' : '' ?>>
    </div>
</div>

<!-- PAYMENTS -->
<div class="tab-content">

    <h3>Paystack</h3>
    <input name="paystack_public" placeholder="Public Key"
    value="<?= $paystack['public_key'] ?? '' ?>">

    <input name="paystack_secret" placeholder="Secret Key"
    value="<?= $paystack['secret_key'] ?? '' ?>">

    <label>
        <input type="checkbox" name="paystack_enabled"
        <?= !empty($paystack['enabled']) ? 'checked' : '' ?>>
        Enable Paystack
    </label>

    <hr>

    <h3>Cryptomus</h3>
    <input name="cryptomus_public" placeholder="API Key"
    value="<?= $cryptomus['public_key'] ?? '' ?>">

    <input name="cryptomus_secret" placeholder="Secret"
    value="<?= $cryptomus['secret_key'] ?? '' ?>">

    <label>
        <input type="checkbox" name="cryptomus_enabled"
        <?= !empty($cryptomus['enabled']) ? 'checked' : '' ?>>
        Enable Cryptomus
    </label>

    <hr>

    <h3>Korapay</h3>
    <input name="korapay_public" placeholder="Public Key"
    value="<?= htmlspecialchars($korapay['public_key'] ?? '') ?>">

    <input name="korapay_secret" placeholder="Secret Key"
    value="<?= htmlspecialchars($korapay['secret_key'] ?? '') ?>">

    <label>
        <input type="checkbox" name="korapay_enabled"
        <?= !empty($korapay['enabled']) ? 'checked' : '' ?>>
        Enable Korapay
    </label>

    <hr>

    <h3>NowPayments</h3>
    <input name="nowpayments_api_key" placeholder="API Key"
    value="<?= htmlspecialchars($nowpayments['secret_key'] ?? '') ?>">

    <input name="nowpayments_ipn_secret" placeholder="IPN Secret Key"
    value="<?= htmlspecialchars($nowpayments['extra_key'] ?? '') ?>">

    <label>
        <input type="checkbox" name="nowpayments_enabled"
        <?= !empty($nowpayments['enabled']) ? 'checked' : '' ?>>
        Enable NowPayments
    </label>

</div>

<!-- BUSINESS -->
<div class="tab-content">
    <label>Global Markup (₦)</label>
    <input type="number" name="markup"
    value="<?= $settings['markup'] ?? 0 ?>">

    <label>
        <input type="checkbox" name="maintenance_mode"
        <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
        Maintenance Mode
    </label>
</div>

<br>
<button class="save-btn">Save Changes</button>

</form>

</div>
</div>
</main>

<script>
function showTab(index) {
    document.querySelectorAll('.tabs button').forEach((btn,i)=>{
        btn.classList.toggle('active', i===index);
    });

    document.querySelectorAll('.tab-content').forEach((tab,i)=>{
        tab.classList.toggle('active', i===index);
    });
} 

</script>
<script src="/admin/assets/js/main.js"></script>
</body>
</html> 
