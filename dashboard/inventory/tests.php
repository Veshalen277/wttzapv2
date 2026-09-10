<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/src/Inventory.php';
use Inventory\Inventory;
foreach([[10,'receive',3,13],[10,'issue',3,7],[10,'count',0,0],[10,'count',5,5]] as [$before,$kind,$quantity,$expected]){
 if(Inventory::balance($before,$kind,$quantity)!==$expected)throw new RuntimeException('Balance calculation failed');
}
foreach([[-1],['1.5'],['-4'],['1e3'],['9999999999']] as [$value]){
 try{Inventory::number($value);throw new RuntimeException('Invalid quantity accepted');}catch(InvalidArgumentException $expected){}
}
try{Inventory::balance(1,'issue',2);throw new RuntimeException('Overselling accepted');}catch(InvalidArgumentException $expected){}
echo "Quantity validation and balance arithmetic passed. Database concurrency still needs integration testing.\n";
