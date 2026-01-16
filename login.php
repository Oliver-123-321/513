<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/db.php';

ensureSession();

$errors = [];
$email = '';
$phone = '';
$redirect = trim($_REQUEST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($email === '' || $phone === '') {
        $errors[] = 'Please enter email and phone.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Try to verify user in wpfw_fc_subscribers table
            // Try multiple queries to handle different column structures
            $queries = [
                "SELECT id, email, phone, name FROM wpfw_fc_subscribers WHERE email = ? AND phone = ?",
                "SELECT id, email, phone, CONCAT_WS(' ', first_name, last_name) AS name FROM wpfw_fc_subscribers WHERE email = ? AND phone = ?",
                "SELECT id, email, phone, '' AS name FROM wpfw_fc_subscribers WHERE email = ? AND phone = ?",
            ];
            
            $user = null;
            foreach ($queries as $sql) {
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$email, $phone]);
                    $user = $stmt->fetch();
                    if ($user) {
                        break;
                    }
                } catch (Exception $inner) {
                    // Try next query
                    continue;
                }
            }
            
            if ($user) {
                // Login successful
                $_SESSION['user'] = [
                    'email' => $user['email'],
                    'username' => $user['name'] ?? '',
                    'id' => $user['id'] ?? 0,
                ];
                // Clear cart on every login
                $_SESSION['cart'] = [];
                
                // Redirect to redirect parameter or shop page
                $redirectUrl = $redirect !== '' ? $redirect : 'shop.php';
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $errors[] = 'Invalid email or phone.';
            }
        } catch (Exception $e) {
            $errors[] = 'Login failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Snack Shop · Login';
$currentPage = 'login';
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
        /* Local styles for centering the login card; reuses existing variables/styles */
        .auth-wrapper { padding: 3rem 5vw; }
        .auth-card { max-width: 440px; margin: 0 auto; }
    </style>
</head>
<body class="page-login">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="auth-wrapper">
    <div class="container">
        <div class="auth-card filter-card">
            <p class="eyebrow">Account</p>
            <h1>Login</h1>

            <?php if (!empty($errors)): ?>
                <div class="field">
                    <div style="color:#c23; font-weight:600; margin-bottom:0.75rem;">
                        <?= implode('<br>', array_map('sanitize', $errors)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <?php if ($redirect !== ''): ?>
                    <input type="hidden" name="redirect" value="<?= sanitize($redirect); ?>">
                <?php endif; ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= sanitize($email); ?>" required>
                </label>

                <label class="field">
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?= sanitize($phone); ?>" required>
                </label>

                <button class="btn primary full-width" type="submit">Login</button>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
