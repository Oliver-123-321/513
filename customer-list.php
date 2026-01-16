<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/admin.php';
require __DIR__ . '/src/db.php';

requireAdmin();

$pageTitle = 'Snack Shop · Customer List';
$currentPage = 'admin';

$users = [];
$errorMsg = '';

try {
    $conn = getDbConnection();
    // Try several column combinations commonly used in subscriber tables
    $queries = [
        "SELECT id, email, name FROM wpfw_fc_subscribers ORDER BY id DESC",
        "SELECT id, email, CONCAT_WS(' ', first_name, last_name) AS name FROM wpfw_fc_subscribers ORDER BY id DESC",
        "SELECT id, email, '' AS name FROM wpfw_fc_subscribers ORDER BY id DESC",
    ];
    foreach ($queries as $sql) {
        try {
            $stmt = $conn->query($sql);
            if ($stmt) {
                $rows = $stmt->fetchAll();
                $users = $rows ?: [];
                break;
            }
        } catch (Exception $inner) {
            // try next query
            continue;
        }
    }
    if (empty($users) && !empty($inner)) {
        $errorMsg = 'Failed to load customers: ' . $inner->getMessage();
    }
} catch (Exception $e) {
    $errorMsg = 'Failed to load customers: ' . $e->getMessage();
}
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
        .admin-panel {
            max-width: 960px;
            margin: 2rem auto;
            padding: 0 1rem 2rem;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.25rem;
        }
        table.user-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.user-table th, table.user-table td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 0.95rem;
        }
        table.user-table th {
            color: var(--muted);
            font-weight: 700;
        }
    </style>
</head>
<body class="page-admin">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="admin-panel">
    <div class="card">
        <h1 style="margin:0 0 1rem;">Customer List</h1>
        <p class="eyebrow" style="margin:0 0 1.2rem;"><?= count($users); ?> customers</p>

        <?php if (!empty($errorMsg)): ?>
            <p style="color:#c23;"><?= sanitize($errorMsg); ?></p>
        <?php elseif (empty($users)): ?>
            <p>No customers found.</p>
        <?php else: ?>
            <table class="user-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $idx => $user): ?>
                    <tr>
                        <td><?= $idx + 1; ?></td>
                        <td><?= sanitize($user['name'] ?? ''); ?></td>
                        <td><?= sanitize($user['email'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

