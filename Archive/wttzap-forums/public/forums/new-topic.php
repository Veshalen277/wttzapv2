<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$activeNav = 'forums';
$businessId = Auth::activeBusinessId();

$categoryId = (int) ($_GET['category'] ?? $_POST['category_id'] ?? 0);
$category = Database::one('SELECT * FROM forum_categories WHERE id = ? AND business_id = ?', [$categoryId, $businessId]);
if (!$category) { http_response_code(404); die('Category not found.'); }

if (!Auth::roleAtLeast($category['min_role_to_post'])) {
    http_response_code(403);
    die('Only ' . e($category['min_role_to_post']) . 's and above can start topics in this category.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        $error = 'Give it a title and a first post.';
    } else {
        Database::run(
            'INSERT INTO forum_topics (category_id, business_id, user_id, title, body) VALUES (?,?,?,?,?)',
            [$categoryId, $businessId, $user['id'], $title, $body]
        );
        $topicId = Database::lastInsertId();
        redirect('forums/topic.php?id=' . $topicId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New topic · <?= e($category['name']) ?> · WttZap</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/forums.css">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../../app/partials/nav.php'; ?>

    <main class="main">
        <div class="topbar">
            <div>
                <div class="forum-breadcrumb"><a href="index.php">Forums</a> / <a href="category.php?id=<?= $categoryId ?>"><?= e($category['name']) ?></a> / New topic</div>
                <h1>New topic</h1>
            </div>
            <?php require __DIR__ . '/../../app/partials/user_menu.php'; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="category_id" value="<?= $categoryId ?>">

                <label for="title">Title</label>
                <input id="title" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>">

                <label for="body">First post</label>
                <textarea id="body" name="body" required rows="8"><?= e($_POST['body'] ?? '') ?></textarea>

                <button class="btn btn-accent" type="submit" style="margin-top:20px;">Post topic</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
