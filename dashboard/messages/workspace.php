<?php
require __DIR__.'/../app/bootstrap.php';
$uid=$portalUser->id;$e=static fn($v)=>\Portal\Html::escape((string)($v??''));
$query=static function(string $sql,string $types='',array $args=[])use($con){$s=$con->prepare($sql);if($types!=='')$s->bind_param($types,...$args);$s->execute();return $s;};
$rows=static function(string $sql,string $types='',array $args=[])use($query){$s=$query($sql,$types,$args);try{return $s->get_result()->fetch_all(MYSQLI_ASSOC);}finally{$s->close();}};
$thread=max(0,(int)($_GET['message_id']??0));$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 try{
  if(!\Portal\Csrf::valid($_POST['_csrf']??null))throw new InvalidArgumentException('Refresh the page and try again.');
  $action=$_POST['action']??'';
  if($action==='post'||$action==='reply'){
   $body=$_POST['body']??null;
   if(!is_string($body)||trim($body)===''||mb_strlen($body)>10000)throw new InvalidArgumentException('Write a message of up to 10,000 characters.');$body=trim($body);
   if($action==='post'){$s=$query('INSERT INTO messages(user_id,message_text,posted_at) VALUES(?,?,NOW())','is',[$uid,$body]);$thread=(int)$s->insert_id;$s->close();}
   else{
    $thread=(int)($_POST['message_id']??0);
    $con->begin_transaction();
    try{if(!$rows('SELECT id FROM messages WHERE id=? FOR UPDATE','i',[$thread]))throw new InvalidArgumentException('This conversation no longer exists.');$s=$query('INSERT INTO replies(message_id,user_id,reply_text,replied_at) VALUES(?,?,?,NOW())','iis',[$thread,$uid,$body]);$s->close();$con->commit();}catch(Throwable $ex){$con->rollback();throw $ex;}
   }
   $_SESSION['msg']='Your message was posted.';
  }elseif($action==='delete'){
   $id=(int)($_POST['message_id']??0);
   foreach($rows("SELECT table_name,engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('messages','replies','notifications')") as $table){if(strtoupper((string)$table['engine'])!=='INNODB')throw new InvalidArgumentException('Removal is unavailable until your administrator enables transactional storage for messages.');}
   $con->begin_transaction();
   try{
    $post=$rows('SELECT user_id FROM messages WHERE id=? FOR UPDATE','i',[$id])[0]??null;
    if(!$post||((int)$post['user_id']!==$uid&&$portalUser->role!==7))throw new InvalidArgumentException('You cannot remove this conversation.');
    $query('DELETE FROM replies WHERE message_id=?','i',[$id])->close();
    // Delete linked notices when that optional table is installed.
    $exists=$rows("SELECT COUNT(*) n FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='notifications' AND column_name='message_id'")[0]['n'];
    if($exists)$query('DELETE FROM notifications WHERE message_id=?','i',[$id])->close();
    $query('DELETE FROM messages WHERE id=?','i',[$id])->close();$con->commit();$thread=0;$_SESSION['msg']='Conversation removed.';
   }catch(Throwable $ex){$con->rollback();throw $ex;}
  }else throw new InvalidArgumentException('This form has changed. Refresh Messages and try again.');
  $_SESSION['msg_type']='success';header('Location: /dashboard/messages/message_board.php'.($thread?'?message_id='.$thread:''));exit;
 }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){error_log('Messages workspace: '.$ex->getMessage());$error='We could not save that change. Please check the conversation before retrying.';}
}
$q=is_string($_GET['q']??null)?trim($_GET['q']):'';$page=max(1,(int)($_GET['page']??1));$posts=[];$replies=[];$total=0;$pages=1;$unavailable=false;
try{
 if($thread){$posts=$rows('SELECT m.*,u.fullname,u.user_scale FROM messages m LEFT JOIN users_tbl u ON u.id=m.user_id WHERE m.id=?','i',[$thread]);if(!$posts){http_response_code(404);$error='This conversation is no longer available.';}
  $total=(int)$rows('SELECT COUNT(*) n FROM replies WHERE message_id=?','i',[$thread])[0]['n'];$pages=max(1,(int)ceil($total/30));$page=min($page,$pages);$offset=($page-1)*30;
  $replies=$rows('SELECT r.*,u.fullname FROM replies r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE r.message_id=? ORDER BY r.replied_at,r.id LIMIT 30 OFFSET '.$offset,'i',[$thread]);
 }else{
  $filter='%'.$q.'%';$total=(int)$rows('SELECT COUNT(*) n FROM messages WHERE message_text LIKE ?','s',[$filter])[0]['n'];$pages=max(1,(int)ceil($total/12));$page=min($page,$pages);$offset=($page-1)*12;
  $posts=$rows('SELECT m.*,u.fullname,u.user_scale,(SELECT COUNT(*) FROM replies r WHERE r.message_id=m.id) reply_count FROM messages m LEFT JOIN users_tbl u ON u.id=m.user_id WHERE m.message_text LIKE ? ORDER BY m.posted_at DESC,m.id DESC LIMIT 12 OFFSET '.$offset,'s',[$filter]);
 }
}catch(Throwable $ex){error_log('Messages read: '.$ex->getMessage());$unavailable=true;$error='Messages are temporarily unavailable. Please try again later.';}
$pageTitle='Messages';$portalNavigationPath='/dashboard/messages/message_board.php';require __DIR__.'/../header.php';
?>
<link rel="stylesheet" href="<?= $e(\Portal\Html::asset('messages/assets/workspace.css')) ?>">
<div class="messages-workspace"><header class="mw-heading"><div><p class="mw-eyebrow">YOUR TEAM / MESSAGES</p><h1><?= $thread?'Conversation':'Team messages' ?></h1><p>Share an update, ask a question, or catch up with your team.</p></div><a class="btn btn-primary" href="message_board.php#compose">Write a message</a></header>
<?php if($error):?><div class="alert alert-warning" role="alert"><?= $e($error) ?></div><?php endif; ?>
<div class="mw-layout"><div class="mw-feed">
<?php if($thread):?><a href="message_board.php">← All team messages</a><?php elseif(!$unavailable):?>
<section class="mw-panel" id="compose"><h2>What would you like to share?</h2><p>This message will be visible on the team messageboard.</p><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="post"><label class="visually-hidden" for="mw-body">Your message</label><textarea id="mw-body" name="body" rows="4" maxlength="10000" placeholder="Share an update or ask your team a question…" required><?= isset($_POST['action'])&&$_POST['action']==='post'&&is_string($_POST['body']??null)?$e($_POST['body']):'' ?></textarea><div class="mw-form-footer"><span>Posting as <?= $e($portalUser->name) ?></span><button class="btn btn-primary">Post message</button></div></form></section>
<form class="mw-search" method="get"><label class="visually-hidden" for="mw-search">Search messages</label><input id="mw-search" name="q" value="<?= $e($q) ?>" placeholder="Find something in team messages"><button class="btn btn-outline-secondary">Search</button></form>
<?php endif; ?>
<?php foreach($posts as $post):?><article class="mw-panel mw-post"><header><span class="mw-avatar" aria-hidden="true"><?= $e(mb_substr($post['fullname']??'?',0,1)) ?></span><div><strong><?= $e($post['fullname']??'Former team member') ?></strong><small><?= $e($post['user_scale']??'') ?> · <?= $e($post['posted_at']) ?></small></div></header>
<?php $text=(string)$post['message_text'];if(!$thread&&mb_strlen($text)>550):?><p class="mw-body"><?= $e(mb_substr($text,0,550)) ?>…</p><?php else:?><p class="mw-body"><?= $e($text) ?></p><?php endif; ?>
<?php if(!$thread):?><div class="mw-post-footer"><a href="?message_id=<?= (int)$post['id'] ?>"><?= (int)$post['reply_count'] ?> <?= (int)$post['reply_count']===1?'reply':'replies' ?> · Open conversation</a><a href="?message_id=<?= (int)$post['id'] ?>#reply">Reply</a></div><?php else:?><details class="mw-details"><summary>More options &amp; message details</summary><p>Message #<?= (int)$post['id'] ?> · Posted <?= $e($post['posted_at']) ?></p><?php if((int)$post['user_id']===$uid||$portalUser->role===7):?><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="message_id" value="<?= (int)$post['id'] ?>"><label><input type="checkbox" required> Remove this conversation and all its replies.</label><button class="btn btn-outline-danger">Remove conversation</button></form><?php endif; ?></details><?php endif; ?></article><?php endforeach; ?>
<?php if(!$posts&&!$thread&&!$unavailable):?><section class="mw-panel"><h2><?= $q!==''?'No matching messages':'Start the conversation' ?></h2><p><?= $q!==''?'Try a different word or clear your search.':'Your team’s updates and replies will appear here.' ?></p></section><?php endif; ?>
<?php if($thread&&$posts):?><h2>Replies <span class="mw-muted">(<?= $total ?>)</span></h2><?php if(!$replies):?><p class="mw-muted">No replies yet. You can be the first to respond.</p><?php endif; ?><?php foreach($replies as $reply):?><article class="mw-panel mw-reply"><strong><?= $e($reply['fullname']??'Former team member') ?></strong><small><?= $e($reply['replied_at']) ?></small><p class="mw-body"><?= $e($reply['reply_text']) ?></p></article><?php endforeach; ?>
<section class="mw-panel" id="reply"><h2>Add your reply</h2><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="message_id" value="<?= $thread ?>"><label class="visually-hidden" for="mw-reply">Your reply</label><textarea id="mw-reply" name="body" rows="4" maxlength="10000" required placeholder="Write a helpful reply…"><?= isset($_POST['action'])&&$_POST['action']==='reply'&&is_string($_POST['body']??null)?$e($_POST['body']):'' ?></textarea><button class="btn btn-primary">Post reply</button></form></section><?php endif; ?>
<?php if($pages>1):?><nav class="mw-pagination" aria-label="Message pages"><?php if($page>1):?><a href="?<?= $e(http_build_query(['message_id'=>$thread,'q'=>$q,'page'=>$page-1])) ?>">← Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if($page<$pages):?><a href="?<?= $e(http_build_query(['message_id'=>$thread,'q'=>$q,'page'=>$page+1])) ?>">Next →</a><?php endif; ?></nav><?php endif; ?>
</div><aside class="mw-aside"><section class="mw-panel"><h2>Your conversations</h2><a href="message_board.php">Team messageboard <span>Updates everyone on the board can see</span></a><a href="view_private_messages.php">Private messages <span>Open your personal inbox</span></a><a href="view_groups.php">Groups <span>Open your group workspace</span></a></section><section class="mw-panel"><h2>Keep the team in the loop</h2><p>Include what happened, who needs to know, and what help you need. Reply to an existing conversation to keep the discussion together.</p><details><summary>Visibility &amp; useful details</summary><p>Use the team board for shared updates. Avoid posting passwords, private employee information or sensitive customer details.</p><p>Points, where enabled, follow the published participation rules. They appear after the next successful update.</p><a href="/dashboard/points/index.php">View your points</a><p>For technical troubleshooting, ask your administrator to consult the Messages README.</p></details></section></aside></div></div>
<?php require __DIR__.'/../footer.php'; ?>
