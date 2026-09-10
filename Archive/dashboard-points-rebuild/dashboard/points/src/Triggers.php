<?php
namespace Participation;
// Optional source adapters for mutations whose tables do not preserve activity history.
final class Triggers
{
 public static function install(Store $store):void {
  $plans=[
   ['sc_stock_items','item_id','stock_change','stock',['item_name','qty_on_hand','received_qty','order_qty','cost_price','retail_price','supplier'],true],
   ['stock_items','item_id','stock_change','stock_basic',['qty_on_hand','received_qty','order_qty'],true],
   ['users_tbl','id','profile_update','profile',['profile_photo','user_image','contact_number','address'],false],
   ['projects_tbl','project_id','project_update','project',['status','priority','end_date'],false],
   ['project_message_files','file_id','file_upload','project_file',[],true],
  ];
  foreach($plans as [$table,$id,$kind,$prefix,$tracked,$inserts]){
   $columns=$store->columns($table);
   if(!in_array($id,$columns,true)){echo "SKIP action adapter $table: stable id column unavailable.\n";continue;}
   foreach(['INSERT','UPDATE'] as $verb){
    if($verb==='INSERT'&&!$inserts)continue;
    $fields=array_values(array_intersect($tracked,$columns));
    if($verb==='UPDATE'&&!$fields)continue;
    $condition=$verb==='UPDATE'?implode(' OR ',array_map(fn($f)=>"NOT (OLD.`$f` <=> NEW.`$f`)",$fields)):'1=1';
    $name='portal_points_'.$prefix.'_'.strtolower($verb);
    // UTC storage; day budget identity explicitly uses South African UTC+02 calendar date.
    $sql="CREATE TRIGGER `$name` AFTER $verb ON `$table` FOR EACH ROW BEGIN
     IF COALESCE(@portal_points_actor,0)>0 AND ($condition) THEN
      INSERT INTO portal_points_actions(user_id,kind,source_ref,activity_day,occurred_at)
      VALUES(@portal_points_actor,'$kind',CONCAT('$table:',NEW.`$id`),DATE(UTC_TIMESTAMP()+INTERVAL 2 HOUR),UTC_TIMESTAMP())
      ON DUPLICATE KEY UPDATE id=id;
     END IF;
    END";
    $store->db->query("DROP TRIGGER IF EXISTS `$name`");$store->db->query($sql);echo "Installed $name\n";
   }
  }
 }
}
