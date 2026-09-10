<?php
require_once __DIR__.'/../app/bootstrap.php';
try {
 if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!\Portal\Csrf::valid($_POST['_csrf']??null)) throw new RuntimeException('Refresh your profile and try again.');
 $file=$_FILES['profileImage']??[];
 if (($file['error']??-1)!==UPLOAD_ERR_OK || ($file['size']??0)>2*1024*1024) throw new RuntimeException('Select an image smaller than 2 MB.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 if (!isset($types[$mime])||!getimagesize($file['tmp_name'])) throw new RuntimeException('Select a JPG, PNG or WebP image.');
 $directory=__DIR__.'/uploads';if(!is_dir($directory)&&!mkdir($directory,0755,true))throw new RuntimeException('Photo storage is unavailable.');
 $name='profile-'.bin2hex(random_bytes(16)).'.'.$types[$mime];$target=$directory.'/'.$name;
 if(!move_uploaded_file($file['tmp_name'],$target))throw new RuntimeException('The photo could not be uploaded.');
 try {
  $path='/dashboard/employee/uploads/'.$name;$id=$portalUser->id;
  $stmt=$con->prepare('UPDATE users_tbl SET profile_photo=? WHERE id=?');$stmt->bind_param('si',$path,$id);$stmt->execute();$stmt->close();
 } catch(Throwable $error) {unlink($target);throw $error;}
 $_SESSION['msg']='Profile photo updated.';$_SESSION['msg_type']='success';
} catch(Throwable $error) {error_log('Profile photo: '.$error->getMessage());$_SESSION['msg']='Photo update failed. Use a JPG, PNG or WebP image smaller than 2 MB and try again.';$_SESSION['msg_type']='error';}
header('Location: /dashboard/employee/my_profile.php');exit;
