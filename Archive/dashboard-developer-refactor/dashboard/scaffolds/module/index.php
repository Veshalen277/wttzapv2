<?php
$dashboardRoot = dirname(__DIR__, 2);
require_once $dashboardRoot . '/app/autoload.php';
$module = \Portal\Modules::definition(basename(__DIR__));
$user = \Portal\Modules::guard(basename(__DIR__));
require_once $dashboardRoot . '/app/bootstrap.php';

// Process writes before rendering any HTML. Replace this demonstration action.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!\Portal\Csrf::valid($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        exit('Refresh the page and try again.');
    }
    \Portal\Flash::set('The starter action ran successfully. No data was changed.');
    \Portal\Http\Response::redirect('/dashboard/modules/' . basename(__DIR__) . '/index.php');
}
$pageTitle = $module['title'];
include $dashboardRoot . '/header.php';
?>
<section class="card">
    <div class="card-header"><h1 class="h5 mb-0"><?= \Portal\Html::escape($pageTitle) ?></h1></div>
    <div class="card-body">
        <p>Your module is ready for development.</p>
        <form method="post">
            <?= \Portal\Csrf::field() ?>
            <button class="btn btn-primary" type="submit">Test form action</button>
        </form>
    </div>
</section>
<?php include $dashboardRoot . '/footer.php'; ?>
