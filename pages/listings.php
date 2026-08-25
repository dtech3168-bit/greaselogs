<?php
/**
*Greaselogs - Marketplace (Clean + Dynamic Pricing + Admin Markup)
*/

require_once __DIR__ . '/../api/acctshop.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

/**
* SAFE SESSION START (important for marketplace cache)
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = db();

/* =========================
   FETCH SETTINGS (MARKUP FIX 🔥)
========================= */
$settings = getSettings();
$global_markup = (float)($settings['markup'] ?? 0);

/* =========================
   FETCH PRODUCTS FROM API
========================= */
$api = new AcctShopAPI();

try {
    $response = $api->getCategories();
} catch (Exception $e) {
    $response = [];
}

$products = [];

if (!empty($response['categories'])) {
    foreach ($response['categories'] as $cat) {
        foreach ($cat['products'] ?? [] as $p) {

            $p['category'] = $cat['name'] ?? 'General';

            // 🔥 APPLY GLOBAL MARKUP HERE (FIX)
            $base_price = (float)($p['price'] ?? 0);
            $p['final_price'] = $base_price + $global_markup;

            $products[] = $p;
        }
    }
}

/* =========================
   MERGE IN MANUAL (ADMIN-UPLOADED) LISTINGS
========================= */
require_once __DIR__ . '/../classes/Listing.php';

try {
    $listingObj = new Listing();
    $manualListings = $listingObj->getListings(['status' => 'available']);

    foreach ($manualListings as $ml) {
        $products[] = [
            'id'           => 'm' . $ml['id'],   // prefixed so it never collides with AcctShop numeric IDs
            'name'         => $ml['title'],
            'price'        => (float)$ml['price'],
            'final_price'  => (float)$ml['price'], // admin sets the final retail price directly, no extra markup
            'image'        => $ml['image'] ?? null,
            'source'       => 'manual',
        ];
    }
} catch (Exception $e) {
    // don't break the marketplace if this fails
}

/* cache for checkout */
$_SESSION['marketplace_products'] = $products;

/* =========================
   FILTERS
========================= */
$search   = strtolower($_GET['search'] ?? '');
$platform = strtolower($_GET['platform'] ?? '');
$page     = max((int)($_GET['page'] ?? 1), 1);

$filtered = [];

foreach ($products as $p) {

    $name = strtolower($p['name'] ?? '');
    $price = $p['final_price'];

    // detect platform
    $platform_detected = 'other';

    if (strpos($name, 'tiktok') !== false) $platform_detected = 'tiktok';
    elseif (strpos($name, 'instagram') !== false) $platform_detected = 'instagram';
    elseif (strpos($name, 'facebook') !== false) $platform_detected = 'facebook';

    // filters
    if ($search && strpos($name, $search) === false) continue;
    if ($platform && $platform !== $platform_detected) continue;

    $p['platform'] = $platform_detected;

    $filtered[] = $p;
}

/* =========================
   PAGINATION
========================= */
$perPage = 12;
$total = count($filtered);
$totalPages = max(1, ceil($total / $perPage));

$offset = ($page - 1) * $perPage;
$products = array_slice($filtered, $offset, $perPage);

/* helper */
function buildQuery($params = []) {
    return '?' . http_build_query(array_merge($_GET, $params));
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketplace - Greaselogs</title>
<style>.support-float {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 55px;
    height: 55px;
    background: rgba(99, 102, 241, 0.9);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    z-index: 9999;
}

.support-float:hover {
    transform: scale(1.1);
    box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
}

/* pulse animation */
.support-float::after {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.4);
    animation: pulse 1.8s infinite;
    z-index: -1;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 0.7; }
    100% { transform: scale(1.8); opacity: 0; }
}

/* mobile adjustment */
@media (max-width: 480px) {
    .support-float {
        width: 50px;
        height: 50px;
        font-size: 20px;
        bottom: 15px;
        right: 15px;
    }
}

.alert-error {
    background: rgba(255, 99, 99, 0.1);
    border: 1px solid rgba(255, 99, 99, 0.4);
    color: #b00020;
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>
<link rel="stylesheet" href="../assets/css/index.css">
<link rel="icon" type="image/png" href="/assets/images/logo.png" sizes="32x32">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>



<!-- Navbar -->
<nav class="navbar">
    <div class="container nav-wrapper">
        <a href="/../index.php" class="logo">
            <img src="/assets/images/logo.png" alt="Greaselogs">
        </a>

        <div class="hamburger">☰</div>

        <ul class="nav-links" id="navMenu">
            <li><a href="listings.php" class="active">Marketplace</a></li>

            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Get Started</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav> 

<header class="page-header">
    <h1>Greaselogs <span>Marketplace</span></h1>
</header>

<section class="container">


<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert-error">
        <?= $_SESSION['error_msg']; ?>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>


<!-- FILTER UI -->
<form class="filters-bar" method="GET">

    <input type="text" name="search" class="search-input"
           placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

    <div class="filter-pills">
        <?php
        $platforms = ['','tiktok','instagram','facebook'];
        foreach ($platforms as $p):
        ?>
        <a href="<?= buildQuery(['platform'=>$p,'page'=>1]) ?>"
           class="pill <?= ($platform === $p) ? 'active' : '' ?>">
            <?= $p ? ucfirst($p) : 'All' ?>
        </a>
        <?php endforeach; ?>
    </div>

</form>

<!-- PRODUCTS -->
<div class="products-grid">

<?php if ($products): foreach ($products as $p): ?>

<div class="product-card">

    <div class="badge-top">🔥 Top</div>

    <?php if (!empty($p['image'])): ?>
        <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
    <?php endif; ?>

    <div class="platform-icon <?= $p['platform'] ?>">
        <i class="fab fa-<?= $p['platform'] ?>"></i>
    </div>

    <h3><?= htmlspecialchars($p['name']) ?></h3>
    <p class="product-id">ID: <?= $p['id'] ?></p>

    <div class="price">₦<?= number_format($p['final_price'], 2) ?></div>

    <form method="POST" action="buy.php">
    <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
<input type="hidden" name="price" value="<?= $p['price']; ?>">
<input type="hidden" name="name" value="<?= htmlspecialchars($p['name']); ?>">

        <button class="btn-buy">Buy Now</button>
    </form>

</div>

<?php endforeach; else: ?>

<p>No products found.</p>

<?php endif; ?>

<a href="https://wa.me/234XXXXXXXXXX" class="support-float" target="_blank">
    <i class="fas fa-headset"></i>
</a> 

</div>

<!-- PAGINATION -->
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="<?= buildQuery(['page'=>$i]) ?>"
       class="page-btn <?= ($page == $i) ? 'active' : '' ?>">
       <?= $i ?>
    </a>
<?php endfor; ?>
</div>

</section>
<script src="/assets/js/main.js"></script>
    
</body>
</html>