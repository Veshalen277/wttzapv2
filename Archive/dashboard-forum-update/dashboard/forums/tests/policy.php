<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/src/Policy.php';
require dirname(__DIR__).'/src/Input.php';
use WorkplaceForum\Policy;
use WorkplaceForum\Input;
function check(bool $condition,string $label): void { if (!$condition) throw new RuntimeException($label); }
$topic=['author_id'=>10,'visibility'=>'private','is_locked'=>0,'is_archived'=>0];
check(!Policy::canView($topic,20,false,false),'Private topic must hide from non-members.');
check(Policy::canView($topic,10,false,false),'Owner must have access.');
check(Policy::canView($topic,20,false,true),'Member must have access.');
check(Policy::canView($topic,20,true,false),'Moderator must have access.');
check(!Policy::canManage($topic,20,false),'Members must not manage topics.');
check(Policy::canManage($topic,10,false),'Owner may manage.');
check(Policy::canManage($topic,20,true),'Moderator may manage.');
check(Policy::canReply($topic,true),'Open unlocked topic accepts replies.');
check(!Policy::canReply($topic,false),'Hidden topic must reject replies.');
$topic['is_locked']=1;check(!Policy::canReply($topic,true),'Locked topic rejects replies.');
$topic['is_locked']=0;$topic['is_archived']=1;check(!Policy::canReply($topic,true),'Archived topic rejects replies.');
$topic['visibility']='open';check(Policy::canView($topic,20,false,false),'Open topic is visible to allowed forum users.');
try { Input::id(-1); throw new RuntimeException('Accepted negative ID.'); } catch (InvalidArgumentException $e) {}
try { Input::text(['body'=>[]],'body',10); throw new RuntimeException('Accepted array input.'); } catch (InvalidArgumentException $e) {}
check(Input::text(['body'=>"  Café ' <tag>  "],'body',100)==="Café ' <tag>",'Keep unicode and punctuation, escape only on output.');
echo "PASS forum access, ownership, lock/archive rules and validation.\n";
