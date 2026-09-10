<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{
 $db=\Portal\Connection::get();$row=$db->query("SELECT cursor_id FROM portal_notification_state WHERE name='points'")->fetch_assoc();if(!$row)throw new RuntimeException('Missing baseline');$baseline=(int)$row['cursor_id'];
 $pending=(int)$db->query("SELECT COUNT(*) n FROM portal_points_ledger l WHERE l.id>$baseline AND l.delta<>0 AND NOT EXISTS(SELECT 1 FROM portal_notifications n WHERE n.user_id=l.user_id AND n.source='points' AND CAST(n.source_ref AS BINARY)=CAST(l.id AS BINARY))")->fetch_assoc()['n'];
 $bad=(int)$db->query("SELECT COUNT(*) n FROM portal_notifications n LEFT JOIN portal_points_ledger l ON CAST(n.source_ref AS BINARY)=CAST(l.id AS BINARY) WHERE n.source='points' AND (l.id IS NULL OR n.user_id<>l.user_id OR l.delta=0)")->fetch_assoc()['n'];
 echo "$pending point notices pending; $bad invalid notification references.\n";exit(($pending||$bad)?1:0);
}catch(Throwable $e){error_log('Notification audit: '.$e->getMessage());fwrite(STDERR,"Notification audit unavailable; inspect setup and logs.\n");exit(1);}
