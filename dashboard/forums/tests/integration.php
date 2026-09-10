<?php
// Run ONLY with a disposable test database. Uses a temporary user id and rolls back test fixtures by deletion.
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
if (getenv('FORUM_TEST_DATABASE') === false || getenv('FORUM_TEST_DATABASE') === '') {
    fwrite(STDERR,"Set FORUM_TEST_DATABASE to a disposable database; this test inserts forum fixtures.\n");exit(1);
}
if (!preg_match('/(_test|_testing)$/', getenv('FORUM_TEST_DATABASE'))) { fwrite(STDERR, "Test database name must end in _test or _testing.\n"); exit(1); }
require dirname(__DIR__).'/src/Repository.php';
require dirname(__DIR__).'/src/Policy.php';
require dirname(__DIR__).'/src/Input.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FORUM_TEST_HOST')?:'localhost',getenv('FORUM_TEST_USER')?:'',getenv('FORUM_TEST_PASSWORD')?:'',getenv('FORUM_TEST_DATABASE'));
$db->set_charset('utf8mb4');
$owner=new WorkplaceForum\Repository($db,1900000001,false);
$outsider=new WorkplaceForum\Repository($db,1900000002,false);
$moderator=new WorkplaceForum\Repository($db,1900000003,true);
$id=null;
function expect(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
try {
    expect($owner->installed(),'Install schema in disposable database first.');
    $id=$owner->create('Private integration fixture','Integration body','private');
    expect($outsider->topic($id)===null,'Private topic leaked.');
    expect($moderator->topic($id)!==null,'Moderator cannot inspect topic.');
    try { $outsider->mutate($id,'reply',['body'=>'unauthorized'],[2,7]); throw new RuntimeException('Unauthorized reply succeeded.'); } catch (DomainException $e) {}
    $owner->mutate($id,'add_member',['user_id'=>1900000002],[2,7]);
    expect($outsider->topic($id)!==null,'Added member cannot read.');
    $owner->mutate($id,'remove_member',['user_id'=>1900000002],[2,7]);
    expect($outsider->topic($id)===null,'Removed member retains private access.');
    $owner->mutate($id,'reply',['body'=>'Valid reply'],[2,7]);
    expect($owner->replies($id,1,30)['total']===1,'Reply missing.');
    $owner->mutate($id,'lock',[],[2,7]);
    try { $owner->mutate($id,'reply',['body'=>'Blocked'],[2,7]); throw new RuntimeException('Locked reply succeeded.'); } catch (DomainException $e) {}
    $owner->mutate($id,'archive',[],[2,7]);
    $row=$owner->topic($id);expect((int)$row['is_archived']===1,'Archive failed.');
    $owner->mutate($id,'restore',[],[2,7]);
    echo "PASS forum repository privacy, replies, locks, archive and restore.\n";
} finally {
    if ($id!==null) { $stmt=$db->prepare('DELETE FROM portal_forum_topics WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close(); }
    $db->close();
}
