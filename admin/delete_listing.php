<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Listing.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

verifyCSRFToken($_GET['csrf_token'] ?? '');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $listingObj = new Listing();
    $listing = $listingObj->getListingById($id);

    // Best-effort cleanup of the uploaded screenshot
    if ($listing && !empty($listing['image'])) {
        $path = __DIR__ . '/../' . $listing['image'];
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $listingObj->deleteListing($id);
}

header('Location: listings.php?deleted=1');
exit();
