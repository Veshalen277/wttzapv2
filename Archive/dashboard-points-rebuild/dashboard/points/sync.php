<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/autoload.php';
date_default_timezone_set('Africa/Johannesburg');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$store=new \Participation\Store(\Portal\Connection::get());
if(!$store->installed()){fwrite(STDERR,"Install points tables first.\n");exit(1);}
$lock='portal_points_'.substr(hash('sha256',$store->rows('SELECT DATABASE() db')[0]['db']),0,32);
if(!(int)$store->rows('SELECT GET_LOCK(?,0) acquired','s',[$lock])[0]['acquired']){echo "Another points sync is running.\n";exit;}
$run=0;
try {
 $run=$store->write("INSERT INTO portal_points_runs(status) VALUES('running')");
 $sync=new \Participation\Sync($store,require __DIR__.'/rules.php');
 foreach(require __DIR__.'/sources.php' as $name=>$source)$sync->source($name,$source,$run);
 $issues=(int)$store->rows("SELECT COUNT(*) n FROM portal_points_sources WHERE run_id=? AND status<>'ok'",'i',[$run])[0]['n'];
 $store->write('UPDATE portal_points_runs SET finished_at=NOW(),status=? WHERE id=?','si',[$issues?'partial':'complete',$run]);
 echo "Sync $run finished; $issues sources need attention. See Points → Coverage.\n";
}catch(\Throwable $error){if($run)$store->write("UPDATE portal_points_runs SET finished_at=NOW(),status='failed' WHERE id=?",'i',[$run]);fwrite(STDERR,"Points sync failed. Check server logs.\n");error_log($error->getMessage());exit(1);}
finally{$store->rows('SELECT RELEASE_LOCK(?)','s',[$lock]);}
