<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=\Portal\Connection::get();
$db->query("CREATE TABLE IF NOT EXISTS portal_notifications(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,source VARCHAR(32) NOT NULL,source_ref VARCHAR(80) NOT NULL,title VARCHAR(180) NOT NULL,body TEXT NOT NULL,target VARCHAR(255) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,read_at DATETIME NULL,UNIQUE KEY source_once(user_id,source,source_ref),KEY user_inbox(user_id,read_at,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("CREATE TABLE IF NOT EXISTS portal_notification_state(name VARCHAR(32) PRIMARY KEY,cursor_id BIGINT UNSIGNED NOT NULL) ENGINE=InnoDB");
// Coordinate the initial baseline with the points job; do not flood the inbox with history.
$name='portal_points_'.substr(hash('sha256',$db->query('SELECT DATABASE() db')->fetch_assoc()['db']),0,32);
$s=$db->prepare('SELECT GET_LOCK(?,0) acquired');$s->bind_param('s',$name);$s->execute();if(!(int)$s->get_result()->fetch_assoc()['acquired'])throw new RuntimeException('Points sync is running. Retry installation when it finishes.');
try{$db->query("INSERT IGNORE INTO portal_notification_state(name,cursor_id) SELECT 'points',COALESCE(MAX(id),0) FROM portal_points_ledger");echo "Notifications installed. Only ledger changes after the first installation are announced.\n";}finally{$s=$db->prepare('SELECT RELEASE_LOCK(?)');$s->bind_param('s',$name);$s->execute();}
