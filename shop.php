<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();
$isLoggedIn = !empty($_SESSION['user'] ?? null);

$products = loadProducts();
$cart = getCart();
if (!$isLoggedIn) {
    $cart = [];
}
$categories = getCategoriesWithCount($products);
$priceList = array_column($products, 'price');
if (!empty($priceList)) {
    $priceFloor = (int) floor(min($priceList));
    $priceCeil = (int) ceil(max($priceList));
} else {
    // No products — use sensible defaults to avoid min()/max() errors
    $priceFloor = 0;
    $priceCeil = 0;
}

$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? $priceFloor;
$maxPrice = $_GET['max_price'] ?? $priceCeil;
$sort = $_GET['sort'] ?? '';

$filtered = filterProducts(
    $products,
    [
        'search' => $search,
        'category' => $categoryFilter,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
    ]
);

$sortedProducts = sortProducts($filtered, $sort);
$totalProducts = count($sortedProducts);
$pageTitle = 'Snack Shop · Shop';
$currentPage = 'shop';
$currentUrl = sanitize($_SERVER['REQUEST_URI'] ?? 'shop.php');

// Helper: resolve product image path. Prefer assets/images/<name> if exists.
function resolveProductImage(string $image): string
{
    $candidate = 'assets/images/' . $image;
    if (file_exists(__DIR__ . '/' . $candidate)) {
        return $candidate;
    }
    // fallback to provided path (could be root or full URL)
    return $image;
}
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
<body class="page-shop">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="shop-layout">
    <aside class="shop-sidebar">
        <form class="filter-card" method="get">
            <label class="field">
                <span>Search products…</span>
                <input type="text" name="search" value="<?= sanitize($search); ?>" placeholder="Search products…">
            </label>

            <section class="price-filter">
                <div class="filter-heading">
                    <h3>Filter by price</h3>
                    <p class="price-range-hint">$<?= $priceFloor; ?> – $<?= $priceCeil; ?></p>
                </div>
                <div class="price-inputs">
                    <input type="number" name="min_price" value="<?= (int) $minPrice; ?>" aria-label="Minimum price" min="<?= $priceFloor; ?>" max="<?= $priceCeil; ?>">
                    <span>—</span>
                    <input type="number" name="max_price" value="<?= (int) $maxPrice; ?>" aria-label="Maximum price" min="<?= $priceFloor; ?>" max="<?= $priceCeil; ?>">
                </div>
            </section>

            <div class="category-filter">
                <h3>Categories</h3>
                <ul>
                    <li>
                        <button type="submit" name="category" value="" class="<?= $categoryFilter === '' ? 'is-active' : ''; ?>">
                            All products (<?= count($products); ?>)
                        </button>
                    </li>
                    <?php foreach ($categories as $category => $count): ?>
                        <li>
                            <button type="submit" name="category" value="<?= sanitize($category); ?>" class="<?= $categoryFilter === $category ? 'is-active' : ''; ?>">
                                <?= sanitize($category); ?> (<?= $count; ?>)
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <button class="btn primary full-width" type="submit">Apply filters</button>
        </form>

    </aside>

    <section class="shop-content">
        <div class="shop-header">
            <div>
                <p class="eyebrow">Home / Shop</p>
                <h1>Shop</h1>
                <p>Showing <?= $totalProducts; ?> of <?= count($products); ?> results</p>
            </div>
            <form class="sort-form" method="get">
                <label>
                Default product sorting
                    <select name="sort" onchange="this.form.submit()">
                        <option value="" <?= $sort === '' ? 'selected' : ''; ?>>Default sorting</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : ''; ?>>Name：A-Z</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : ''; ?>>Name：Z-A</option>
                    </select>
                </label>
                <input type="hidden" name="search" value="<?= sanitize($search); ?>">
                <input type="hidden" name="category" value="<?= sanitize($categoryFilter); ?>">
                <input type="hidden" name="min_price" value="<?= (int) $minPrice; ?>">
                <input type="hidden" name="max_price" value="<?= (int) $maxPrice; ?>">
            </form>
        </div>

        <div class="product-grid shop-grid">
            <?php if (empty($sortedProducts)): ?>
                <div class="filter-card" style="padding:2rem;">No products found.</div>
            <?php else: ?>
                <?php foreach ($sortedProducts as $product): ?>
                <?php $cartQty = $isLoggedIn ? (int) ($cart[$product['id']] ?? 0) : 0; ?>
                <article class="product-card">
                    <?php if ($cartQty > 0): ?>
                        <span class="product-qty-badge"><?= $cartQty; ?></span>
                    <?php endif; ?>
                    <div class="product-card-link" data-product-id="<?= (int) $product['id']; ?>" data-product-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" data-product-image="<?= htmlspecialchars(resolveProductImage($product['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-product-rating="<?= (int) ($product['rating'] ?? 0); ?>" data-product-description="<?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-product-price="<?= (float) $product['price']; ?>" data-product-old-price="<?= !empty($product['old_price']) ? (float) $product['old_price'] : ''; ?>">
                        <img src="<?= sanitize(resolveProductImage($product['image'] ?? '')); ?>" alt="<?= sanitize($product['name']); ?>">
                        <h3><?= sanitize($product['name']); ?></h3>
                        <div class="product-rating">
                            <?= renderStars((int) ($product['rating'] ?? 0)); ?>
                        </div>
                        <?php if (!empty($product['description'])): ?>
                            <p class="product-description"><?= sanitize($product['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="product-footer">
                        <p class="price">
                            <?php if (!empty($product['old_price'])): ?>
                                <span class="price-old"><?= formatPrice((float) $product['old_price']); ?></span>
                            <?php endif; ?>
                            <span><?= formatPrice((float) $product['price']); ?></span>
                        </p>
                        <?php if (!$isLoggedIn): ?>
                            <a class="btn outline" href="login.php?redirect=<?= urlencode($currentUrl); ?>">Login to add</a>
                        <?php else: ?>
                        <form class="add-to-cart-form" method="post" action="cart.php" data-product-id="<?= (int) $product['id']; ?>">
                            <input type="hidden" name="action" value="set">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">
                            <input type="hidden" name="redirect" value="<?= $currentUrl; ?>">
                            <div class="quantity-control small">
                                <button type="button" class="qty-minus" aria-label="Decrease quantity">-</button>
                                <span class="qty-value"><?= $cartQty; ?></span>
                                <button type="button" class="qty-plus" aria-label="Increase quantity">+</button>
                            </div>
                            <input type="hidden" name="quantity" value="<?= $cartQty; ?>" class="qty-input">
                            <button class="btn outline" type="submit">Add to Cart</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<div class="container" style="padding:2rem 0; text-align:center;">
    <a href="go-cart.php" class="btn primary" style="padding:0.9rem 2rem;">Confirm</a>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

<!-- Product Detail Modal -->
<div id="productModal" class="product-modal">
    <div class="product-modal-overlay"></div>
    <div class="product-modal-content">
        <button class="product-modal-close" aria-label="Close modal">&times;</button>
        <div class="product-modal-body">
            <div class="product-modal-image-wrapper">
                <img id="modalProductImage" src="" alt="" class="product-modal-image">
            </div>
            <div class="product-modal-info">
                <h2 id="modalProductName"></h2>
                <div id="modalProductRating" class="product-modal-rating"></div>
                <div id="modalProductDescription" class="product-modal-description"></div>
                <div class="product-modal-price-wrapper">
                    <span id="modalProductOldPrice" class="product-modal-price-old"></span>
                    <span id="modalProductPrice" class="product-modal-price"></span>
                </div>
                <div class="product-modal-actions">
                    <div id="modalProductLogin" style="display: none;">
                        <a class="btn primary" href="login.php?redirect=<?= urlencode($currentUrl); ?>" style="display: inline-block; padding: 1rem 2rem;">Login to add to cart</a>
                    </div>
                    <form id="modalProductForm" class="add-to-cart-form-modal" method="post" action="cart.php" style="display: none;">
                        <input type="hidden" name="action" value="set">
                        <input type="hidden" name="product_id" id="modalProductId" value="">
                        <input type="hidden" name="redirect" value="<?= $currentUrl; ?>">
                        <div class="quantity-control small">
                            <button type="button" class="qty-minus-modal" aria-label="Decrease quantity">-</button>
                            <span class="qty-value-modal">0</span>
                            <button type="button" class="qty-plus-modal" aria-label="Increase quantity">+</button>
                        </div>
                        <input type="hidden" name="quantity" id="modalProductQuantity" value="0" class="qty-input-modal">
                        <button class="btn primary" type="submit" style="padding: 1rem 2rem;">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modal = document.getElementById('productModal');
    const modalOverlay = modal.querySelector('.product-modal-overlay');
    const modalClose = modal.querySelector('.product-modal-close');
    const productCardLinks = document.querySelectorAll('.product-card-link');
    
    function openModal(productData) {
        document.getElementById('modalProductImage').src = productData.image;
        document.getElementById('modalProductImage').alt = productData.name;
        document.getElementById('modalProductName').textContent = productData.name;
        document.getElementById('modalProductId').value = productData.id;
        
        // Render stars
        const ratingEl = document.getElementById('modalProductRating');
        ratingEl.innerHTML = '';
        const stars = '★'.repeat(productData.rating) + '☆'.repeat(5 - productData.rating);
        ratingEl.innerHTML = '<span class="stars">' + stars + '</span>';
        
        // Description
        const descEl = document.getElementById('modalProductDescription');
        if (productData.description) {
            descEl.textContent = productData.description;
            descEl.style.display = 'block';
        } else {
            descEl.style.display = 'none';
        }
        
        // Price
        const priceEl = document.getElementById('modalProductPrice');
        priceEl.textContent = '$' + productData.price.toFixed(2);
        
        const oldPriceEl = document.getElementById('modalProductOldPrice');
        if (productData.oldPrice) {
            oldPriceEl.textContent = '$' + productData.oldPrice.toFixed(2);
            oldPriceEl.style.display = 'inline';
        } else {
            oldPriceEl.style.display = 'none';
        }
        
        // Show/hide login or cart form
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false'; ?>;
        const cartQty = <?= json_encode($cart); ?>;
        const currentQty = cartQty[productData.id] || 0;
        
        if (isLoggedIn) {
            document.getElementById('modalProductLogin').style.display = 'none';
            document.getElementById('modalProductForm').style.display = 'flex';
            document.querySelector('.qty-value-modal').textContent = currentQty;
            document.getElementById('modalProductQuantity').value = currentQty;
        } else {
            document.getElementById('modalProductLogin').style.display = 'block';
            document.getElementById('modalProductForm').style.display = 'none';
        }
        
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
    
    productCardLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't open modal if clicking on the card itself when it's inside a link
            if (e.target.closest('.add-to-cart-form')) {
                return;
            }
            
            const productData = {
                id: parseInt(link.getAttribute('data-product-id'), 10),
                name: link.getAttribute('data-product-name'),
                image: link.getAttribute('data-product-image'),
                rating: parseInt(link.getAttribute('data-product-rating'), 10),
                description: link.getAttribute('data-product-description'),
                price: parseFloat(link.getAttribute('data-product-price')),
                oldPrice: link.getAttribute('data-product-old-price') ? parseFloat(link.getAttribute('data-product-old-price')) : null
            };
            openModal(productData);
        });
    });
    
    modalOverlay.addEventListener('click', closeModal);
    modalClose.addEventListener('click', closeModal);
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
    
    // Modal cart form functionality
    const modalForm = document.getElementById('modalProductForm');
    if (modalForm) {
        const modalQtyInput = document.getElementById('modalProductQuantity');
        const modalQtyValue = modalForm.querySelector('.qty-value-modal');
        const modalMinusBtn = modalForm.querySelector('.qty-minus-modal');
        const modalPlusBtn = modalForm.querySelector('.qty-plus-modal');
        
        function setModalQty(val) {
            const q = Math.max(0, val);
            modalQtyInput.value = q;
            modalQtyValue.textContent = q;
        }
        
        function submitModalQty(qty) {
            // Ensure quantity is at least 0
            qty = Math.max(0, qty);
            // Update UI first
            setModalQty(qty);
            
            const fd = new FormData(modalForm);
            fd.set('quantity', qty);
            const submitBtn = modalForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            const productId = parseInt(document.getElementById('modalProductId').value, 10);
            fetch(modalForm.getAttribute('action') || 'cart.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).then(() => {
                // Ensure quantity display stays correct after fetch
                setModalQty(qty);
                // Update quantity badge on product card
                const productCard = document.querySelector('.product-card-link[data-product-id="' + productId + '"]');
                if (productCard) {
                    const card = productCard.closest('.product-card');
                    if (card) {
                        let badge = card.querySelector('.product-qty-badge');
                        if (qty > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'product-qty-badge';
                                card.prepend(badge);
                            }
                            badge.textContent = qty;
                        } else if (badge) {
                            badge.remove();
                        }
                        // Update quantity in the card's form
                        const cardForm = card.querySelector('.add-to-cart-form');
                        if (cardForm) {
                            const cardQtyInput = cardForm.querySelector('.qty-input');
                            const cardQtyValue = cardForm.querySelector('.qty-value');
                            if (cardQtyInput) cardQtyInput.value = qty;
                            if (cardQtyValue) cardQtyValue.textContent = qty;
                        }
                    }
                }
            }).catch(() => {
                // On error, still keep the UI updated
                setModalQty(qty);
            }).finally(() => {
                if (submitBtn) submitBtn.disabled = false;
            });
        }
        
        if (modalMinusBtn) {
            modalMinusBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const current = parseInt(modalQtyInput.value || '0', 10);
                const next = Math.max(0, current - 1);
                submitModalQty(next);
            });
        }
        
        if (modalPlusBtn) {
            modalPlusBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const current = parseInt(modalQtyInput.value || '0', 10);
                const next = current + 1;
                submitModalQty(next);
            });
        }
        
        modalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let qty = parseInt(modalQtyInput.value || '0', 10);
            if (qty < 1) {
                qty = 1;
            }
            setModalQty(qty);
            
            // Submit and close modal on success
            const fd = new FormData(modalForm);
            fd.set('quantity', qty);
            const submitBtn = modalForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            const productId = parseInt(document.getElementById('modalProductId').value, 10);
            
            fetch(modalForm.getAttribute('action') || 'cart.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).then(() => {
                // Update quantity display
                setModalQty(qty);
                // Update quantity badge on product card
                const productCard = document.querySelector('.product-card-link[data-product-id="' + productId + '"]');
                if (productCard) {
                    const card = productCard.closest('.product-card');
                    if (card) {
                        let badge = card.querySelector('.product-qty-badge');
                        if (qty > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'product-qty-badge';
                                card.prepend(badge);
                            }
                            badge.textContent = qty;
                        } else if (badge) {
                            badge.remove();
                        }
                        // Update quantity in the card's form
                        const cardForm = card.querySelector('.add-to-cart-form');
                        if (cardForm) {
                            const cardQtyInput = cardForm.querySelector('.qty-input');
                            const cardQtyValue = cardForm.querySelector('.qty-value');
                            if (cardQtyInput) cardQtyInput.value = qty;
                            if (cardQtyValue) cardQtyValue.textContent = qty;
                        }
                    }
                }
                // Close modal after successful add
                closeModal();
            }).catch(() => {
                setModalQty(qty);
            }).finally(() => {
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
    
    // Prevent product card link clicks when clicking on cart form buttons
    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        form.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        const qtyInput = form.querySelector('.qty-input');
        const qtyValue = form.querySelector('.qty-value');
        const minusBtn = form.querySelector('.qty-minus');
        const plusBtn = form.querySelector('.qty-plus');
        const card = form.closest('.product-card');

        function updateBadge(val) {
            if (!card) return;
            let badge = card.querySelector('.product-qty-badge');
            if (val > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'product-qty-badge';
                    card.prepend(badge);
                }
                badge.textContent = val;
            } else if (badge) {
                badge.remove();
            }
        }

        function setQty(val) {
            const q = Math.max(0, val);
            qtyInput.value = q;
            qtyValue.textContent = q;
            updateBadge(q);
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

        minusBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const next = parseInt(qtyInput.value || '1', 10) - 1;
            setQty(next);
            submitQty(parseInt(qtyInput.value || '0', 10));
        });
        plusBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const next = parseInt(qtyInput.value || '1', 10) + 1;
            setQty(next);
            submitQty(parseInt(qtyInput.value || '0', 10));
        });

        // Keep badge synced on load with current input value
        setQty(parseInt(qtyInput.value || '1', 10));

        // On submit, enforce at least 1 so add works, then sync badge
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            let qty = Math.max(0, parseInt(qtyInput.value || '0', 10));
            if (qty < 1) {
                qty = 1;
                setQty(qty);
            } else {
                setQty(qty); // sync UI & badge
            }
            setQty(qty); // sync UI & badge

            // Submit via fetch to avoid page reload
            submitQty(qty);
        });
    });
});
</script>

</body>
</html>

