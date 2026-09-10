<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/autoload.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$store=new \Participation\Store(\Portal\Connection::get());
$sql=file_get_contents(__DIR__.'/database/install.sql');
foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$store->db->query($statement);
// Only add the metadata that the source review proved missing. Never invent historical values.
foreach(['orders'=>['points_user_id','INT NULL'],'checklist_items'=>['points_completed_at','DATETIME NULL']] as $table=>[$column,$type]){
 $columns=$store->columns($table);
 if($columns&&!in_array($column,$columns,true))$store->db->query("ALTER TABLE `$table` ADD COLUMN `$column` $type");
}
require_once __DIR__.'/src/Triggers.php';
if(in_array('--with-actions',$argv,true)) \Participation\Triggers::install($store);
else echo "Inventory/profile/project mutation adapters not installed. Use --with-actions when TRIGGER privileges are available.\n";
echo "Points schema installed. Historical unknown ownership/completion dates remain NULL. Run sync.php next.\n";
