<?php
/**
 * DeluxeSocial - Product Engine (Normalized Layer)
 * Handles API + DB and returns unified product structure
 */

require_once __DIR__ . '/../api/acctshop.php';
require_once __DIR__ . '/../classes/Listing.php';

/**
 * Normalize ANY product into ONE standard structure
 */
function normalizeProduct($item, $source = 'api') {

    // API PRODUCT
    if ($source === 'api') {
        return [
            'id'        => $item['id'] ?? null,
            'name'      => $item['name'] ?? 'No Name',
            'price'     => (float) ($item['price'] ?? 0),
            'category'  => $item['category'] ?? 'Uncategorized',
            'description'=> $item['description'] ?? '',
            'source'    => 'api'
        ];
    }

    // DATABASE PRODUCT
    return [
        'id'        => $item['id'] ?? null,
        'name'      => $item['title'] ?? 'No Name',
        'price'     => (float) ($item['price'] ?? 0),
        'category'  => $item['platform'] ?? 'Manual',
        'description'=> $item['description'] ?? '',
        'source'    => 'manual'
    ];
}

/**
 * MAIN ENGINE: Get all marketplace products
 */
function getMarketplaceProducts() {

    $products = [];

    /* =========================
       1. API PRODUCTS (PRIMARY)
    ========================== */
    try {
        $api = new AcctShopAPI();
        $response = $api->getCategories();

        if (!empty($response['categories'])) {
            foreach ($response['categories'] as $cat) {

                if (!empty($cat['products'])) {
                    foreach ($cat['products'] as $p) {

                        $p['category'] = $cat['name'];

                        $products[] = normalizeProduct($p, 'api');
                    }
                }
            }
        }

    } catch (Exception $e) {
        // silently fail API, don't break site
    }


    /* =========================
       2. DATABASE PRODUCTS (FALLBACK / EXTRA)
    ========================== */
    try {
        $listingObj = new Listing();
        $dbListings = $listingObj->getListings([]);

        if (!empty($dbListings)) {
            foreach ($dbListings as $l) {
                $products[] = normalizeProduct($l, 'manual');
            }
        }

    } catch (Exception $e) {
        // ignore DB errors safely
    }


    return $products;
}