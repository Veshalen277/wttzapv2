<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/src/Policy.php';
$rules=require dirname(__DIR__).'/rules.php';$sources=require dirname(__DIR__).'/sources.php';
function check(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);}
$r=$rules['rules']['forum_reply'];
check(\Participation\Policy::award($r,0,'2026-01-01 10:00:00',20)[0]===2,'Forum reply award');
check(\Participation\Policy::award($r,20,'2026-01-01 10:00:00',20)[0]===0,'Daily cap');
check(\Participation\Policy::award($r,0,null,20)[0]===0,'Unknown dates must not score');
check(\Participation\Policy::award($r,0,'2026-01-01 10:00:00',2)[0]===0,'Short text');
check(\Participation\Policy::award($rules['rules']['reaction'],0,'2026-01-01 10:00:00',0,false)[0]===0,'Self reaction');
check(\Participation\Policy::date('2026-02-31')===null,'Reject impossible dates');
foreach($sources as $source)check(isset($rules['rules'][$source['rule']]),'Unregistered rule');
echo "PASS policy, caps, dates, source coverage and self-reaction checks.\n";
