<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$name=getenv('POINTS_TEST_DATABASE')?:'';
if(!preg_match('/(_test|_testing)$/',$name)){fwrite(STDERR,"Use a disposable POINTS_TEST_DATABASE ending _test or _testing.\n");exit(1);}
require dirname(__DIR__).'/src/Store.php';require dirname(__DIR__).'/src/Policy.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('POINTS_TEST_HOST')?:'localhost',getenv('POINTS_TEST_USER')?:'',getenv('POINTS_TEST_PASSWORD')?:'',$name);
$s=new \Participation\Store($db);$policy=require dirname(__DIR__).'/rules.php';$user=1900000001;
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$db->begin_transaction();
try {
 $source='test_'.bin2hex(random_bytes(5));$date='2025-01-01 10:00:00';
 $s->observe($source,'one',$user,'work',$date,100,true,1,$policy);
 $s->observe($source,'one',$user,'work',$date,100,true,2,$policy);
 $events=$s->rows('SELECT * FROM portal_points_events WHERE source=?','s',[$source]);check(count($events)===1,'Duplicate event');
 $e=$events[0];$s->change($e,false,'Test reversal');$e=$s->rows('SELECT * FROM portal_points_events WHERE id=?','i',[$e['id']])[0];
 $s->change($e,true,'Test restoration');
 $ledger=$s->rows('SELECT delta FROM portal_points_ledger WHERE event_id=? ORDER BY id','i',[$e['id']]);
 check(array_map('intval',array_column($ledger,'delta'))===[10,-10,10],'Award/reverse/restore audit');
 $s->observe($source,'two',$user,'work',$date,100,true,3,$policy);
 $second=$s->rows('SELECT awarded FROM portal_points_events WHERE source=? AND source_ref=?','ss',[$source,'two'])[0];check((int)$second['awarded']===0,'Daily limit bypass');
 $s->observe($source,'undated',$user,'forum_reply',null,100,true,1,$policy);
 $s->observe($source,'undated',$user,'forum_reply',$date,100,true,2,$policy);
 $resolved=$s->rows('SELECT awarded FROM portal_points_events WHERE source=? AND source_ref=?','ss',[$source,'undated'])[0];check((int)$resolved['awarded']===2,'Undated event resolution');
 echo "PASS duplicate prevention, daily cap, reversal, restoration and timestamp resolution.\n";
}finally{$db->rollback();$db->close();}
