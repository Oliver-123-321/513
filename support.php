<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/storage.php';

ensureSession();

// Enable verbose errors for debugging (remove or disable in production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$errors = [];
$success = '';

// Initialize database tables (disabled by default to avoid blocking page load when DB is unreachable)
// If you need to run initialization, visit /support.php?run_init=1 while signed in as admin.
if (isset($_GET['run_init']) && !empty($_GET['run_init']) && !empty($_SESSION['user']['is_admin'])) {
    try {
        if (function_exists('initDatabaseTables')) {
            initDatabaseTables();
            $success = 'Database initialized.';
        }
    } catch (Exception $e) {
        $errors[] = 'Database initialization error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'Please fill in name, email and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    } else {
        try {
            // require DB only when needed to avoid connecting on page load
            if (!function_exists('getDbConnection') && file_exists(__DIR__ . '/src/db.php')) {
                require_once __DIR__ . '/src/db.php';
            }
            $conn = getDbConnection();
            $stmt = $conn->prepare("
                INSERT INTO Support (name, email, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$name, $email, $subject ?: null, $message]);
            
            if ($result) {
                $success = 'Thank you — your feedback has been saved to the database.';
                
                // Also save to JSON as backup
                $item = [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                ];
                saveFeedback($item);
                
                // Try to email vendor (optional) — configure vendor email below if desired
                $vendorEmail = ''; // e.g. 'vendor@example.com'
                if ($vendorEmail !== '') {
                    $body = "Feedback from {$name} ({$email})\n\nSubject: {$subject}\n\n{$message}";
                    @mail($vendorEmail, "Customer Feedback: " . ($subject ?: 'No subject'), $body, "From: {$email}\r\nReply-To: {$email}");
                }
            } else {
                $errors[] = 'Unable to save feedback. Please try again later.';
            }
        } catch (Exception $e) {
            // Fallback to JSON storage if database fails
            $item = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ];
            
            if (saveFeedback($item)) {
                $success = 'Thank you — your feedback has been saved (database unavailable, saved locally).';
            } else {
                $errors[] = 'Unable to save feedback. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Snack Shop · Customer Support';
$currentPage = 'support';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .support-card { max-width:800px; margin:2rem auto; padding:2rem; }
        .support-card .field input, .support-card .field textarea { width:100%; }
    </style>
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <div class="container">
        <div class="support-card filter-card">
            <h1>Customer Support</h1>
            <p>If you need to contact our vendors or send feedback, use the form below. Submissions are saved for our team.</p>

            <?php if (!empty($errors)): ?>
                <div style="color:#c23; font-weight:600; margin-bottom:0.75rem;">
                    <?= implode('<br>', array_map('sanitize', $errors)); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="color:green; font-weight:600; margin-bottom:0.75rem;"><?= sanitize($success); ?></div>
            <?php endif; ?>

            <form method="post" action="support.php">
                <label class="field"><span>Name</span><input type="text" name="name" required></label>
                <label class="field"><span>Email</span><input type="email" name="email" required></label>
                <label class="field"><span>Subject</span><input type="text" name="subject"></label>
                <label class="field"><span>Message</span><textarea name="message" rows="6" required></textarea></label>
                <button class="btn primary" type="submit">Send Feedback</button>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
