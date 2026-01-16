<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();

$errors = [];
$username = '';

// Admin credentials (in production, use database or secure config)
$adminUsername = 'admin';
$adminPassword = 'admin123'; // Change this in production!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter username and password.';
    } elseif ($username === $adminUsername && $password === $adminPassword) {
        $_SESSION['admin'] = [
            'username' => $username,
            'logged_in' => true,
            'login_time' => time()
        ];
        
        header('Location: admin.php');
        exit;
    } else {
        $errors[] = 'Invalid username or password.';
    }
}

$pageTitle = 'Snack Shop · Admin Login';
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
        .auth-wrapper { padding: 3rem 5vw; min-height: calc(100vh - 200px); }
        .auth-card { max-width: 440px; margin: 0 auto; }
    </style>
</head>
<body class="page-login">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="auth-wrapper">
    <div class="container">
        <div class="auth-card filter-card">
            <p class="eyebrow">Administrator</p>
            <h1>Admin Login</h1>

            <?php if (!empty($errors)): ?>
                <div class="field">
                    <div style="color:#c23; font-weight:600; margin-bottom:0.75rem;">
                        <?= implode('<br>', array_map('sanitize', $errors)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="admin-login.php">
                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" value="<?= sanitize($username); ?>" required autofocus>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>

                <p style="margin:0 0 1rem; color:var(--muted); font-size:0.9rem;">
                    Admin account: <strong>admin</strong> / <strong>admin123</strong> (please change ASAP)
                </p>

                <button class="btn primary full-width" type="submit">Login</button>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

