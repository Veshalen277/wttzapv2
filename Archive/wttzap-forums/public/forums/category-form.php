<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireRole('admin', 'super_admin');
$activeNav = 'forums';
$businessId = Auth::activeBusinessId();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$category = $id ? Database::one('SELECT * FROM forum_categories WHERE id = ? AND business_id = ?', [$id, $businessId]) : null;
if ($id && !$category) { http_response_code(404); die('Category not found.'); }

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $minRole = $_POST['min_role_to_post'] ?? 'employee';
    $position = (int) ($_POST['position'] ?? 0);

    if ($name === '') {
        $error = 'Give the category a name.';
    } elseif (!in_array($minRole, ['employee', 'manager', 'admin'], true)) {
        $error = 'Invalid posting level.';
    } elseif ($category) {
        Database::run(
            'UPDATE forum_categories SET name=?, description=?, min_role_to_post=?, position=? WHERE id=?',
            [$name, $description, $minRole, $position, $category['id']]
        );
        flash('success', 'Category updated.');
        redirect('forums/index.php');
    } else {
        Database::run(
            'INSERT INTO forum_categories (business_id, name, description, min_role_to_post, position) VALUES (?,?,?,?,?)',
            [$businessId, $name, $description, $minRole, $position]
        );
        flash('success', 'Category created.');
        redirect('forums/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit' : 'New' ?> category · Forums · WttZap</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/forums.css">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../../app/partials/nav.php'; ?>

    <main class="main">
        <div class="topbar">
            <h1><?= $id ? 'Edit category' : 'New category' ?></h1>
            <?php require __DIR__ . '/../../app/partials/user_menu.php'; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <?= csrf_field() ?>
                <label for="name">Name</label>
                <input id="name" name="name" required value="<?= e($category['name'] ?? '') ?>">

                <label for="description">Description</label>
                <input id="description" name="description" value="<?= e($category['description'] ?? '') ?>">

                <label for="min_role_to_post">Who can start new topics here?</label>
                <select id="min_role_to_post" name="min_role_to_post">
                    <?php foreach (['employee' => 'Everyone', 'manager' => 'Managers and above', 'admin' => 'Admins only'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($category['min_role_to_post'] ?? 'employee') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="field-hint">Everyone can still read and reply — this only controls who can start a new topic.</div>

                <label for="position">Order (lower shows first)</label>
                <input type="number" id="position" name="position" value="<?= e((string) ($category['position'] ?? 0)) ?>">

                <button class="btn btn-accent" type="submit" style="margin-top:20px;"><?= $id ? 'Save changes' : 'Create category' ?></button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
