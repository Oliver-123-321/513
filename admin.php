<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/admin.php';
require __DIR__ . '/src/storage.php';

requireAdmin();

// Helpers to safely read optional JSON files (posts/comments)
function read_json_file_if_exists(string $path, string $key): array {
    if (!file_exists($path)) return ['data' => []];
    $raw = file_get_contents($path);
    $arr = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) return ['data' => []];
    return ['data' => $arr[$key] ?? []];
}

$errors = [];
$success = '';
$products = loadProductsData();
$maxId = empty($products) ? 0 : max(array_column($products, 'id'));
$action = $_POST['action'] ?? '';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add_product') {
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $image = trim($_POST['image'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $badge = trim($_POST['badge'] ?? '');
            $rating = max(0, min(5, (int) ($_POST['rating'] ?? 0)));
            $bestSeller = isset($_POST['best_seller']);

            if ($name === '' || $category === '' || $price <= 0) {
                throw new Exception('Name, Category, Price are required and price must be > 0.');
            }

            $products[] = [
                'id' => $maxId + 1,
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'stock' => $stock,
                'image' => $image,
                'description' => $description,
                'badge' => $badge,
                'rating' => $rating,
                'best_seller' => $bestSeller,
            ];
            saveProductsData($products);
            $success = 'Product added.';
        } elseif ($action === 'update_product') {
            $id = (int) ($_POST['id'] ?? 0);
            foreach ($products as &$p) {
                if ((int) $p['id'] === $id) {
                    $p['name'] = trim($_POST['name'] ?? $p['name']);
                    $p['category'] = trim($_POST['category'] ?? $p['category']);
                    $p['price'] = (float) ($_POST['price'] ?? $p['price']);
                    $p['stock'] = (int) ($_POST['stock'] ?? $p['stock']);
                    $p['image'] = trim($_POST['image'] ?? $p['image']);
                    $p['description'] = trim($_POST['description'] ?? $p['description']);
                    $p['badge'] = trim($_POST['badge'] ?? ($p['badge'] ?? ''));
                    $p['rating'] = max(0, min(5, (int) ($_POST['rating'] ?? ($p['rating'] ?? 0))));
                    $p['best_seller'] = isset($_POST['best_seller']);
                    break;
                }
            }
            unset($p);
            saveProductsData($products);
            $success = 'Product updated.';
        } elseif ($action === 'delete_product') {
            $id = (int) ($_POST['id'] ?? 0);
            $products = array_values(array_filter($products, fn($p) => (int) $p['id'] !== $id));
            saveProductsData($products);
            $success = 'Product deleted.';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    // Reload products after modifications
    $products = loadProductsData();
    $maxId = empty($products) ? 0 : max(array_column($products, 'id'));
}

// Admin actions: delete forum posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_post_admin') {
    $postId = (int) ($_POST['post_id'] ?? 0);
    try {
        if ($postId > 0) {
            if (deletePost($postId)) {
                $success = 'Post deleted.';
                // Refresh posts after deletion
                // (will be reloaded below)
            } else {
                $errors[] = 'Unable to delete post.';
            }
        } else {
            $errors[] = 'Invalid post id.';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
    // reload products (no change) and continue
    $products = loadProductsData();
    $maxId = empty($products) ? 0 : max(array_column($products, 'id'));
}

// Admin actions: delete forum comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_comment_admin') {
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    try {
        if ($commentId > 0) {
            if (deleteComment($commentId)) {
                $success = 'Comment deleted.';
            } else {
                $errors[] = 'Unable to delete comment.';
            }
        } else {
            $errors[] = 'Invalid comment id.';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
    // reload products (no change)
    $products = loadProductsData();
    $maxId = empty($products) ? 0 : max(array_column($products, 'id'));
}

$postsList = array_reverse(listPosts());
$commentsList = array_reverse(listComments());
$feedbacks = listFeedback();
$orders = array_reverse(listOrders()); // show newest orders first

// Normalize orders returned from DB or JSON so admin view can rely on same fields:
foreach ($orders as &$o) {
    // Ensure items is an array (DB may store items_json)
    if (isset($o['items_json']) && is_string($o['items_json']) && $o['items_json'] !== '') {
        $decoded = json_decode($o['items_json'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $o['items'] = $decoded;
        } else {
            $o['items'] = $o['items'] ?? [];
        }
    } elseif (isset($o['items']) && is_string($o['items'])) {
        $decoded = json_decode($o['items'], true);
        $o['items'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    } else {
        $o['items'] = $o['items'] ?? [];
    }

    // Ensure total uses total_amount if present
    if (isset($o['total_amount'])) {
        $o['total'] = (float) $o['total_amount'];
    } else {
        $o['total'] = isset($o['total']) ? (float) $o['total'] : 0.0;
    }

    // Ensure items_count is available
    if (!isset($o['items_count'])) {
        $o['items_count'] = is_array($o['items']) ? count($o['items']) : 0;
    }
}
unset($o);

// If there are no top-level posts, also try loading comments (some deployments store directly as comments)
if (empty($postsList)) {
    $maybeComments = listComments();
    if (!empty($maybeComments)) {
        $postsList = array_reverse($maybeComments);
    }
}

$pageTitle = 'Snack Shop · Admin Dashboard';
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
            max-width: 1280px;
            margin: 2rem auto;
            padding: 0 1rem 3rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 1.5rem;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.25rem;
        }
        .card h3 {
            margin: 0 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 720px;
            overflow: auto;
            padding-right: 0.25rem;
        }
        .product-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 1rem;
            background: #fafafa;
        }
        .card .field {
            display: block;
        }
        .card .field span {
            display: block;
            margin-bottom: 0.35rem;
        }
        .product-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #eee;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .card .field textarea {
            height: 56px;
            min-height: 56px;
            max-height: 56px;
            padding: 0.75rem;
            line-height: 1.4;
            resize: vertical;
            width: 100%;
            box-sizing: border-box;
        }
        .section-title {
            margin: 2rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 0.95rem;
        }
        .status-pill {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .muted {
            color: var(--muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="page-admin">
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="admin-panel">
    <div class="admin-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="eyebrow">Products · Orders · Feedback · Forum</p>
        </div>
        <a href="logout.php?admin=1" class="btn outline">Logout</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="card" style="border-color:#f8d7da; background:#fff5f5; color:#842029; margin-bottom:1rem;">
            <?= implode('<br>', array_map('sanitize', $errors)); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="card" style="border-color:#d1e7dd; background:#f0fdf4; color:#0f5132; margin-bottom:1rem;">
            <?= sanitize($success); ?>
        </div>
    <?php endif; ?>

    <div class="admin-grid">
        <!-- Add product -->
        <div class="card">
            <h3>➕ Add New Product</h3>
            <form method="post" class="admin-form">
                <input type="hidden" name="action" value="add_product">
                <label class="field"><span>Name *</span><input type="text" name="name" required></label>
                <label class="field"><span>Category *</span><input type="text" name="category" required></label>
                <label class="field"><span>Price *</span><input type="number" step="0.01" name="price" required></label>
                <label class="field"><span>Stock</span><input type="number" name="stock" value="0"></label>
                <label class="field"><span>Image URL</span><input type="text" name="image" placeholder="1.jpg or https://..."></label>
                <label class="field"><span>Badge</span><input type="text" name="badge" placeholder="New / Hot / ..."></label>
                <label class="field"><span>Rating (0-5)</span><input type="number" name="rating" min="0" max="5" value="5"></label>
                <label class="field"><span>Description</span><textarea name="description" rows="3"></textarea></label>
                <label style="display:flex;align-items:center;gap:0.5rem; margin:0.5rem 0 1rem;">
                    <input type="checkbox" name="best_seller"> <span>Best Seller</span>
                </label>
                <button class="btn primary full-width" type="submit">Add Product</button>
            </form>
        </div>

        <!-- Product list -->
        <div class="card">
            <h3>📦 Product List (<?= count($products); ?>)</h3>
            <div class="product-list">
                <?php if (empty($products)): ?>
                    <p class="muted">No products found.</p>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <form method="post" class="product-item">
                            <input type="hidden" name="action" value="update_product">
                            <input type="hidden" name="id" value="<?= (int) $p['id']; ?>">
                            <div>
                                <img src="<?= sanitize($p['image'] ?? ''); ?>" alt="<?= sanitize($p['name']); ?>">
                            </div>
                            <div>
                                <label class="field" style="margin:0 0 0.35rem;">
                                    <span style="font-size:0.85rem; color:var(--muted);">Name / ID: <?= (int) $p['id']; ?></span>
                                    <input type="text" name="name" value="<?= sanitize($p['name']); ?>" required>
                                </label>
                                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.5rem;">
                                    <label class="field"><span>Category</span><input type="text" name="category" value="<?= sanitize($p['category']); ?>"></label>
                                    <label class="field"><span>Price</span><input type="number" step="0.01" name="price" value="<?= (float) $p['price']; ?>"></label>
                                    <label class="field"><span>Stock</span><input type="number" name="stock" value="<?= (int) ($p['stock'] ?? 0); ?>"></label>
                                </div>
                                <label class="field"><span>Image URL</span><input type="text" name="image" value="<?= sanitize($p['image']); ?>"></label>
                                <label class="field"><span>Badge</span><input type="text" name="badge" value="<?= sanitize($p['badge'] ?? ''); ?>"></label>
                                <label class="field"><span>Rating (0-5)</span><input type="number" name="rating" min="0" max="5" value="<?= (int) ($p['rating'] ?? 0); ?>"></label>
                                <label class="field"><span>Description</span><textarea name="description" rows="2"><?= sanitize($p['description'] ?? ''); ?></textarea></label>
                                <label style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="checkbox" name="best_seller" <?= !empty($p['best_seller']) ? 'checked' : ''; ?>> <span>Best Seller</span>
                                </label>
                                <div class="product-actions">
                                    <button class="btn primary" type="submit">Update</button>
                                    <button class="btn btn-danger" name="action" value="delete_product" type="submit" onclick="return confirm('Delete this product?');">Delete</button>
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h3 class="section-title">🧾 Orders Management</h3>
    <div class="card">
        <table class="table" id="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="muted">No orders data available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php $orderId = (int) ($o['id'] ?? 0); ?>
                        <tr class="order-row">
                            <td>#<?= $orderId; ?></td>
                            <td><?= sanitize($o['customer_email'] ?? ''); ?></td>
                            <td><?= sanitize(substr($o['created_at'] ?? '', 0, 10)); ?></td>
                            <td><?= count($o['items'] ?? []); ?></td>
                            <td><?= sanitize('$' . number_format((float) ($o['total'] ?? 0), 2)); ?></td>
                            <td><span class="status-pill"><?= sanitize(ucfirst($o['status'] ?? 'unknown')); ?></span></td>
                            <td>
                                <?php if (!empty($o['items'])): ?>
                                    <button class="btn" type="button" onclick="toggleOrderDetails(<?= $orderId; ?>)">Toggle</button>
                                <?php else: ?>
                                    <button class="btn" type="button" disabled>View</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="order-details" id="order-details-<?= $orderId; ?>" style="display:none;">
                            <td colspan="7">
                                <strong>Items:</strong>
                                <table style="width:100%; border-collapse:collapse; margin-top:0.5rem;">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left; padding:0.25rem;">Product</th>
                                            <th style="text-align:left; padding:0.25rem;">Qty</th>
                                            <th style="text-align:left; padding:0.25rem;">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($o['items'] ?? [] as $item): ?>
                                            <tr>
                                                <td style="padding:0.25rem;"><?= sanitize($item['product']['name'] ?? ($item['name'] ?? '')); ?></td>
                                                <td style="padding:0.25rem;"><?= (int) ($item['quantity'] ?? ($item['qty'] ?? 0)); ?></td>
                                                <td style="padding:0.25rem;"><?= sanitize('$' . number_format((float) ($item['product']['price'] ?? ($item['price'] ?? 0)), 2)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div style="margin-top:0.5rem;">
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this order?');">
                                        <input type="hidden" name="action" value="delete_order_admin">
                                        <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                                        <button class="btn btn-danger" type="submit">Delete Order</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        function toggleOrderDetails(id) {
            var el = document.getElementById('order-details-' + id);
            if (!el) return;
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>

    <h3 class="section-title">✉️ Customer Feedback</h3>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>From</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="5" class="muted">No feedback data available.</td></tr>
                <?php else: ?>
                    <?php foreach (array_reverse($feedbacks) as $f): ?>
                        <tr>
                            <td><?= (int) ($f['id'] ?? 0); ?></td>
                            <td><?= sanitize($f['email'] ?? ($f['name'] ?? '')); ?></td>
                            <td><?= nl2br(sanitize(mb_strlen($f['message'] ?? '') > 120 ? mb_substr($f['message'] ?? '', 0, 120) . '...' : ($f['message'] ?? ''))); ?></td>
                            <td><?= sanitize(substr($f['created_at'] ?? '', 0, 10)); ?></td>
                            <td>
                                <button class="btn" type="button" onclick="alert('<?= sanitize(addslashes($f['message'] ?? '')); ?>')">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h3 class="section-title">💬 Forum Comments</h3>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Content</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($commentsList)): ?>
                    <tr><td colspan="4" class="muted">No comments available.</td></tr>
                <?php else: ?>
                    <?php foreach ($commentsList as $post): ?>
                        <tr>
                            <td>
                                <?php $full = $post['content'] ?? ''; ?>
                                <?= nl2br(sanitize(mb_strlen($full) > 140 ? mb_substr($full, 0, 140) . '...' : $full)); ?>
                            </td>
                            <td><?= sanitize($post['author'] ?? ''); ?></td>
                            <td><?= sanitize(date('Y-m-d H:i', strtotime($post['created_at'] ?? ''))); ?></td>
                            <td>
                                <button class="btn" type="button" onclick="alert('<?= sanitize(addslashes($post['content'] ?? '')); ?>')">View</button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
                                    <input type="hidden" name="action" value="delete_comment_admin">
                                    <input type="hidden" name="comment_id" value="<?= (int)($post['id'] ?? 0); ?>">
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

