<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=\Portal\Connection::get();
foreach(['sc_stock_items','sc_stock_categories'] as $table){
 $s=$db->prepare('SELECT ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->bind_param('s',$table);$s->execute();$row=$s->get_result()->fetch_assoc();
 if(!$row||strtoupper($row['ENGINE'])!=='INNODB')throw new RuntimeException($table.' must exist and use InnoDB. Review its schema before migration.');
}
if($db->query('SELECT item_id FROM sc_stock_items WHERE qty_on_hand IS NULL OR qty_on_hand<0 OR qty_on_hand>999999999 OR qty_on_hand<>FLOOR(qty_on_hand) LIMIT 1')->num_rows)throw new RuntimeException('Resolve null, negative, fractional or oversized legacy stock quantities before installation.');
foreach(explode(';',file_get_contents(__DIR__.'/database/install.sql')) as $sql)if(trim($sql)!=='')$db->query($sql);
$db->begin_transaction();
try {
 $rows=$db->query('SELECT item_id,qty_on_hand FROM sc_stock_items ORDER BY item_id FOR UPDATE');
 $s=$db->prepare("INSERT IGNORE INTO portal_inventory_movements(request_key,item_id,actor_id,kind,quantity_before,delta,quantity_after,reason) VALUES(?,?,NULL,'opening',0,?,?,'Balance at inventory migration; not historical activity')");
 while($r=$rows->fetch_assoc()){$id=(int)$r['item_id'];if($db->query('SELECT id FROM portal_inventory_movements WHERE item_id='.$id.' LIMIT 1')->num_rows)continue;$qty=(int)$r['qty_on_hand'];$key=hash('sha256','inventory-opening:'.$id);$s->bind_param('siii',$key,$id,$qty,$qty);$s->execute();}
 $db->commit();echo "Inventory tables installed. Existing balances recorded once; no balances changed.\n";
}catch(Throwable $e){$db->rollback();throw $e;}
