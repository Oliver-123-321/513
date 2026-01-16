<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();

// Check if admin logout
$isAdminLogout = isset($_GET['admin']) && $_GET['admin'] === '1';

// Destroy session
session_destroy();

// Redirect based on logout type
if ($isAdminLogout) {
    header('Location: admin-login.php');
} else {
    header('Location: index.php');
}
exit;

