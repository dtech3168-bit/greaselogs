<?php

require_once __DIR__ . '/../api/acctshop.php';
require_once __DIR__ . '/../classes/Listing.php';

function getMarketplaceProducts() {

    $api = new AcctShopAPI();
    $response = $api->getProducts();

    $products = [];

    // 1. API products (MAIN SOURCE)
    if (isset($response['status']) && $response['status'] === 'success') {

        $categories = $response['categories'] ?? [];

        foreach ($categories as $cat) {
            if (!empty($cat['products'])) {
                foreach ($cat['products'] as $p) {
                    $p['source'] = 'api';
                    $p['category'] = $cat['name'];
                    $products[] = $p;
                }
            }
        }
    }

    // 2. fallback: manual DB listings
    $listingObj = new Listing();
    $dbListings = $listingObj->getListings([]);

    if (!empty($dbListings)) {
        foreach ($dbListings as $l) {
            $l['source'] = 'manual';
            $products[] = $l;
        }
    }

    return $products;
}