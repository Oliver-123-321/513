<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/storage.php';

ensureSession();

$errors = [];
$success = '';

// Handle post creation — require login (content-only comments, no title required)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_post') {
    if (empty($_SESSION['user'])) {
        $errors[] = 'You must be logged in to post. Please log in first.';
    } else {
        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            $errors[] = 'Please provide content for your comment.';
        } else {
            $post = [
                'author' => $_SESSION['user']['username'] ?? $_SESSION['user']['email'] ?? 'User',
                'title' => '', // no title required for this simple comment board
                'content' => $content,
            ];
            if (savePost($post)) {
                $success = 'Comment published.';
            } else {
                $errors[] = 'Unable to save post.';
            }
        }
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (empty($_SESSION['user'])) {
        $errors[] = 'You must be logged in to comment. Please log in first.';
    } else {
        $postId = (int)($_POST['post_id'] ?? 0);
        $commentText = trim($_POST['comment'] ?? '');
        if ($postId <= 0 || $commentText === '') {
            $errors[] = 'Please provide a valid comment.';
        } else {
            $comment = [
                'post_id' => $postId,
                'author' => $_SESSION['user']['username'] ?? $_SESSION['user']['email'] ?? 'User',
                'content' => $commentText,
            ];
            if (saveComment($comment)) {
                $success = 'Comment added.';
            } else {
                $errors[] = 'Unable to save comment.';
            }
        }
    }
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    if (empty($_SESSION['user'])) {
        $errors[] = 'You must be logged in to delete posts.';
    } else {
        $postId = (int)($_POST['post_id'] ?? 0);
        $currentUser = $_SESSION['user']['username'] ?? $_SESSION['user']['email'] ?? '';
        $posts = listPosts();
        $post = null;
        foreach ($posts as $p) {
            if ((int)($p['id'] ?? 0) === $postId) {
                $post = $p;
                break;
            }
        }
        
        if ($post && !empty($_SESSION['user']['is_admin'])) {
            if (deletePost($postId)) {
                // 删除成功后重定向到论坛首页
                header('Location: forum.php');
                exit;
            } else {
                $errors[] = 'Unable to delete post.';
            }
        } else {
            $errors[] = 'You do not have permission to delete this post.';
        }
    }
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    if (empty($_SESSION['user'])) {
        $errors[] = 'You must be logged in to delete comments.';
    } else {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $currentUser = $_SESSION['user']['username'] ?? $_SESSION['user']['email'] ?? '';
        $comments = listComments();
        $comment = null;
        $postId = 0;
        foreach ($comments as $c) {
            if ((int)($c['id'] ?? 0) === $commentId) {
                $comment = $c;
                $postId = (int)($c['post_id'] ?? 0);
                break;
            }
        }
        
        if ($comment && !empty($_SESSION['user']['is_admin'])) {
            if (deleteComment($commentId)) {
                // 删除成功后重定向回当前话题页面
                if ($postId > 0) {
                    header('Location: forum.php?topic_id=' . $postId);
                } else {
                    header('Location: forum.php');
                }
                exit;
            } else {
                $errors[] = 'Unable to delete comment.';
            }
        } else {
            $errors[] = 'You do not have permission to delete this comment.';
        }
    }
}

$posts = array_reverse(listPosts()); // newest first
// If no top-level posts found, fallback to showing any comments so forum always displays content
if (empty($posts)) {
    $posts = array_reverse(listComments());
}
$pageTitle = 'Snack Shop · Community Forum';
$currentPage = 'forum';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .forum-wrap { max-width:900px; margin:2rem auto; padding: 0 1rem; }
        .post { background: var(--card); padding:1rem 1.25rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--border); }
        .post h3 { margin:0 0 0.25rem; }
        .post-meta { color:var(--muted); font-size:0.9rem; margin-bottom:0.5rem; }
        .new-post { margin-bottom:1.5rem; }
        
        .new-post form .field input[type="text"],
        .new-post form .field textarea {
            width: 100%;
            padding: 0.85rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            font-family: inherit;
            background: #fff;
            box-sizing: border-box;
        }
        
        .new-post form .field input[type="text"] {
            height: auto;
            min-height: 2.5rem;
        }
        
        .new-post form .field textarea {
            min-height: 8rem;
            resize: vertical;
        }
        
        .new-post form .field input[type="text"]:focus,
        .new-post form .field textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .comments-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .comments-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: var(--text);
        }
        
        .comments-list {
            margin-bottom: 1.5rem;
        }
        
        .comment {
            background: #f9f9f9;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        
        .comment-meta > div:first-child {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-delete-small {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .btn-delete-small:hover {
            background: #c82333;
        }
        
        .comment-meta strong {
            color: var(--text);
        }
        
        .comment-time {
            color: var(--muted);
            font-size: 0.8rem;
        }
        
        .comment-content {
            color: var(--text);
            line-height: 1.5;
            font-size: 0.95rem;
        }
        
        .comment-form {
            margin-top: 1rem;
        }
        
        .comment-form-inline textarea {
            font-size: 0.9rem;
        }
        
        .forum-table-container {
            overflow-x: auto;
        }
        
        .forum-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .forum-table thead {
            background: var(--accent);
        }
        
        .forum-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text);
            border-bottom: 2px solid var(--border);
        }
        
        .forum-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .forum-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .topic-cell {
            width: 60%;
        }
        
        .topic-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .topic-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .author-cell {
            width: 25%;
            color: var(--muted);
        }
        
        .replies-cell {
            width: 15%;
            text-align: center;
            color: var(--muted);
        }
        
        .topic-detail {
            padding: 2rem;
        }
        
        .post-content {
            color: var(--text);
            font-size: 1rem;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <div class="container forum-wrap">
        <h1>💬 Discussion Forum</h1>
        <?php if (empty($_SESSION['user'])): ?>
            <p class="eyebrow" style="margin-bottom: 1.5rem;">You must be logged in to create a new topic.</p>
        <?php else: ?>
            <p class="eyebrow" style="margin-bottom: 1.5rem;">Share feedback, suggestions, and ideas with the community.</p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div style="color:#c23; font-weight:600; margin-bottom:0.75rem;">
                <?= implode('<br>', array_map('sanitize', $errors)); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="color:green; font-weight:600; margin-bottom:0.75rem;"><?= sanitize($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['user'])): ?>
            <div class="new-post filter-card">
                <h2>New Comment</h2>
                <form method="post" action="forum.php">
                    <input type="hidden" name="action" value="new_post">
                    <label class="field"><textarea name="content" rows="4" placeholder="Write your comment..." required></textarea></label>
                    <button class="btn primary" type="submit">Publish</button>
                </form>
            </div>
        <?php endif; ?>

        <section class="posts-list">
            <?php if (empty($posts)): ?>
                <p>No comments yet.</p>
            <?php else: ?>
                <div class="filter-card">
                    <div class="comments-list">
                        <?php foreach ($posts as $post): ?>
                            <?php $postId = (int)($post['id'] ?? 0); ?>
                            <div class="comment" style="margin-bottom:0.75rem;">
                                <div class="comment-meta">
                                    <div>
                                        <strong><?= sanitize($post['author'] ?? 'User'); ?></strong>
                                        <span class="comment-time"><?= date('Y-m-d H:i', strtotime($post['created_at'] ?? '')); ?></span>
                                    </div>
                                    <?php 
                                    $currentUser = $_SESSION['user']['username'] ?? $_SESSION['user']['email'] ?? '';
                                    $isAdmin = !empty($_SESSION['user']['is_admin'] ?? false);
                                    // Only show delete control to admins
                                    if ($isAdmin): ?>
                                        <form method="post" action="forum.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= $postId; ?>">
                                            <button type="submit" class="btn-delete-small" title="Delete post">×</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-content"><?= nl2br(sanitize($post['content'] ?? '')); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
