<?php
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/points/autoload.php';
use Portal\Html;
$store=new \Participation\Store($con);$uid=$portalUser->id;$error=null;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 if(!\Portal\Csrf::valid($_POST['_csrf']??null)){http_response_code(403);exit('Refresh and try again.');}
 try {
  $action=$_POST['action']??'';$id=(int)($_POST['id']??0);
  if(in_array($action,['add','edit'],true)){
   $item=is_string($_POST['item']??null)?trim($_POST['item']):'';
   if($item===''||mb_strlen($item)>500)throw new \InvalidArgumentException('Enter a checklist item of 1–500 characters.');
   if($action==='add')$store->write('INSERT INTO checklist_items(user_id,item,created_at) VALUES(?,?,NOW())','is',[$uid,$item]);
   else $store->write('UPDATE checklist_items SET item=? WHERE id=? AND user_id=?','sii',[$item,$id,$uid]);
  }elseif($action==='toggle'){
   $store->write('UPDATE checklist_items SET points_completed_at=CASE WHEN checked=0 THEN COALESCE(points_completed_at,NOW()) ELSE points_completed_at END,checked=NOT checked WHERE id=? AND user_id=?','ii',[$id,$uid]);
  }elseif($action==='delete'){$store->write('DELETE FROM checklist_items WHERE id=? AND user_id=?','ii',[$id,$uid]);}
  elseif($action==='delete_completed'){$store->write('DELETE FROM checklist_items WHERE user_id=? AND checked=1','i',[$uid]);}
  else throw new \InvalidArgumentException('Unknown checklist action.');
  \Portal\Http\Response::redirect('/dashboard/users/checklist.php');
 }catch(\InvalidArgumentException $e){$error=$e->getMessage();}
 catch(\Throwable $e){error_log('Checklist: '.$e->getMessage());$error='Checklist could not be saved. Verify the points metadata migration has been installed.';}
}
$items=$store->rows('SELECT * FROM checklist_items WHERE user_id=? ORDER BY checked,id DESC','i',[$uid]);
$pageTitle='My checklist';include dirname(__DIR__).'/header.php';
?>
<h1 class="h4">My checklist</h1><p class="text-muted">Completion points are awarded once per item after the next points sync. Reopening an item reverses that award.</p>
<?php if($error): ?><div class="alert alert-danger"><?= Html::escape($error) ?></div><?php endif; ?>
<form method="post" class="card p-3 mb-3"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="add"><label for="new-item">New item</label><input id="new-item" class="form-control my-2" name="item" maxlength="500" required><div><button class="btn btn-primary" type="submit">Add item</button></div></form>
<?php foreach($items as $item): ?><article class="card p-3 mb-3"><p><?= $item['checked']?'✓ ':'' ?><?= Html::escape($item['item']) ?></p><small class="text-muted">Created <?= Html::escape($item['created_at']) ?></small><form method="post" class="mt-2"><?= \Portal\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-outline-secondary" name="action" value="toggle"><?= $item['checked']?'Reopen':'Mark complete' ?></button></form><details class="mt-2"><summary>Edit or delete</summary><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><label class="visually-hidden" for="edit-<?= (int)$item['id'] ?>">Checklist text</label><input id="edit-<?= (int)$item['id'] ?>" class="form-control my-2" name="item" maxlength="500" required value="<?= Html::escape($item['item']) ?>"><button class="btn btn-primary" name="action" value="edit">Save</button><button class="btn btn-danger" name="action" value="delete">Delete item</button></form></details></article><?php endforeach; ?>
<?php if(!$items): ?><p>No checklist items yet.</p><?php endif; ?><details><summary>Remove completed items</summary><p>Deleting completed items reverses their participation awards on the next sync.</p><form method="post"><?= \Portal\Csrf::field() ?><button class="btn btn-danger" name="action" value="delete_completed">Delete my completed items</button></form></details>
<?php include dirname(__DIR__).'/footer.php'; ?>
