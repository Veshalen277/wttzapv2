<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';require __DIR__.'/src/Inventory.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$store=new \Inventory\Inventory(\Portal\Connection::get());
$rows=$store->rows('SELECT i.item_id,i.qty_on_hand,COALESCE(SUM(m.delta),0) ledger_balance FROM sc_stock_items i LEFT JOIN portal_inventory_movements m ON m.item_id=i.item_id GROUP BY i.item_id,i.qty_on_hand HAVING i.qty_on_hand<>ledger_balance');
foreach($rows as $row)echo 'Balance mismatch for item '.$row['item_id'].': stock='.$row['qty_on_hand'].', ledger='.$row['ledger_balance']."\n";
$bad=$store->rows('SELECT id FROM portal_inventory_movements WHERE quantity_before+delta<>quantity_after OR quantity_after<0');
$missing=$store->rows('SELECT DISTINCT m.item_id FROM portal_inventory_movements m LEFT JOIN sc_stock_items i ON i.item_id=m.item_id WHERE i.item_id IS NULL');
echo count($rows)." balance mismatches; ".count($bad)." invalid movement rows; ".count($missing)." missing items.\n";
exit(($rows||$bad||$missing)?1:0);
