<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();
$isLoggedIn = !empty($_SESSION['user'] ?? null);

$productId = (int) ($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: shop.php');
    exit;
}

$products = loadProducts();
$product = null;
foreach ($products as $p) {
    if ((int) $p['id'] === $productId) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: shop.php');
    exit;
}

$cart = getCart();
if (!$isLoggedIn) {
    $cart = [];
}
$cartQty = $isLoggedIn ? (int) ($cart[$productId] ?? 0) : 0;

// Helper: resolve product image path
function resolveProductImage(string $image): string
{
    $candidate = 'assets/images/' . $image;
    if (file_exists(__DIR__ . '/' . $candidate)) {
        return $candidate;
    }
    return $image;
}

$pageTitle = 'Snack Shop · ' . sanitize($product['name']);
$currentPage = 'shop';
$currentUrl = sanitize($_SERVER['REQUEST_URI'] ?? 'product-detail.php?id=' . $productId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .product-detail-wrapper {
            padding: 3rem 5vw;
        }
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .product-detail-card {
            background: var(--card);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        .product-detail-image {
            width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
        }
        .product-detail-info h1 {
            font-size: 2.5rem;
            margin: 0 0 1rem 0;
            color: var(--text);
        }
        .product-detail-rating {
            margin: 1rem 0;
        }
        .product-detail-description {
            color: var(--muted);
            font-size: 1.1rem;
            line-height: 1.8;
            margin: 2rem 0;
        }
        .product-detail-price {
            font-size: 2rem;
            font-weight: 700;
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
            align-items: baseline;
        }
        .product-detail-price-old {
            color: var(--muted);
            text-decoration: line-through;
            font-size: 1.5rem;
            font-weight: 400;
        }
        .product-detail-actions {
            margin-top: 2rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            color: var(--primary);
        }
        @media (max-width: 768px) {
            .product-detail-card {
                grid-template-columns: 1fr;
                padding: 2rem;
            }
        }
    </style>
</head>
<body class="page-shop">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="product-detail-wrapper">
    <div class="product-detail-container">
        <a href="shop.php" class="back-link">← Back to Shop</a>
        
        <div class="product-detail-card">
            <div>
                <img src="<?= sanitize(resolveProductImage($product['image'] ?? '')); ?>" 
                     alt="<?= sanitize($product['name']); ?>" 
                     class="product-detail-image">
            </div>
            
            <div class="product-detail-info">
                <h1><?= sanitize($product['name']); ?></h1>
                
                <div class="product-detail-rating">
                    <?= renderStars((int) ($product['rating'] ?? 0)); ?>
                </div>
                
                <?php if (!empty($product['description'])): ?>
                    <div class="product-detail-description">
                        <?= nl2br(sanitize($product['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-detail-price">
                    <?php if (!empty($product['old_price'])): ?>
                        <span class="product-detail-price-old"><?= formatPrice((float) $product['old_price']); ?></span>
                    <?php endif; ?>
                    <span><?= formatPrice((float) $product['price']); ?></span>
                </div>
                
                <div class="product-detail-actions">
                    <?php if (!$isLoggedIn): ?>
                        <a class="btn primary" href="login.php?redirect=<?= urlencode($currentUrl); ?>" style="display: inline-block; padding: 1rem 2rem;">Login to add to cart</a>
                    <?php else: ?>
                        <form class="add-to-cart-form" method="post" action="cart.php" data-product-id="<?= (int) $product['id']; ?>" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="set">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">
                            <input type="hidden" name="redirect" value="<?= $currentUrl; ?>">
                            <div class="quantity-control small">
                                <button type="button" class="qty-minus" aria-label="Decrease quantity">-</button>
                                <span class="qty-value"><?= $cartQty; ?></span>
                                <button type="button" class="qty-plus" aria-label="Increase quantity">+</button>
                            </div>
                            <input type="hidden" name="quantity" value="<?= $cartQty; ?>" class="qty-input">
                            <button class="btn primary" type="submit" style="padding: 1rem 2rem;">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<?php if ($isLoggedIn): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.add-to-cart-form');
    if (!form) return;
    
    const qtyInput = form.querySelector('.qty-input');
    const qtyValue = form.querySelector('.qty-value');
    const minusBtn = form.querySelector('.qty-minus');
    const plusBtn = form.querySelector('.qty-plus');

    function setQty(val) {
        const q = Math.max(0, val);
        qtyInput.value = q;
        qtyValue.textContent = q;
    }

    function submitQty(qty) {
        const fd = new FormData(form);
        fd.set('quantity', qty);
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        fetch(form.getAttribute('action') || 'cart.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).catch(() => {}).finally(() => {
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    minusBtn.addEventListener('click', function() {
        const next = parseInt(qtyInput.value || '0', 10) - 1;
        setQty(next);
        submitQty(next);
    });
    
    plusBtn.addEventListener('click', function() {
        const next = parseInt(qtyInput.value || '0', 10) + 1;
        setQty(next);
        submitQty(next);
    });

    setQty(parseInt(qtyInput.value || '0', 10));

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let qty = parseInt(qtyInput.value || '0', 10);
        if (qty < 1) {
            qty = 1;
        }
        setQty(qty);
        submitQty(qty);
    });
});
</script>
<?php endif; ?>

</body>
</html>

