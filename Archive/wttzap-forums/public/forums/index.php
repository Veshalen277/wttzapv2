<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$activeNav = 'forums';
$businessId = Auth::activeBusinessId();

$categories = Database::all(
    'SELECT c.*,
        (SELECT COUNT(*) FROM forum_topics t WHERE t.category_id = c.id) AS topic_count,
        (SELECT COUNT(*) FROM forum_replies r JOIN forum_topics t2 ON t2.id = r.topic_id WHERE t2.category_id = c.id) AS reply_count,
        (SELECT t3.title FROM forum_topics t3 WHERE t3.category_id = c.id
            ORDER BY COALESCE(t3.last_reply_at, t3.created_at) DESC LIMIT 1) AS latest_title,
        (SELECT COALESCE(t3.last_reply_at, t3.created_at) FROM forum_topics t3 WHERE t3.category_id = c.id
            ORDER BY COALESCE(t3.last_reply_at, t3.created_at) DESC LIMIT 1) AS latest_at
     FROM forum_categories c
     WHERE c.business_id = ?
     ORDER BY c.position, c.name',
    [$businessId]
);

$q = trim($_GET['q'] ?? '');
$searchResults = [];
if ($q !== '') {
    $searchResults = Database::all(
        'SELECT t.id, t.title, t.created_at, u.full_name, cat.name AS category_name
         FROM forum_topics t
         JOIN users u ON u.id = t.user_id
         JOIN forum_categories cat ON cat.id = t.category_id
         WHERE t.business_id = ? AND t.title LIKE ?
         ORDER BY t.created_at DESC LIMIT 20',
        [$businessId, '%' . $q . '%']
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums · WttZap</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/forums.css">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../../app/partials/nav.php'; ?>

    <main class="main">
        <div class="topbar">
            <h1>Forums</h1>
            <div style="display:flex; align-items:center; gap:14px;">
                <?php if (Auth::roleAtLeast('admin')): ?>
                    <a class="btn btn-outline" href="category-form.php">+ New category</a>
                <?php endif; ?>
                <?php require __DIR__ . '/../../app/partials/user_menu.php'; ?>
            </div>
        </div>

        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <form method="get" class="forum-search">
            <input type="text" name="q" placeholder="Search topics…" value="<?= e($q) ?>">
            <button class="btn btn-outline btn-sm" type="submit">Search</button>
        </form>

        <?php if ($q !== ''): ?>
            <div class="card">
                <h2>Results for "<?= e($q) ?>"</h2>
                <?php if (!$searchResults): ?>
                    <div class="empty-state">No topics match that search.</div>
                <?php else: ?>
                    <?php foreach ($searchResults as $r): ?>
                        <div class="forum-search-row">
                            <a href="topic.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a>
                            <span class="forum-meta">in <?= e($r['category_name']) ?> · by <?= e($r['full_name']) ?> · <?= time_ago($r['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$categories): ?>
            <div class="card"><div class="empty-state">
                No forum categories yet.
                <?php if (Auth::roleAtLeast('admin')): ?><br><a href="category-form.php">Create the first one →</a><?php endif; ?>
            </div></div>
        <?php else: ?>
            <div class="forum-category-list">
                <?php foreach ($categories as $c): ?>
                    <a class="forum-category-card" href="category.php?id=<?= (int) $c['id'] ?>">
                        <div class="forum-category-main">
                            <h3><?= e($c['name']) ?>
                                <?php if ($c['min_role_to_post'] !== 'employee'): ?>
                                    <span class="badge badge-pending"><?= e(ucfirst($c['min_role_to_post'])) ?>-only posting</span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($c['description']): ?><p class="forum-category-desc"><?= e($c['description']) ?></p><?php endif; ?>
                        </div>
                        <div class="forum-category-stats">
                            <div><span class="num"><?= (int) $c['topic_count'] ?></span> topics</div>
                            <div><span class="num"><?= (int) $c['reply_count'] ?></span> replies</div>
                        </div>
                        <div class="forum-category-latest">
                            <?php if ($c['latest_title']): ?>
                                <div class="forum-meta"><?= e($c['latest_title']) ?></div>
                                <div class="forum-meta num"><?= time_ago($c['latest_at']) ?></div>
                            <?php else: ?>
                                <div class="forum-meta">No topics yet</div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
