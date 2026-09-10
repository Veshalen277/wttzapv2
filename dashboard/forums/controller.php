<?php
require __DIR__ . '/bootstrap.php';
use Portal\Html;
use Portal\Csrf;
use Portal\Flash;
use Portal\Http\Response;
use WorkplaceForum\Input;
use WorkplaceForum\Policy;

$forumView = $forumView ?? 'feed';
$error = null;
$topicId = 0;
if ($forumView === 'thread') {
    try { $topicId = Input::id($_GET['id'] ?? $_GET['topic_id'] ?? null); }
    catch (\InvalidArgumentException $exception) { http_response_code(404); exit('Discussion not found.'); }
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::valid($_POST['_csrf'] ?? null)) { http_response_code(403); exit('Refresh the page and try again.'); }
    try {
        $action = $_POST['action'] ?? '';
        if (!is_string($action)) throw new \InvalidArgumentException('Invalid action.');
        if ($forumView === 'create' && $action === 'create') {
            $visibility=$_POST['visibility']??'open';
            if (!in_array($visibility,['open','private'],true)) throw new \InvalidArgumentException('Invalid visibility.');
            $topicId=$forumRepository->create(Input::text($_POST,'title',180),Input::text($_POST,'body',10000),$visibility);
        } elseif ($forumView === 'thread') {
            $forumRepository->mutate($topicId,$action,$_POST,$forumConfig['roles']);
        } else { throw new \InvalidArgumentException('Invalid action for this page.'); }
        Flash::set('Discussion updated.');
        Response::redirect('/dashboard/forums/thread.php?id='.$topicId);
    } catch (\InvalidArgumentException | \DomainException $exception) {
        $error=$exception->getMessage(); http_response_code(422);
    } catch (\Throwable $exception) {
        error_log('Forum write: '.$exception->getMessage());
        $error='The change could not be saved. Your submitted text is still shown below.'; http_response_code(500);
    }
}
try {
    if ($forumView==='feed') {
        $search=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,180):'';
        $filter=in_array($_GET['filter']??'', ['mine','archived'],true)?$_GET['filter']:'all';
        $page=max(1,(int)($_GET['page']??1));
        $feed=$forumRepository->feed($search,$filter,$page,$forumConfig['page_size']);
        $pageTitle='Forum';
    } elseif ($forumView==='thread') {
        $topic=$forumRepository->topic($topicId);
        if (!$topic) { http_response_code(404); exit('Discussion not found.'); }
        $canManage=Policy::canManage($topic,$forumUser->id,$forumModerator);
        $canReply=Policy::canReply($topic,true);
        $replies=$forumRepository->replies($topicId,max(1,(int)($_GET['page']??1)),$forumConfig['reply_page_size']);
        $members=$canManage && $topic['visibility']==='private'?$forumRepository->members($topicId):[];
        $candidates=$canManage && $topic['visibility']==='private'?$forumRepository->candidates($forumConfig['roles']):[];
        $pageTitle=$topic['title'];
    } else { $pageTitle='New discussion'; }
} catch (\Throwable $exception) {
    error_log('Forum read: '.$exception->getMessage());
    http_response_code(503); exit('The forum is temporarily unavailable. Please try again.');
}
$portalNavigationPath='/dashboard/forums/'.($forumView==='create'?'create_topic.php':'view_topics.php');
include dirname(__DIR__).'/header.php';
?>
<link rel="stylesheet" href="<?= Html::escape(Html::asset('forums/assets/forum.css')) ?>">
<div class="forum-module">
    <div class="forum-toolbar">
        <div><span class="forum-eyebrow">WORKPLACE DISCUSSIONS</span><h1><?= Html::escape($pageTitle) ?></h1></div>
        <nav aria-label="Forum pages"><a class="btn btn-outline-secondary" href="/dashboard/forums/view_topics.php">All discussions</a> <a class="btn btn-primary" href="/dashboard/forums/create_topic.php">New discussion</a></nav>
    </div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Html::escape($error) ?></div><?php endif; ?>
    <?php require __DIR__.'/views/'.$forumView.'.php'; ?>
</div>
<?php include dirname(__DIR__).'/footer.php'; ?>
