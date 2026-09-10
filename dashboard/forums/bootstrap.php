<?php
if (!is_file(dirname(__DIR__) . '/app/autoload.php')) {
    http_response_code(503); exit('Install the portal developer refactor before enabling the forum.');
}
require_once dirname(__DIR__) . '/app/autoload.php';
require_once __DIR__ . '/src/Policy.php';
require_once __DIR__ . '/src/Input.php';
require_once __DIR__ . '/src/Repository.php';
$forumConfig = require __DIR__ . '/config.php';
if (!$forumConfig['enabled']) { http_response_code(404); exit('Forum is disabled.'); }
$forumUser = \Portal\Auth::requireRoles($forumConfig['roles']);
require_once dirname(__DIR__) . '/app/bootstrap.php';
$forumModerator = in_array($forumUser->role, $forumConfig['moderator_roles'], true);
$forumRepository = new \WorkplaceForum\Repository($con, $forumUser->id, $forumModerator);
try { $forumInstalled = $forumRepository->installed(); }
catch (\Throwable $error) { error_log('Forum setup check: '.$error->getMessage()); $forumInstalled=false; }
if (!$forumInstalled) {
    http_response_code(503);
    $pageTitle = 'Forum unavailable';
    include dirname(__DIR__) . '/header.php';
    echo '<div class="alert alert-warning">The forum is not set up yet. Ask the portal administrator to install the forum database tables.</div>';
    include dirname(__DIR__) . '/footer.php';
    exit;
}
