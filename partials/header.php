<?php
$navItems = [
    ['key' => 'home', 'label' => 'Home', 'href' => 'index.php'],
    ['key' => 'shop', 'label' => 'Shop', 'href' => 'shop.php'],
    ['key' => 'cart', 'label' => 'Cart', 'href' => 'cart.php'],
    ['key' => 'about', 'label' => 'About', 'href' => 'about.php'],
    ['key' => 'recruit', 'label' => 'Recruit', 'href' => 'recruit.php'],
    ['key' => 'forum', 'label' => 'Forum', 'href' => 'forum.php'],
    ['key' => 'support', 'label' => 'Support', 'href' => 'support.php'],
    ['key' => 'customer-list', 'label' => 'Customer List', 'href' => 'customer-list.php'],
];

// Check if session is active and user is logged in
$isLoggedIn = false;
$isAdminLoggedIn = false;
$userName = '';
if (session_status() === PHP_SESSION_ACTIVE) {
    $isLoggedIn = !empty($_SESSION['user'] ?? null);
    $isAdminLoggedIn = !empty($_SESSION['admin']['logged_in'] ?? null);
    if ($isLoggedIn && isset($_SESSION['user']['username'])) {
        $userName = $_SESSION['user']['username'];
    } elseif ($isLoggedIn && isset($_SESSION['user']['email'])) {
        $userName = $_SESSION['user']['email'];
    }
}
?>
 
<header class="site-header">
    <div class="container header-inner">
        <nav class="primary-nav" aria-label="Main Navigation">
            <a class="logo" href="index.php" aria-label="Snack Shop">
                <img src="21.png" alt="Snack Shop Logo" class="logo-icon">
            </a>
            <?php foreach ($navItems as $item): ?>
                <a
                    href="<?= $item['href']; ?>"
                    class="<?= ($currentPage ?? '') === $item['key'] ? 'is-active' : ''; ?>"
                >
                    <?= $item['label']; ?>
                </a>
            <?php endforeach; ?>
            <?php if ($isAdminLoggedIn): ?>
                <a href="admin.php" class="<?= ($currentPage ?? '') === 'admin' ? 'is-active' : ''; ?>">Admin</a>
            <?php else: ?>
                <a href="admin-login.php" class="<?= ($currentPage ?? '') === 'admin-login' ? 'is-active' : ''; ?>">Admin</a>
            <?php endif; ?>
            <div class="header-actions">
            <?php if ($isLoggedIn): ?>
                <a href="logout.php" class="<?= ($currentPage ?? '') === 'logout' ? 'is-active' : ''; ?>">Logout</a>
            <?php else: ?>
                <a href="login.php" class="<?= ($currentPage ?? '') === 'login' ? 'is-active' : ''; ?>">Login</a>
                <a href="http://oliver.gamer.gd/513/substription/" class="<?= ($currentPage ?? '') === 'register' ? 'is-active' : ''; ?>">Register</a>
            <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

