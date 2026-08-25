<?php
require_once __DIR__ . '/api/acctshop.php';

$api = new AcctShopAPI();

// Fetch API response
$response = $api->getCategories();

// Safety check
if (!is_array($response) || ($response['status'] ?? '') !== 'success') {
    die("API Error: " . ($response['msg'] ?? 'Unknown error'));
}

// ✅ Correct structure: categories → products
$categories = $response['categories'] ?? [];

$products = [];

foreach ($categories as $cat) {
    if (!empty($cat['products'])) {
        foreach ($cat['products'] as $p) {
            $p['category'] = $cat['name'];
            $products[] = $p;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Test</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 12px;
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: #00ceff;
        }

        .title {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .id {
            font-size: 12px;
            color: #aaa;
        }

        .price {
            margin-top: 10px;
            color: #00ff99;
            font-weight: bold;
        }

        .category {
            font-size: 12px;
            color: #00ceff;
            margin-top: 5px;
        }

        .desc {
            font-size: 12px;
            color: #bbb;
            margin-top: 10px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<h1>🧪 AcctShop Products</h1>

<?php if (empty($products)): ?>

    <p>No products found (API categories have empty products arrays).</p>

<?php else: ?>

    <div class="grid">

        <?php foreach ($products as $product): ?>

            <div class="card">

                <div class="title">
                    <?= htmlspecialchars($product['name'] ?? 'No name') ?>
                </div>

                <div class="id">
                    ID: <?= $product['id'] ?? 'N/A' ?>
                </div>

                <div class="category">
                    Category: <?= htmlspecialchars($product['category'] ?? '') ?>
                </div>

                <div class="price">
                    ₦<?= number_format($product['price'] ?? 0) ?>
                </div>

                <div class="desc">
                    <?= htmlspecialchars(substr(strip_tags($product['description'] ?? ''), 0, 100)) ?>...
                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</body>
</html>