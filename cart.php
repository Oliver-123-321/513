<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();
$products = loadProducts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $redirectRaw = $_POST['redirect'] ?? 'cart.php';
    $redirect = $redirectRaw !== '' ? filter_var($redirectRaw, FILTER_SANITIZE_URL) : 'cart.php';

    if ($action === 'add') {
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        addProductToCart($productId, $qty);
    } elseif ($action === 'set') {
        $qty = max(0, (int) ($_POST['quantity'] ?? 0));
        setProductQuantity($productId, $qty);
    } elseif ($action === 'increment') {
        adjustProductQuantity($productId, 1);
    } elseif ($action === 'decrement') {
        adjustProductQuantity($productId, -1);
    } elseif ($action === 'remove') {
        setProductQuantity($productId, 0);
    }

    header('Location: ' . ($redirect ?: 'cart.php'));
    exit;
}

$cart = getCart();
$cartSnapshot = buildCartItems($products, $cart);
$cartItems = $cartSnapshot['items'];
$subtotal = $cartSnapshot['subtotal'];

$shipping = $subtotal > 60 ? 0 : 6.5;
$grandTotal = $subtotal + $shipping;
$pageTitle = 'Snack Shop · Cart';
$currentPage = 'cart';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body class="page-cart">
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <div class="cart-layout">
        <section class="cart-table">
            <div class="shop-header">
                <div>
                    <p class="eyebrow">Home / Cart</p>
                    <h1>Your Cart</h1>
                    <p><?= count($cartItems); ?> items selected</p>
                </div>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <?php $product = $item['product']; ?>
                    <tr>
                        <td>
                            <div class="cart-item">
                                <img src="<?= sanitize($product['image']); ?>" alt="<?= sanitize($product['name']); ?>">
                                <div>
                                    <p><?= sanitize($product['name']); ?></p>
                                    <small><?= sanitize($product['category']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= formatPrice((float) $product['price']); ?></td>
                        <td>
                            <form method="post" class="quantity-control">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">
                                <input type="hidden" name="redirect" value="cart.php">
                                <button type="submit" name="action" value="decrement" aria-label="Decrease quantity">-</button>
                                <span><?= $item['quantity']; ?></span>
                                <button type="submit" name="action" value="increment" aria-label="Increase quantity">+</button>
                            </form>
                        </td>
                        <td><?= formatPrice((float) $item['line_total']); ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">
                                <input type="hidden" name="redirect" value="cart.php">
                                <button class="btn outline" type="submit" name="action" value="remove" onclick="return confirm('Remove this item?');">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <aside class="cart-summary">
            <h2>Order Summary</h2>
            <div class="summary-line">
                <span>Subtotal</span>
                <strong><?= formatPrice((float) $subtotal); ?></strong>
            </div>
            <div class="summary-line">
                <span>Shipping</span>
                <strong><?= $shipping === 0 ? 'Free' : formatPrice((float) $shipping); ?></strong>
            </div>
            <hr>
            <div class="summary-line summary-total">
                <span>Total</span>
                <span><?= formatPrice((float) $grandTotal); ?></span>
            </div>
            <div class="cart-actions">
                <a class="btn primary" href="checkout.php">Checkout Securely</a>
                <a class="btn outline" href="shop.php">Continue Shopping</a>
            </div>
            <p class="eyebrow">Safe checkout · SSL encrypted</p>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

