<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$activeNav = 'forums';
$businessId = Auth::activeBusinessId();

$categoryId = (int) ($_GET['id'] ?? 0);
$category = Database::one('SELECT * FROM forum_categories WHERE id = ? AND business_id = ?', [$categoryId, $businessId]);
if (!$category) { http_response_code(404); die('Category not found.'); }

$canPost = Auth::roleAtLeast($category['min_role_to_post']);

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalTopics = Database::one('SELECT COUNT(*) c FROM forum_topics WHERE category_id = ?', [$categoryId])['c'];

$topics = Database::all(
    "SELECT t.*, u.full_name AS author_name, lru.full_name AS last_reply_name
     FROM forum_topics t
     JOIN users u ON u.id = t.user_id
     LEFT JOIN users lru ON lru.id = t.last_reply_by
     WHERE t.category_id = ?
     ORDER BY t.is_pinned DESC, COALESCE(t.last_reply_at, t.created_at) DESC
     LIMIT $perPage OFFSET $offset",
    [$categoryId]
);
$totalPages = max(1, (int) ceil($totalTopics / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($category['name']) ?> · Forums · WttZap</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/forums.css">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../../app/partials/nav.php'; ?>

    <main class="main">
        <div class="topbar">
            <div>
                <div class="forum-breadcrumb"><a href="index.php">Forums</a> / <?= e($category['name']) ?></div>
                <h1><?= e($category['name']) ?></h1>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <?php if ($canPost): ?>
                    <a class="btn btn-accent" href="new-topic.php?category=<?= $categoryId ?>">+ New topic</a>
                <?php endif; ?>
                <?php if (Auth::roleAtLeast('admin')): ?>
                    <a class="btn btn-outline" href="category-form.php?id=<?= $categoryId ?>">Edit category</a>
                <?php endif; ?>
                <?php require __DIR__ . '/../../app/partials/user_menu.php'; ?>
            </div>
        </div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <?php if (!$canPost): ?>
            <div class="alert alert-error">Only <?= e($category['min_role_to_post']) ?>s and above can start new topics here — you can still read and reply.</div>
        <?php endif; ?>

        <div class="card" style="padding:0;">
            <?php if (!$topics): ?>
                <div class="empty-state">No topics here yet<?= $canPost ? ' — start the first one.' : '.' ?></div>
            <?php else: ?>
                <table>
                    <thead><tr><th>Topic</th><th>Replies</th><th>Views</th><th>Last activity</th></tr></thead>
                    <tbody>
                        <?php foreach ($topics as $t): ?>
                            <tr>
                                <td>
                                    <?php if ($t['is_pinned']): ?><span class="badge badge-pending">Pinned</span><?php endif; ?>
                                    <?php if ($t['is_locked']): ?><span class="badge badge-inactive">Locked</span><?php endif; ?>
                                    <a href="topic.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a>
                                    <div class="forum-meta">by <?= e($t['author_name']) ?> · <?= time_ago($t['created_at']) ?></div>
                                </td>
                                <td class="num"><?= (int) $t['reply_count'] ?></td>
                                <td class="num"><?= (int) $t['views'] ?></td>
                                <td class="forum-meta">
                                    <?php if ($t['last_reply_at']): ?>
                                        <?= time_ago($t['last_reply_at']) ?> by <?= e($t['last_reply_name']) ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="forum-pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="btn btn-sm <?= $p === $page ? 'btn-accent' : 'btn-outline' ?>"
                       href="category.php?id=<?= $categoryId ?>&page=<?= $p ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
