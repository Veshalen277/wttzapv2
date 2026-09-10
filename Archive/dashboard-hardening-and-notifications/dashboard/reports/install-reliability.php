<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=\Portal\Connection::get();
foreach(['reports','special_reports','special_report_sectors'] as $table){$s=$db->prepare('SELECT ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->bind_param('s',$table);$s->execute();$row=$s->get_result()->fetch_assoc();if(!$row||strtoupper($row['ENGINE'])!=='INNODB')throw new RuntimeException($table.' must exist and use InnoDB.');}
$db->query("CREATE TABLE IF NOT EXISTS portal_cashup_requests(request_key CHAR(64) CHARACTER SET ascii PRIMARY KEY,payload_hash CHAR(64) CHARACTER SET ascii NOT NULL,user_id INT NOT NULL,report_kind VARCHAR(12) NOT NULL,report_id BIGINT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS portal_report_archives(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,report_id INT NOT NULL,actor_id INT NOT NULL,previous_status VARCHAR(64) NULL,reason VARCHAR(255) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,KEY report_history(report_id)) ENGINE=InnoDB");
echo "Cashup request tracking installed. Existing reports unchanged.\n";
