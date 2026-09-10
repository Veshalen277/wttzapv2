<?php
require __DIR__.'/../app/bootstrap.php';$uid=$portalUser->id;$error='';$items=[];$pages=1;$page=max(1,(int)($_GET['page']??1));
try{
 if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  if(!\Portal\Csrf::valid($_POST['_csrf']??null)){http_response_code(403);exit('Refresh Notifications before updating your inbox.');}
  if(isset($_POST['mark_all'])){$s=$con->prepare('UPDATE portal_notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');$s->bind_param('i',$uid);$s->execute();}
  elseif(isset($_POST['open'])){$id=(int)$_POST['open'];$s=$con->prepare('SELECT target FROM portal_notifications WHERE id=? AND user_id=?');$s->bind_param('ii',$id,$uid);$s->execute();$row=$s->get_result()->fetch_assoc();if(!$row){http_response_code(404);exit('Notification not found.');}$s=$con->prepare('UPDATE portal_notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?');$s->bind_param('ii',$id,$uid);$s->execute();$target=$row['target']==='/dashboard/points/index.php'?$row['target']:'/dashboard/notifications/index.php';header('Location: '.$target);exit;}
  header('Location: /dashboard/notifications/index.php');exit;
 }
 $s=$con->prepare('SELECT COUNT(*) n FROM portal_notifications WHERE user_id=?');$s->bind_param('i',$uid);$s->execute();$total=(int)$s->get_result()->fetch_assoc()['n'];$pages=max(1,(int)ceil($total/25));$page=min($page,$pages);$offset=($page-1)*25;
 $s=$con->prepare('SELECT * FROM portal_notifications WHERE user_id=? ORDER BY id DESC LIMIT 25 OFFSET '.$offset);$s->bind_param('i',$uid);$s->execute();$items=$s->get_result()->fetch_all(MYSQLI_ASSOC);
}catch(Throwable $e){error_log('Notifications inbox: '.$e->getMessage());$error='Notifications are temporarily unavailable. Please try again later.';}
$pageTitle='Notifications';require __DIR__.'/../header.php';$e=static fn($v)=>\Portal\Html::escape((string)$v);
?>
<div class="container-fluid" style="max-width:960px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1>Notifications</h1><p>Updates about your contributions and progress.</p></div><a href="/dashboard/points/index.php">My points</a></div>
<?php if($error):?><div class="alert alert-warning"><?= $e($error) ?></div><?php else:?><form method="post" class="mb-3"><?= \Portal\Csrf::field() ?><button class="btn btn-outline-secondary" name="mark_all">Mark all as read</button></form><?php if(!$items):?><div class="card p-4"><h2>You’re all caught up</h2><p>New point awards and adjustments will appear here after participation updates run.</p></div><?php endif; ?>
<?php foreach($items as $item):?><article class="card p-4 mb-3"><div class="d-flex justify-content-between"><h2><?= $e($item['title']) ?></h2><?php if(!$item['read_at']):?><span class="badge bg-primary">New</span><?php endif; ?></div><p><?= $e($item['body']) ?></p><small class="text-muted"><?= $e($item['created_at']) ?></small><form method="post" class="mt-3"><?= \Portal\Csrf::field() ?><button class="btn btn-primary" name="open" value="<?= (int)$item['id'] ?>">View points history</button></form></article><?php endforeach; ?>
<nav class="d-flex gap-3" aria-label="Notification pages"><?php if($page>1):?><a href="?page=<?= $page-1 ?>">Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if($page<$pages):?><a href="?page=<?= $page+1 ?>">Next</a><?php endif; ?></nav><?php endif; ?></div><?php require __DIR__.'/../footer.php'; ?>
