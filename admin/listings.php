<?php
/**
 * Greaselogs - Admin: Create/Manage Social Media Account Listings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Listing.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$listingObj = new Listing();

$success = '';
$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_listing'])) {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    try {
        $imagePath = handleListingImageUpload('image');

        $data = [
            'title'            => sanitize($_POST['title'] ?? ''),
            'platform'         => sanitize($_POST['platform'] ?? ''),
            'followers_count'  => intval($_POST['followers_count'] ?? 0),
            'engagement_rate'  => $_POST['engagement_rate'] !== '' ? floatval($_POST['engagement_rate']) : null,
            'niche'            => sanitize($_POST['niche'] ?? ''),
            'price'            => floatval($_POST['price'] ?? 0),
            'description'      => sanitize($_POST['description'] ?? ''),
            'image'            => $imagePath,
            'account_username' => sanitize($_POST['account_username'] ?? ''),
            'account_password' => $_POST['account_password'] ?? '', // not htmlspecialchars'd — credential, delivered as-is
            'account_email'    => sanitize($_POST['account_email'] ?? ''),
            'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
        ];

        if ($data['title'] === '' || $data['platform'] === '' || $data['price'] <= 0) {
            $error = "Title, platform, and a valid price are required.";
        } elseif ($listingObj->addListing($data)) {
            $success = "Listing added successfully! It's now live on the marketplace.";
        } else {
            $error = "Failed to add listing.";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$filters = [];
if (!empty($_GET['status'])) $filters['status'] = sanitize($_GET['status']);
if (!empty($_GET['search'])) $filters['search'] = sanitize($_GET['search']);

$listings = $listingObj->getListings($filters);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listings - Greaselogs Admin</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .listing-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; background:#222; }
        .form-row { display:flex; gap:15px; flex-wrap:wrap; }
        .form-row .form-group { flex: 1; min-width: 180px; }
        .badge-available { background:#1f9d55; color:#fff; padding:3px 10px; border-radius:20px; font-size:12px; }
        .badge-sold { background:#999; color:#fff; padding:3px 10px; border-radius:20px; font-size:12px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo"><img src="/assets/images/logo.png" alt="Greaselogs Admin"></a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="listings.php" class="active">Listings</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Manage <span>Listings</span></h1>
                <p>Create and upload social media account listings for the marketplace.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Add Listing Form -->
            <div class="dashboard-card mb-40">
                <div class="card-header">
                    <h3>Add New Account Listing</h3>
                    <i class="fa fa-plus-circle"></i>
                </div>
                <div class="card-body">
                    <form action="listings.php" method="POST" class="admin-form" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" required placeholder="e.g. 10k Instagram Fashion Page">
                            </div>
                            <div class="form-group">
                                <label>Platform</label>
                                <select name="platform" required>
                                    <option value="Instagram">Instagram</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="TikTok">TikTok</option>
                                    <option value="Twitter/X">Twitter/X</option>
                                    <option value="YouTube">YouTube</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Followers</label>
                                <input type="number" name="followers_count" required min="0" placeholder="e.g. 10000">
                            </div>
                            <div class="form-group">
                                <label>Engagement Rate (%)</label>
                                <input type="number" step="0.01" name="engagement_rate" placeholder="e.g. 5.5">
                            </div>
                            <div class="form-group">
                                <label>Price (₦)</label>
                                <input type="number" step="0.01" name="price" required min="1" placeholder="e.g. 50000">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Niche</label>
                            <input type="text" name="niche" placeholder="e.g. Fashion, Gaming, Tech">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" placeholder="Enter account description..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Proof Screenshot (optional, JPG/PNG/WEBP, max 5MB)</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <hr class="my-20">
                        <h4>Account Credentials (Auto-Delivery on purchase)</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="account_username" required placeholder="Account username">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="text" name="account_password" required placeholder="Account password">
                            </div>
                            <div class="form-group">
                                <label>Email (Optional)</label>
                                <input type="text" name="account_email" placeholder="Account email">
                            </div>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" name="is_featured" id="is_featured">
                            <label for="is_featured">Mark as Featured</label>
                        </div>

                        <button type="submit" name="add_listing" class="btn btn-primary">Add Listing</button>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div class="dashboard-card mb-20">
                <div class="card-body">
                    <form method="GET" class="form-row" style="align-items:flex-end;">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Title or niche">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All</option>
                                <option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="sold" <?= ($_GET['status'] ?? '') === 'sold' ? 'selected' : '' ?>>Sold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-outline">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Listings Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>All Listings (<?= count($listings) ?>)</h3>
                    <i class="fa fa-list"></i>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Platform</th>
                                    <th>Followers</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($listings)): ?>
                                    <?php foreach ($listings as $listing): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($listing['image'])): ?>
                                                    <img class="listing-thumb" src="../<?= htmlspecialchars($listing['image']) ?>" alt="">
                                                <?php else: ?>
                                                    <div class="listing-thumb"></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>#<?= (int)$listing['id'] ?></td>
                                            <td><?= htmlspecialchars($listing['title']) ?></td>
                                            <td><?= htmlspecialchars($listing['platform']) ?></td>
                                            <td><?= number_format((int)$listing['followers_count']) ?></td>
                                            <td><?= formatCurrency($listing['price']) ?></td>
                                            <td>
                                                <span class="badge-<?= htmlspecialchars($listing['status']) ?>">
                                                    <?= ucfirst($listing['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $listing['is_featured'] ? '⭐' : '—' ?></td>
                                            <td>
                                                <a href="edit_listing.php?id=<?= (int)$listing['id'] ?>" class="btn btn-sm btn-outline"><i class="fa fa-pencil"></i></a>
                                                <a href="delete_listing.php?id=<?= (int)$listing['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>"
                                                   class="btn btn-sm btn-error"
                                                   onclick="return confirm('Delete this listing? This cannot be undone.')">
                                                   <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No listings found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Greaselogs Admin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
