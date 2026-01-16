<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/storage.php';

ensureSession();

// If not logged in, redirect to login with redirect back to checkout
if (empty($_SESSION['user'])) {
    $redirect = 'checkout.php';
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit;
}

$pageTitle = 'Snack Shop · Checkout';
$currentPage = 'checkout';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    // build order from session cart
    $products = loadProducts();
    $cart = getCart();
    $cartSnapshot = buildCartItems($products, $cart);
    $items = [];
    foreach ($cartSnapshot['items'] as $it) {
        $p = $it['product'];
        $items[] = [
            'product_id' => (int) $p['id'],
            'name' => $p['name'] ?? '',
            'quantity' => (int) $it['quantity'],
            'unit_price' => (float) $p['price'],
            'line_total' => (float) $it['line_total'],
        ];
    }

    $subtotal = (float) $cartSnapshot['subtotal'];
    $shipping = $subtotal > 60 ? 0 : 6.5;
    $total = $subtotal + $shipping;

    // prepare order payload matching storage.saveOrder DB schema
    $items_count = 0;
    foreach ($items as $it) {
        $items_count += (int)($it['quantity'] ?? 0);
    }

    $order = [
        'order_number' => uniqid('ord_'),
        'customer_email' => $_SESSION['user']['email'] ?? '',
        'items' => $items,
        'items_count' => $items_count,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
        'total_amount' => $total,
        'items_json' => json_encode($items, JSON_UNESCAPED_UNICODE),
        'status' => 'Pending',
    ];

    saveOrder($order);

    // clear cart
    $_SESSION['cart'] = [];

    // redirect back to shop with a success flag
    header('Location: shop.php?order=placed');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .checkout-card { max-width:720px; margin:2rem auto; padding:2rem; text-align:center; }
    </style>
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <div class="container">
        <div class="checkout-card filter-card">
            <h1>Customer Information</h1>

            <form method="post" class="customer-form" style="display:flex; flex-direction:column; gap:1.25rem; margin-top:1rem;">
                <input type="hidden" name="action" value="place_order">
                <label class="field">
                    <span>Email Address</span>
                    <input type="email" name="email" value="<?= sanitize($_SESSION['user']['email'] ?? ''); ?>" readonly>
                </label>

                

                <div>
                    <button type="submit" class="btn primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
