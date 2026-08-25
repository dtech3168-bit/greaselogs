<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Listing.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$listingObj = new Listing();
$id = (int)($_GET['id'] ?? 0);
$listing = $listingObj->getListingById($id);

if (!$listing) {
    header('Location: listings.php');
    exit();
}

$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
            'account_username' => sanitize($_POST['account_username'] ?? ''),
            'account_password' => $_POST['account_password'] ?? '',
            'account_email'    => sanitize($_POST['account_email'] ?? ''),
            'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
        ];

        if ($imagePath !== null) {
            $data['image'] = $imagePath;
        }

        if ($listingObj->updateListing($id, $data)) {
            header('Location: listings.php?updated=1');
            exit();
        }

        $error = "Failed to update listing.";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    $listing = array_merge($listing, $data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Listing - Greaselogs Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .form-row { display:flex; gap:15px; flex-wrap:wrap; }
        .form-row .form-group { flex: 1; min-width: 180px; }
        .listing-thumb-lg { width: 140px; border-radius: 10px; margin-bottom: 15px; }
    </style>
</head>
<body class="dark-theme">
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Admin<span>Panel</span></a>
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
                <h1>Edit <span>Listing</span> #<?= (int)$listing['id'] ?></h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="card-body">

                    <?php if (!empty($listing['image'])): ?>
                        <img class="listing-thumb-lg" src="../<?= htmlspecialchars($listing['image']) ?>" alt="">
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" required value="<?= htmlspecialchars($listing['title']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Platform</label>
                                <select name="platform" required>
                                    <?php foreach (['Instagram','Facebook','TikTok','Twitter/X','YouTube'] as $p): ?>
                                        <option value="<?= $p ?>" <?= $listing['platform'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Followers</label>
                                <input type="number" name="followers_count" required min="0" value="<?= (int)$listing['followers_count'] ?>">
                            </div>
                            <div class="form-group">
                                <label>Engagement Rate (%)</label>
                                <input type="number" step="0.01" name="engagement_rate" value="<?= htmlspecialchars($listing['engagement_rate'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Price (₦)</label>
                                <input type="number" step="0.01" name="price" required min="1" value="<?= htmlspecialchars($listing['price']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Niche</label>
                            <input type="text" name="niche" value="<?= htmlspecialchars($listing['niche'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"><?= htmlspecialchars($listing['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Replace Screenshot (optional)</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <hr class="my-20">
                        <h4>Account Credentials</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="account_username" required value="<?= htmlspecialchars($listing['account_username'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="text" name="account_password" required value="<?= htmlspecialchars($listing['account_password'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Email (Optional)</label>
                                <input type="text" name="account_email" value="<?= htmlspecialchars($listing['account_email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" name="is_featured" id="is_featured" <?= $listing['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Mark as Featured</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="listings.php" class="btn btn-outline">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
