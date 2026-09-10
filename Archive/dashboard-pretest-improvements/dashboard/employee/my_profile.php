<?php
require_once __DIR__ . '/../app/bootstrap.php';
$id = $portalUser->id;
$escape = static function ($value): string { return \Portal\Html::escape((string)($value ?? '')); };
$stmt=$con->prepare('SELECT * FROM users_tbl WHERE id=?');
$stmt->bind_param('i',$id); $stmt->execute(); $userRow=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$userRow) { http_response_code(404); exit('Employee profile not found.'); }
$error='';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST') {
    try {
        if (!\Portal\Csrf::valid($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Your session expired. Refresh this page and try again.');
        if (isset($_POST['change_password'])) {
            $current=$_POST['current_password'] ?? null; $new=$_POST['new_password'] ?? null; $confirm=$_POST['confirm_password'] ?? null;
            if (!is_string($current)||!is_string($new)||!is_string($confirm)) throw new InvalidArgumentException('Please complete all password fields.');
            if (!\Portal\Passwords::verify($current,(string)$userRow['user_pass'])) throw new InvalidArgumentException('The current password is incorrect.');
            if ($new!==$confirm) throw new InvalidArgumentException('The new passwords do not match.');
            $hash=\Portal\Passwords::replacement($new,(string)$userRow['user_pass']);
            $old=(string)$userRow['user_pass'];
            $stmt=$con->prepare('UPDATE users_tbl SET user_pass=? WHERE id=? AND user_pass=?');
            $stmt->bind_param('sis',$hash,$id,$old);$stmt->execute();
            if ($stmt->affected_rows!==1) throw new InvalidArgumentException('The password was unchanged or changed in another session. Please refresh.');
            $stmt->close(); session_regenerate_id(true);
            $_SESSION['msg']='Password updated.';
        } elseif (isset($_POST['update_info'])) {
            $fields=['user_name','contact_number','address','next_of_kin','next_of_kin_number'];$values=[];
            foreach($fields as $field) {
                if (!is_string($_POST[$field] ?? null)) throw new InvalidArgumentException('Please complete the contact fields.');
                $values[]=trim($_POST[$field]);
            }
            if ($values[0]==='') throw new InvalidArgumentException('Please enter your name.');
            $stmt=$con->prepare('UPDATE users_tbl SET fullname=?,contact_number=?,address=?,next_of_kin=?,next_of_kin_number=? WHERE id=?');
            $stmt->bind_param('sssssi',$values[0],$values[1],$values[2],$values[3],$values[4],$id);$stmt->execute();$stmt->close();
            $_SESSION['u_data'][0]=$values[0]; $_SESSION['msg']='Contact details updated.';
        } else throw new InvalidArgumentException('Unknown profile action.');
        $_SESSION['msg_type']='success'; header('Location: /dashboard/employee/my_profile.php'); exit;
    } catch (InvalidArgumentException $exception) { $error=$exception->getMessage(); }
      catch (Throwable $exception) { error_log('Profile update: '.$exception->getMessage());$error='The update could not be saved. Please try again.'; }
}
$leave=json_decode((string)($userRow['leave_balances']??''),true);
$pageTitle='My profile'; require __DIR__.'/../header.php';
?>
<link rel="stylesheet" href="<?= $escape(\Portal\Html::asset('employee/assets/profile.css')) ?>">
<div class="profile-page">
  <header class="profile-title"><div><p class="profile-kicker">YOUR WORKSPACE</p><h1>My profile</h1><p>Manage your details, responsibilities, and account security.</p></div><a href="emp_profile.php">Back to work report →</a></header>
  <?php if($error): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
  <div class="profile-layout">
    <aside class="profile-summary">
      <img src="<?= $escape(($userRow['profile_photo'] ?? '') ?: '/dashboard/employee/uploads/placeholder.jpg') ?>" width="72" height="72" alt="">
      <h2><?= $escape($userRow['fullname']) ?></h2><p><?= $escape($userRow['user_des']??'') ?></p>
      <dl><dt>Department</dt><dd><?= $escape($userRow['user_scale']??'—') ?></dd><dt>Started</dt><dd><?= $escape($userRow['date_started']??'—') ?></dd></dl>
      <form action="upload.php" method="post" enctype="multipart/form-data"><?= \Portal\Csrf::field() ?><label for="profile-photo">Profile photo</label><input id="profile-photo" type="file" name="profileImage" accept="image/jpeg,image/png,image/webp" required><small>JPG, PNG or WebP · up to 2 MB</small><button name="submit" class="btn btn-outline-secondary">Update photo</button></form>
      <nav aria-label="Profile sections"><a href="#contact-details">Contact details</a><a href="#responsibilities">Responsibilities</a><a href="#account-security">Account security</a></nav>
    </aside>
    <div class="profile-sections">
      <section class="profile-section" id="contact-details"><h2>Contact details</h2><p>Keep your contact and emergency information current.</p><form method="post"><?= \Portal\Csrf::field() ?><div class="profile-fields">
      <?php foreach(['user_name'=>['Full name','fullname'],'contact_number'=>['Phone number','contact_number'],'address'=>['Home address','address'],'next_of_kin'=>['Emergency contact','next_of_kin'],'next_of_kin_number'=>['Emergency phone','next_of_kin_number']] as $name=>[$label,$column]): ?>
        <div><label for="<?= $name ?>"><?= $label ?></label><input id="<?= $name ?>" name="<?= $name ?>" type="<?= strpos($name,'number')!==false?'tel':'text' ?>" value="<?= $escape(isset($_POST['update_info'])&&is_string($_POST[$name]??null)?$_POST[$name]:($userRow[$column]??'')) ?>" <?= $name==='user_name'?'required':'' ?>></div>
      <?php endforeach; ?></div><div class="profile-actions"><button class="btn btn-primary" name="update_info">Save contact details</button></div></form></section>
      <section class="profile-section" id="responsibilities"><h2>Responsibilities</h2><ul class="profile-responsibilities"><?php foreach(array_filter(array_map('trim',preg_split('/(?<=\.)\s+|\r?\n/',(string)($userRow['user_res']??'')))) as $task): ?><li><?= $escape($task) ?></li><?php endforeach; ?></ul>
      <details><summary>Recorded leave balance</summary><p><?= isset($leave['accrued_leave'])?$escape($leave['accrued_leave']).' days recorded as accrued.':'No accrued balance is recorded.' ?> Approved leave and policy adjustments must be checked before using this as an available balance.</p></details></section>
      <section class="profile-section" id="account-security"><h2>Account security</h2><p>Use a unique passphrase. Letters, numbers, spaces, punctuation and Unicode are supported; 8–72 bytes.</p><form method="post"><?= \Portal\Csrf::field() ?><div class="profile-fields">
      <?php foreach(['current_password'=>'Current password','new_password'=>'New password','confirm_password'=>'Confirm new password'] as $name=>$label): ?><div><label for="<?= $name ?>"><?= $label ?></label><input type="password" id="<?= $name ?>" name="<?= $name ?>" autocomplete="<?= $name==='current_password'?'current-password':'new-password' ?>" required></div><?php endforeach; ?></div><div class="profile-actions"><button class="btn btn-primary" name="change_password">Change password</button></div></form></section>
    </div>
  </div>
</div>
<?php require __DIR__.'/../footer.php'; ?>
