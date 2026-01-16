<?php
declare(strict_types=1);

// Ensure DB connection helper is available when storage functions are used.
// If db.php exists in the same directory, require it so getDbConnection() is defined.
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}

// Lightweight storage layer: prefers database helpers if present, otherwise uses JSON files in data/

function storage_dir(): string
{
    return __DIR__ . '/../data';
}

function load_json_file(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function save_json_file(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return (bool) @file_put_contents($path, $json);
}

/** Feedback storage **/
function saveFeedback(array $item): bool
{
    // If DB adapter exists, you can extend to use DB. For now fallback to JSON file.
    $path = storage_dir() . '/feedback.json';
    $data = load_json_file($path);
    if (!isset($data['feedback']) || !is_array($data['feedback'])) {
        $data['feedback'] = [];
    }
    $item['id'] = (int) (end($data['feedback'])['id'] ?? 0) + 1;
    $item['created_at'] = date('c');
    $data['feedback'][] = $item;
    return save_json_file($path, $data);
}

function listFeedback(): array
{
    $path = storage_dir() . '/feedback.json';
    $data = load_json_file($path);
    return $data['feedback'] ?? [];
}

/** Forum posts storage **/
function listPosts(): array
{
    $path = storage_dir() . '/posts.json';
    // Try DB first (read top-level posts from Forum where post_id = 0)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('SELECT id, author, title, content, created_at FROM Forum WHERE post_id = 0 ORDER BY created_at DESC');
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        }
    } catch (Exception $e) {
        error_log('Post DB list from Forum failed: ' . $e->getMessage());
    }

    $data = load_json_file($path);
    return $data['posts'] ?? [];
}

function savePost(array $post): bool
{
    $path = storage_dir() . '/posts.json';
    // Try to persist to DB first (store top-level posts into Forum table with post_id = 0)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('INSERT INTO Forum (post_id, author, content, created_at) VALUES (:post_id, :author, :content, :created_at)');
            $now = date('c');
            $stmt->execute([
                ':post_id' => 0,
                ':author' => $post['author'] ?? '',
                ':content' => $post['content'] ?? ($post['title'] ?? ''),
                ':created_at' => $now,
            ]);
            return true;
        }
    } catch (Exception $e) {
        error_log('Post DB save to Forum failed: ' . $e->getMessage());
    }

    // Fallback to JSON file
    $data = load_json_file($path);
    if (!isset($data['posts']) || !is_array($data['posts'])) {
        $data['posts'] = [];
    }
    $post['id'] = (int) (end($data['posts'])['id'] ?? 0) + 1;
    $post['created_at'] = date('c');
    $data['posts'][] = $post;
    return save_json_file($path, $data);
}

/** Forum comments storage **/
function saveComment(array $comment): bool
{
    // Try to persist to DB if available (store into Forum table)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('INSERT INTO Forum (post_id, author, content, created_at) VALUES (:post_id, :author, :content, :created_at)');
            $now = date('c');
            $stmt->execute([
                ':post_id' => (int)($comment['post_id'] ?? 0),
                ':author' => $comment['author'] ?? '',
                ':content' => $comment['content'] ?? '',
                ':created_at' => $now,
            ]);
            return true;
        }
    } catch (Exception $e) {
        // fallback to file storage on any DB error
        error_log('Comment DB save to Forum failed: ' . $e->getMessage());
    }

    $path = storage_dir() . '/comments.json';
    $data = load_json_file($path);
    if (!isset($data['comments']) || !is_array($data['comments'])) {
        $data['comments'] = [];
    }
    $comment['id'] = (int) (end($data['comments'])['id'] ?? 0) + 1;
    $comment['created_at'] = date('c');
    $data['comments'][] = $comment;
    return save_json_file($path, $data);
}

function listComments(?int $postId = null): array
{
    $path = storage_dir() . '/comments.json';
    // Try DB first (read from Recruit table and map fields)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            if ($postId !== null) {
                $stmt = $conn->prepare('SELECT id, post_id, author, content, created_at FROM Forum WHERE post_id = :post_id ORDER BY created_at ASC');
                $stmt->execute([':post_id' => $postId]);
            } else {
                $stmt = $conn->query('SELECT id, post_id, author, content, created_at FROM Forum ORDER BY created_at ASC');
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($rows) && count($rows) > 0) {
                return $rows;
            }
            // If Forum table has no rows, try the legacy Comments table as a fallback
            $alt = listCommentsAlternate($postId);
            if (!empty($alt)) {
                return $alt;
            }
            return [];
        }
    } catch (Exception $e) {
        error_log('Comment DB list from Forum failed: ' . $e->getMessage());
    }

    // Fallback to JSON file
    $data = load_json_file($path);
    $comments = $data['comments'] ?? [];
    
    if ($postId !== null) {
        $comments = array_filter($comments, function($comment) use ($postId) {
            return isset($comment['post_id']) && (int)$comment['post_id'] === $postId;
        });
    }
    
    return array_values($comments);
}

/**
 * Try to list comments from alternate DB table `Comments` if `Forum` is empty.
 * This helps support older deployments that used `Posts`/`Comments` tables.
 */
function listCommentsAlternate(?int $postId = null): array
{
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            if ($postId !== null) {
                $stmt = $conn->prepare('SELECT id, post_id, author, content, created_at FROM Comments WHERE post_id = :post_id ORDER BY created_at ASC');
                $stmt->execute([':post_id' => $postId]);
            } else {
                $stmt = $conn->query('SELECT id, post_id, author, content, created_at FROM Comments ORDER BY created_at ASC');
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        }
    } catch (Exception $e) {
        error_log('Comment DB list from Comments table failed: ' . $e->getMessage());
    }
    return [];
}

function deletePost(int $postId): bool
{
    // Try DB first: delete the top-level post stored in Forum (post_id = 0 entries)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('DELETE FROM Forum WHERE id = :id AND post_id = 0');
            $stmt->execute([':id' => $postId]);
            $rows = $stmt->rowCount();
            // Also delete comments (rows with post_id = this id)
            $deletedChild = deleteCommentsByPostId($postId);
            if ($rows > 0 || $deletedChild) {
                return true;
            }
            // if nothing deleted in DB, fall through to JSON fallback
        }
    } catch (Exception $e) {
        error_log('Post DB delete from Forum failed: ' . $e->getMessage());
    }

    // Try deleting from legacy Posts table
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('DELETE FROM Posts WHERE id = :id');
            $stmt->execute([':id' => $postId]);
            if ($stmt->rowCount() > 0) {
                // Also delete associated comments
                deleteCommentsByPostId($postId);
                return true;
            }
        }
    } catch (Exception $e) {
        error_log('Post DB delete from Posts table failed: ' . $e->getMessage());
    }

    $path = storage_dir() . '/posts.json';
    $data = load_json_file($path);
    if (!isset($data['posts']) || !is_array($data['posts'])) {
        return false;
    }
    
    $data['posts'] = array_filter($data['posts'], function($post) use ($postId) {
        return (int)($post['id'] ?? 0) !== $postId;
    });
    
    // Also delete all comments for this post
    deleteCommentsByPostId($postId);
    
    return save_json_file($path, $data);
}

function deleteComment(int $commentId): bool
{
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('DELETE FROM Forum WHERE id = :id');
            $stmt->execute([':id' => $commentId]);
            if ($stmt->rowCount() > 0) {
                return true;
            }
            // if no rows affected, fall through to JSON fallback
        }
    } catch (Exception $e) {
        error_log('Comment DB delete from Forum failed: ' . $e->getMessage());
    }
    // Try alternate Comments table
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('DELETE FROM Comments WHERE id = :id');
            $stmt->execute([':id' => $commentId]);
            if ($stmt->rowCount() > 0) {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log('Comment DB delete from Comments table failed: ' . $e->getMessage());
    }

    $path = storage_dir() . '/comments.json';
    $data = load_json_file($path);
    if (!isset($data['comments']) || !is_array($data['comments'])) {
        return false;
    }
    
    $data['comments'] = array_filter($data['comments'], function($comment) use ($commentId) {
        return (int)($comment['id'] ?? 0) !== $commentId;
    });
    
    return save_json_file($path, $data);
}

function deleteCommentsByPostId(int $postId): bool
{
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('DELETE FROM Forum WHERE post_id = :post_id');
            $stmt->execute([':post_id' => $postId]);
            if ($stmt->rowCount() > 0) {
                return true;
            }
            // Try deleting from alternate Comments table
            $stmt2 = $conn->prepare('DELETE FROM Comments WHERE post_id = :post_id');
            $stmt2->execute([':post_id' => $postId]);
            return $stmt2->rowCount() > 0;
        }
    } catch (Exception $e) {
        error_log('Comment DB delete-by-post from Forum failed: ' . $e->getMessage());
    }

    $path = storage_dir() . '/comments.json';
    $data = load_json_file($path);
    if (!isset($data['comments']) || !is_array($data['comments'])) {
        return false;
    }
    
    $data['comments'] = array_filter($data['comments'], function($comment) use ($postId) {
        return (int)($comment['post_id'] ?? 0) !== $postId;
    });
    
    return save_json_file($path, $data);
}

/** Orders storage **/
function saveOrder(array $order): bool
{
    // Try DB first (write to Orders table)
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->prepare('INSERT INTO Orders (order_number, customer_email, items_count, total_amount, items_json, status, created_at) VALUES (:order_number, :customer_email, :items_count, :total_amount, :items_json, :status, :created_at)');
            $now = date('c');
            $stmt->execute([
                ':order_number' => $order['order_number'] ?? uniqid('ord_'),
                ':customer_email' => $order['customer_email'] ?? '',
                ':items_count' => (int)($order['items_count'] ?? 0),
                ':total_amount' => number_format((float)($order['total_amount'] ?? 0.0), 2, '.', ''),
                ':items_json' => is_string($order['items_json'] ?? null) ? $order['items_json'] : json_encode($order['items'] ?? []),
                ':status' => $order['status'] ?? 'Pending',
                ':created_at' => $now,
            ]);
            return true;
        }
    } catch (Exception $e) {
        error_log('Order DB save failed: ' . $e->getMessage());
    }

    // Fallback to JSON file storage
    $path = storage_dir() . '/orders.json';
    $data = load_json_file($path);
    if (!isset($data['orders']) || !is_array($data['orders'])) {
        $data['orders'] = [];
    }
    $order['id'] = (int) (end($data['orders'])['id'] ?? 0) + 1;
    $order['created_at'] = date('c');
    $data['orders'][] = $order;
    return save_json_file($path, $data);
}

function listOrders(): array
{
    // Try DB first
    try {
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $stmt = $conn->query('SELECT id, order_number, customer_email, items_count, total_amount, items_json, status, created_at FROM Orders ORDER BY created_at DESC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        }
    } catch (Exception $e) {
        error_log('Order DB list failed: ' . $e->getMessage());
    }

    // Fallback to JSON file storage
    $path = storage_dir() . '/orders.json';
    $data = load_json_file($path);
    return $data['orders'] ?? [];
}