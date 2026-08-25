
Digi Tech
1:05 AM (9 minutes ago)
to me

<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../api/acctshop.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Product not found");
}

$api = new AcctShopAPI();
$response = $api->getProduct($id);

if (!isset($response['product'])) {
    die("Product not found");
}

$product = $response['product'];

// your profit
$markup = 3000;
$price = $product['price'] + $markup;
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>product</title>
</head>
<body>
<h1><?= $product['name']; ?></h1>

<p><strong>ID:</strong> <?= $product['id']; ?></p>
<p><strong>Category:</strong> <?= $product['category'] ?? 'N/A'; ?></p>

<h2>₦<?= number_format($price); ?></h2>

<?php if (isLoggedIn()): ?>
    <form method="POST" action="checkout.php">
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
        <button>Proceed to Checkout</button>
    </form>
<?php else: ?>
    <a href="login.php">Login to Buy</a>
<?php endif; ?>

</body>
</html>