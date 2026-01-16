<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

$products = loadProducts();
$bestSelling = bestSellingProducts($products);
$pageTitle = 'Snack Shop · Handmade snacks';
$currentPage = 'home';
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
<body class="page-home">
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-media">
                <img src="1.jpg" alt="手作汤圆">
            </div>
            <div class="hero-copy">
                <p class="eyebrow">Best Snack Selection</p>
                <h1>Enjoy Our Delicious Snacks!</h1>
                <p>
                    Welcome to Snack Shop – Every bite is full of surprises. Explore now and add a touch of sweetness to every moment!Our one-stop selection of homemade snacks: crispy nuts, soft pastries, homemade cookies and healthy beverages.
Every bite is full of surprises. Explore now and add a touch of sweetness to every moment!
                </p>
                <a class="btn primary" href="login.php">Shop Now</a>
            </div>
        </div>
    </section>

    <section class="benefits">
        <div class="container benefits-grid">
            <?php
            $benefits = [
                ['icon' => '🧁', 'title' => 'Handmade Snacks', 'text' => 'Delicious & Healthy'],
                ['icon' => '🥨', 'title' => 'Freshly Packed', 'text' => 'Crunchy & Delicious'],
                ['icon' => '📦', 'title' => 'Great Snack Deals', 'text' => 'Save Every Bite'],
                ['icon' => '💳', 'title' => 'Easy Refund', 'text' => 'Buy & Enjoy, Risk-Free'],
            ];
            foreach ($benefits as $benefit): ?>
                <article class="benefit-card">
                    <span class="benefit-icon"><?= $benefit['icon']; ?></span>
                    <h3><?= $benefit['title']; ?></h3>
                    <p><?= $benefit['text']; ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="best-selling">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Best Selling Products</p>
                    <h2>Highly popular and best-selling · Best Selling Products</h2>
                </div>
            </div>

            <div class="product-grid">
                <?php foreach ($bestSelling as $product): ?>
                    <article class="product-card">
                        <img src="<?= sanitize($product['image']); ?>" alt="<?= sanitize($product['name']); ?>">
                        <h3><?= sanitize($product['name']); ?></h3>
                        <div class="product-rating" aria-label="Scoring">
                            <?= renderStars((int) ($product['rating'] ?? 0)); ?>
                        </div>
                        <p class="price">
                            <?php if (!empty($product['old_price'])): ?>
                                <span class="price-old"><?= formatPrice((float) $product['old_price']); ?></span>
                            <?php endif; ?>
                            <span><?= formatPrice((float) $product['price']); ?></span>
                        </p>
                        <button class="btn outline">Add to Cart</button>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="slider-dots" aria-hidden="true">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

