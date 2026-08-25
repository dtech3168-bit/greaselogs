<?php
/**
 * DeluxeSocial - Listing Detail Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Listing.php';

$listingObj = new Listing();
$id = $_GET['id'] ?? 0;
$listing = $listingObj->getListingById($id);

if (!$listing) {
    redirect('pages/listings.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $listing['title']; ?> - DeluxeSocial</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Deluxe<span>Social</span></a>
            <ul class="nav-links">
                <li><a href="listings.php">Marketplace</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Listing Detail Content -->
    <section class="listing-detail">
        <div class="container">
            <div class="detail-layout">
                <!-- Left Column: Details -->
                <div class="detail-main">
                    <div class="detail-card">
                        <div class="detail-header">
                            <div class="platform-badge"><?php echo $listing['platform']; ?></div>
                            <h1><?php echo $listing['title']; ?></h1>
                            <div class="detail-meta">
                                <span><i class="fas fa-users"></i> <?php echo number_format($listing['followers_count']); ?> Followers</span>
                                <span><i class="fas fa-chart-line"></i> <?php echo $listing['engagement_rate']; ?>% Engagement</span>
                                <span><i class="fas fa-tag"></i> <?php echo $listing['niche']; ?></span>
                            </div>
                        </div>
                        
                        <div class="detail-body">
                            <h3>Description</h3>
                            <p><?php echo nl2br($listing['description']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Purchase -->
                <aside class="detail-sidebar">
                    <div class="purchase-card">
                        <div class="price-section">
                            <span class="label">Price</span>
                            <h2 class="price"><?php echo formatCurrency($listing['price']); ?></h2>
                        </div>
                        
                        <div class="purchase-info">
                            <ul>
                                <li><i class="fas fa-check-circle"></i> Instant Delivery</li>
                                <li><i class="fas fa-check-circle"></i> Secure Payment</li>
                                <li><i class="fas fa-check-circle"></i> Verified Account</li>
                            </ul>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-block">Buy Now</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-block">Login to Buy</a>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> DeluxeSocial. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
