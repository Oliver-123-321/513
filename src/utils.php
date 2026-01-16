<?php
declare(strict_types=1);

function loadProducts(): array
{
    $path = __DIR__ . '/../data/products.json';
    if (!file_exists($path)) {
        return [];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $data['products'] ?? [];
}

function bestSellingProducts(array $products, int $limit = 4): array
{
    $best = array_filter($products, static fn ($product) => !empty($product['best_seller']));
    return array_slice(array_values($best), 0, $limit);
}

function getCategoriesWithCount(array $products): array
{
    $categories = [];

    foreach ($products as $product) {
        $category = $product['category'] ?? 'Other';
        $categories[$category] = ($categories[$category] ?? 0) + 1;
    }

    ksort($categories);

    return $categories;
}

function formatPrice(float $price): string
{
    return '$' . number_format($price, 2);
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function toLowercase(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }

    return strtolower($value);
}

function containsInsensitive(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle) !== false;
    }

    return stripos($haystack, $needle) !== false;
}

function filterProducts(array $products, array $criteria): array
{
    $search = toLowercase(trim($criteria['search'] ?? ''));
    $category = $criteria['category'] ?? '';
    $min = is_numeric($criteria['min_price'] ?? null) ? (float) $criteria['min_price'] : null;
    $max = is_numeric($criteria['max_price'] ?? null) ? (float) $criteria['max_price'] : null;

    return array_values(
        array_filter(
            $products,
            static function ($product) use ($search, $category, $min, $max) {
                $productName = toLowercase($product['name'] ?? '');
                $matchesSearch = containsInsensitive($productName, $search);
                $matchesCategory = $category === '' || $product['category'] === $category;
                $matchesMin = $min === null || $product['price'] >= $min;
                $matchesMax = $max === null || $product['price'] <= $max;

                return $matchesSearch && $matchesCategory && $matchesMin && $matchesMax;
            }
        )
    );
}

function sortProducts(array $products, string $sort): array
{
    return match ($sort) {
        'price_desc' => sortBy($products, 'price', 'desc'),
        'price_asc' => sortBy($products, 'price', 'asc'),
        'name_asc' => sortBy($products, 'name', 'asc'),
        'name_desc' => sortBy($products, 'name', 'desc'),
        default => $products,
    };
}

function sortBy(array $products, string $key, string $direction = 'asc'): array
{
    usort(
        $products,
        static function ($a, $b) use ($key, $direction) {
            $valueA = $a[$key] ?? null;
            $valueB = $b[$key] ?? null;

            if ($valueA == $valueB) {
                return 0;
            }

            if ($direction === 'asc') {
                return ($valueA < $valueB) ? -1 : 1;
            }

            return ($valueA > $valueB) ? -1 : 1;
        }
    );

    return $products;
}

function renderStars(int $rating = 0): string
{
    $rating = max(0, min(5, $rating));
    $filled = str_repeat('★', $rating);
    $empty = str_repeat('☆', 5 - $rating);

    return sprintf('<span class="stars">%s%s</span>', $filled, $empty);
}

function ensureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getCart(): array
{
    ensureSession();
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    return $_SESSION['cart'];
}

function addProductToCart(int $productId, int $quantity = 1): void
{
    if ($productId <= 0 || $quantity <= 0) {
        return;
    }

    ensureSession();
    $cart = getCart();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function adjustProductQuantity(int $productId, int $delta): void
{
    if ($productId <= 0 || $delta === 0) {
        return;
    }

    ensureSession();
    $cart = getCart();
    $current = $cart[$productId] ?? 0;
    $newQuantity = $current + $delta;

    if ($newQuantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $newQuantity;
    }

    $_SESSION['cart'] = $cart;
}

function setProductQuantity(int $productId, int $quantity): void
{
    ensureSession();
    $cart = getCart();

    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }

    $_SESSION['cart'] = $cart;
}

function buildCartItems(array $products, array $cart): array
{
    $map = [];
    foreach ($products as $product) {
        $map[$product['id']] = $product;
    }

    $items = [];
    $subtotal = 0;

    foreach ($cart as $productId => $quantity) {
        if (!isset($map[$productId])) {
            continue;
        }

        $product = $map[$productId];
        $lineTotal = $product['price'] * $quantity;
        $subtotal += $lineTotal;

        $items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    return [
        'items' => $items,
        'subtotal' => $subtotal,
    ];
}

