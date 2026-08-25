<?php
/**
 * DeluxeSocial - Homepage
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Listing.php';


$listingObj = new Listing();
$featuredListings = $listingObj->getFeaturedListings(6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>greaselogs - Premium Accounts. Elite Growth.</title>
    

<link rel="icon" type="image/png" href="/assets/images/logo.png" sizes="32x32">
    <link rel="stylesheet" href="/assets/css/index.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

    <!-- Navbar -->
    <nav class="navbar">
    <div class="container nav-wrapper">

        <!-- LOGO (IMAGE SUPPORT) -->
        <a href="index.php" class="logo">
            <img src="/assets/images/logo.png" alt="DeluxeSocial">
        </a>

        <!-- HAMBURGER -->
        <div class="hamburger">
    ☰
</div> 


        <!-- MENU -->
        <ul class="nav-links" id="navMenu">
            <li><a href="pages/listings.php">Marketplace</a></li>

            

                <li><a href="/pages/dashboard.php">Dashboard</a></li>
                <li><a href="/pages/login.php">Login</a></li>
                <li><a href="/pages/register.php" class="btn btn-primary">Get Started</a></li>

        </ul>

    </div>
</nav> 


    <!-- Hero Section -->
    <header class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="fade-in">Greaselog Social Media <br><span>Marketplace</span></h1>
                <p class="fade-in delay-1">Buy and sell premium social media accounts with instant delivery and guaranteed security.</p>
                <div class="hero-btns fade-in delay-2">
                    <a href="/pages/listings.php" class="btn btn-primary">Browse Listings</a>
                    <a href="/pages/register.php" class="btn btn-outline">Sell Your Account</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3 class="counter">10k+</h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-item">
                    <h3 class="counter">50k+</h3>
                    <p>Accounts Sold</p>
                </div>
                <div class="stat-item">
                    <h3 class="counter">24</h3>
                    <p>Elite Support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Listings -->
    <section class="showcase">
    <div class="container">

        <div class="section-header">
            <h2>What You’ll <span>Get Instantly</span></h2>
            <p>Real premium accounts delivered after purchase</p>
        </div>

        <!-- Instagram -->
        <div class="showcase-item">
            <div class="showcase-img">
                <img src="/assets/images/insta.png" alt="Instagram Account Preview">
            </div>
            <div class="showcase-text">
                <h3>Instagram Verified / High Growth Accounts</h3>
                <p>Get ready-to-use accounts with real followers, engagement, and monetization potential.</p>
            </div>
        </div>

        <!-- Facebook -->
        <div class="showcase-item reverse">
            <div class="showcase-img">
                <img src="/assets/images/facebook.png" alt="Facebook Account Preview">
            </div>
            <div class="showcase-text">
                <h3>Facebook Business Accounts</h3>
                <p>Perfect for ads, business pages, and monetization setup.</p>
            </div>
        </div>

        <!-- TikTok -->
        <div class="showcase-item">
            <div class="showcase-img">
                <img src="/assets/images/tiktok.png" alt="TikTok Account Preview">
            </div>
            <div class="showcase-text">
                <h3>TikTok Monetized Accounts</h3>
                <p>Start earning immediately with pre-built viral-ready accounts.</p>
            </div>
        </div>

    </div>
</section> 

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-info">
                <a href="index.php" class="logo">
            <img src="/assets/images/logo.png" alt="DeluxeSocial">
        </a>
                    <p>The world's most trusted marketplace for premium social media assets.</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/pages/listings.php">Marketplace</a></li>
                        <li><a href="/pages/login.php">Login</a></li>
                        <li><a href="/pages/register.php">Register</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <p>Email: support@greaselog.com</p>
                    <div class="social-icons">
                        <a href="https://twitter.com"><i class="fab fa-twitter"></i></a>
                        <a href="https://instagram.com"><i class="fab fa-instagram"></i></a>
                        <a href="https://facebook.com"><i class="fab fa-telegram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?>  Greaselog. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/yournumber" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <script src="/assets/js/main.js"></script>
    
</body>
</html>
