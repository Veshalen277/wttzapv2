<?php
// Read-only live-data gate. Run after sync.php, from the command line.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/autoload.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$store=new \Participation\Store(\Portal\Connection::get());
if(!$store->installed()){fwrite(STDERR,"Points tables are not installed.\n");exit(1);}
$issues=0;
foreach($store->rows("SELECT e.id,e.user_id,e.awarded,e.active,COALESCE(SUM(l.delta),0) actual FROM portal_points_events e LEFT JOIN portal_points_ledger l ON l.event_id=e.id GROUP BY e.id,e.user_id,e.awarded,e.active HAVING actual<>e.awarded*e.active") as $row){echo 'LEDGER MISMATCH event '.$row['id']."\n";$issues++;}
foreach($store->rows('SELECT l.id FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id WHERE l.user_id<>e.user_id') as $row){echo 'OWNER MISMATCH ledger '.$row['id']."\n";$issues++;}
$latest=$store->rows('SELECT id,status,finished_at FROM portal_points_runs ORDER BY id DESC LIMIT 1')[0]??null;
foreach(require __DIR__.'/sources.php' as $name=>$definition){
 $row=$store->rows('SELECT * FROM portal_points_sources WHERE source=?','s',[$name])[0]??null;
 $ready=$row && $latest && (int)$row['run_id']===(int)$latest['id'] && $row['status']==='ok';
 echo $name.': '.($row['status']??'not checked').' — '.($row['detail']??'Run sync first')."\n";
 if(!$ready)$issues++;
}
if(!$latest || !$latest['finished_at'] || strtotime($latest['finished_at'])<time()-3600){echo "Sync is missing, incomplete or older than one hour.\n";$issues++;}
echo "$issues items require review. Disabled/uninstalled modules can be documented as excluded; do not silently treat missing activity as zero.\n";
exit($issues?1:0);
