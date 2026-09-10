<?php
require __DIR__.'/bootstrap.php';
if($pointsUser->role!==7){http_response_code(403);exit('Access denied.');}
use Portal\Html;
$error=null;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 if(!\Portal\Csrf::valid($_POST['_csrf']??null)){http_response_code(403);exit('Refresh and try again.');}
 try {
  $uid=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);$delta=filter_var($_POST['delta']??null,FILTER_VALIDATE_INT);
  $reason=is_string($_POST['reason']??null)?trim($_POST['reason']):'';$nonce=$_POST['request_id']??'';
  if(!$uid||$delta===false||$delta===0||abs($delta)>1000||mb_strlen($reason)<15||mb_strlen($reason)>180||!is_string($nonce)||!preg_match('/^[a-f0-9]{32}$/',$nonce))throw new \InvalidArgumentException('Choose a user, a non-zero adjustment between -1000 and 1000, and a reason of 15–180 characters.');
  if(!$store->rows('SELECT id FROM users_tbl WHERE id=?','i',[$uid]))throw new \InvalidArgumentException('User not found.');
  $key=hash('sha256','manual:'.$pointsUser->id.':'.$nonce);$when=date('Y-m-d H:i:s');$audit='Admin #'.$pointsUser->id.': '.$reason;
  $store->db->begin_transaction();
  try {
   $existing=$store->rows('SELECT id FROM portal_points_events WHERE event_key=? FOR UPDATE','s',[$key]);
   if(!$existing){
    $id=$store->write("INSERT INTO portal_points_events(event_key,source,source_ref,user_id,kind,occurred_at,awarded,rule_version,reason,seen_run) VALUES(?,'manual',?,?,'adjustment',?,?,1,?,0)",'ssisis',[$key,$nonce,$uid,$when,$delta,$audit]);
    $store->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,1,?,?,?,?)','iiiss',[$id,$uid,$delta,$when,$audit]);
   }
   $store->db->commit();
  }catch(\Throwable $e){$store->db->rollback();throw $e;}
  \Portal\Flash::set('Adjustment recorded. Repeated submission of this request will not add it twice.');
  \Portal\Http\Response::redirect('/dashboard/points/index.php?user='.$uid.'&range=all');
 }catch(\InvalidArgumentException $e){$error=$e->getMessage();}
 catch(\Throwable $e){error_log('Points adjustment: '.$e->getMessage());$error='Adjustment could not be saved. Check the ledger before retrying.';}
}
$users=$store->rows('SELECT id,fullname FROM users_tbl ORDER BY fullname');$pageTitle='Audited points adjustment';include dirname(__DIR__).'/header.php';
?>
<h1 class="h4">Audited points adjustment</h1><p>Use corrections for verified missing activity or mistakes. The user, amount, administrator and reason remain in the ledger. Adjustments are dated today.</p>
<?php if($error): ?><div class="alert alert-danger"><?= Html::escape($error) ?></div><?php endif; ?>
<form method="post" class="card p-4"><?= \Portal\Csrf::field() ?><input type="hidden" name="request_id" value="<?= bin2hex(random_bytes(16)) ?>"><label for="adjust-user">User</label><select id="adjust-user" class="form-select mb-3" name="user_id" required><?php foreach($users as $user): ?><option value="<?= (int)$user['id'] ?>"><?= Html::escape($user['fullname']) ?></option><?php endforeach; ?></select><label for="adjust-value">Points to add or subtract</label><input id="adjust-value" class="form-control mb-3" type="number" name="delta" min="-1000" max="1000" required><label for="adjust-reason">Verified reason</label><textarea id="adjust-reason" class="form-control mb-3" name="reason" minlength="15" maxlength="180" required></textarea><button class="btn btn-primary" type="submit">Record correction</button></form>
<?php include dirname(__DIR__).'/footer.php'; ?>
