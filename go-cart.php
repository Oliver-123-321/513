<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();

// If user is logged in, go to cart. Otherwise redirect to login with redirect back.
if (!empty($_SESSION['user'])) {
    header('Location: cart.php');
    exit;
}

// Not logged in — send to login and ask to return to cart afterwards
$redirect = 'cart.php';
header('Location: login.php?redirect=' . urlencode($redirect));
exit;
