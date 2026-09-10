<?php
require __DIR__.'/../app/Passwords.php';
foreach(["Letters123!", " spaces & symbols ' \" ", "Unicode-é漢字🙂"] as $password){
 foreach([sha1('old password'),password_hash('old password',PASSWORD_DEFAULT)] as $old){
  $hash=\Portal\Passwords::replacement($password,$old);
  if(!\Portal\Passwords::verify($password,$hash)||\Portal\Passwords::verify($password.'x',$hash))throw new RuntimeException('Password roundtrip failed');
 }
}
foreach(['short',str_repeat('x',73),"null\0byte"] as $bad){
 try{\Portal\Passwords::validate($bad);throw new RuntimeException('Invalid password accepted');}
 catch(InvalidArgumentException $expected){}
}
echo "Password character and compatibility checks passed.\n";
