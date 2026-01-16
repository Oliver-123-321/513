<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/db.php';

ensureSession();

$errors = [];
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($username === '' || $email === '' || $phone === '') {
        $errors[] = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Create register table if it doesn't exist
            $conn->exec("
                CREATE TABLE IF NOT EXISTS register (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                $errors[] = 'An account with that email already exists.';
            } else {
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO register (username, email, phone, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $result = $stmt->execute([$username, $email, $phone]);
                
                if ($result) {
                    // auto-login
                    $_SESSION['user'] = ['email' => $email, 'username' => $username];
                    // Redirect to subscription page
                    header('Location: http://oliver.gamer.gd/513/substription/');
                    exit;
                } else {
                    $errors[] = 'Unable to save user. Please try again.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }

    if (!empty($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['resume'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error.';
        } else {
            // basic limits
            $maxBytes = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxBytes) {
                $error = 'File is too large (max 5MB).';
            } else {
                // verify content type using finfo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = [
                    'application/pdf' => 'pdf',
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                if (!array_key_exists($mime, $allowed)) {
                    $error = 'Invalid file type. Only PDF or images allowed.';
                } else {
                    // build destination path
                    $ext = $allowed[$mime];
                    $uploadsDir = __DIR__ . '/uploads/resumes';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }
                    // create unique filename
                    $basename = bin2hex(random_bytes(8)); // 16 hex chars
                    $filename = $basename . '.' . $ext;
                    $dest = $uploadsDir . '/' . $filename;

                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $error = 'Failed to move uploaded file.';
                    } else {
                        // success: $dest contains the uploaded file path on server
                        // store relative path for DB or session, e.g. 'uploads/resumes/'.$filename
                        $storedResumePath = 'uploads/resumes/' . $filename;
                    }
                }
            }
        }
    }
}

$pageTitle = 'Snack Shop · Register';
$currentPage = 'register';
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
</head>
<body class="page-register">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="auth-wrapper">
    <div class="container">
        <div class="auth-card filter-card">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                <div>
                    <p class="eyebrow">Account</p>
                    <h1>Create Account</h1>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="field">
                    <div style="color:#c23; font-weight:600; margin-bottom:0.75rem;">
                        <?= implode('<br>', array_map('sanitize', $errors)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" enctype="multipart/form-data">
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= sanitize($email); ?>" required>
                </label>

                <label class="field">
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?= sanitize($phone); ?>" required>
                </label>

                <button class="btn primary full-width" type="submit">Register</button>
            </form>

            <p class="auth-footer">Already have an account? <a href="login.php" class="auth-link">Login here</a></p>

        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
