<?php
namespace Inventory;
final class Inventory
{
 private \mysqli $db;
 public function __construct(\mysqli $db){$this->db=$db;}
 public function query(string $sql,string $types='',array $values=[]): \mysqli_stmt {
  $s=$this->db->prepare($sql);if($types!=='')$s->bind_param($types,...$values);$s->execute();return $s;
 }
 public function rows(string $sql,string $types='',array $values=[]):array {
  $s=$this->query($sql,$types,$values);try{return $s->get_result()->fetch_all(MYSQLI_ASSOC);}finally{$s->close();}
 }
 public static function number($value):int {
  if(!is_scalar($value)||!preg_match('/^\d{1,9}$/',(string)$value))throw new \InvalidArgumentException('Quantities must be whole numbers from 0 to 999,999,999.');
  return (int)$value;
 }
 public static function balance(int $before,string $kind,int $quantity):int {
  if(!in_array($kind,['receive','issue','count'],true))throw new \InvalidArgumentException('Unknown movement type.');
  if($kind!=='count'&&$quantity===0)throw new \InvalidArgumentException('Enter a quantity greater than zero.');
  $after=$kind==='count'?$quantity:($kind==='receive'?$before+$quantity:$before-$quantity);
  if($after<0||$after>999999999)throw new \InvalidArgumentException('Insufficient stock or quantity outside supported range.');return $after;
 }
 public function move(array $lines,string $kind,string $key,int $actor):bool {
  if(!preg_match('/^[a-f0-9]{64}$/',$key))throw new \InvalidArgumentException('Invalid submission reference. Refresh this page.');
  if(!$lines||count($lines)>200)throw new \InvalidArgumentException('Submit between 1 and 200 items.');
  $normalized=[];
  foreach($lines as $line){$id=self::number($line['item_id']??'');$q=self::number($line['quantity']??'');$reason=is_string($line['reason']??null)?trim($line['reason']):'';
   if(!$id||isset($normalized[$id])||$reason===''||strlen($reason)>255)throw new \InvalidArgumentException('Use distinct item IDs and a reason of 1–255 bytes per row.');
   $normalized[$id]=['item_id'=>$id,'quantity'=>$q,'reason'=>$reason];
  }
  ksort($normalized);$hash=hash('sha256',json_encode([$actor,$kind,$normalized],JSON_THROW_ON_ERROR));
  $this->db->begin_transaction();
  try {
   $s=$this->query('INSERT IGNORE INTO portal_inventory_requests(request_key,payload_hash,actor_id) VALUES(?,?,?)','ssi',[$key,$hash,$actor]);
   if(!$s->affected_rows){$old=$this->rows('SELECT payload_hash FROM portal_inventory_requests WHERE request_key=?','s',[$key])[0];if(!hash_equals($old['payload_hash'],$hash))throw new \InvalidArgumentException('This submission reference was already used for different data. Refresh the page.');$this->db->commit();return false;}
   foreach($normalized as $id=>$line){
    $row=$this->rows('SELECT item_id,qty_on_hand FROM sc_stock_items WHERE item_id=? FOR UPDATE','i',[$id])[0]??null;
    if(!$row)throw new \InvalidArgumentException('An item no longer exists.');
    $meta=$this->rows('SELECT archived FROM portal_inventory_settings WHERE item_id=?','i',[$id])[0]??[];
    if(!empty($meta['archived']))throw new \InvalidArgumentException('Unarchive the item before recording stock.');
    $before=(int)$row['qty_on_hand'];$after=self::balance($before,$kind,$line['quantity']);$delta=$after-$before;
    $this->query('UPDATE sc_stock_items SET qty_on_hand=? WHERE item_id=?','ii',[$after,$id]);
    $this->query('INSERT INTO portal_inventory_movements(request_key,item_id,actor_id,kind,quantity_before,delta,quantity_after,reason) VALUES(?,?,?,?,?,?,?,?)','siisiiis',[$key,$id,$actor,$kind,$before,$delta,$after,$line['reason']]);
   }
   $this->db->commit();return true;
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 public function catalog(array $data):void {
  $action=$data['action']??'';
  $name=is_string($data['item_name']??null)?trim($data['item_name']):'';
  $this->db->begin_transaction();
  try {
   if($action==='category'){
    $name=is_string($data['category_name']??null)?trim($data['category_name']):'';
    if($name===''||strlen($name)>100)throw new \InvalidArgumentException('Enter a category name of 1–100 bytes.');
    $category=self::number($data['category_id']??'0');
    if($category){
     if(!$this->rows('SELECT category_id FROM sc_stock_categories WHERE category_id=? FOR UPDATE','i',[$category]))throw new \InvalidArgumentException('Category not found.');
     $this->query('UPDATE sc_stock_categories SET category_name=? WHERE category_id=?','si',[$name,$category]);
    }else{$this->query('INSERT INTO sc_stock_categories(category_name) VALUES(?)','s',[$name]);}
   }else{
    $category=self::number($data['category_id']??'');$id=self::number($data['item_id']??'0');
    if($name===''||strlen($name)>100)throw new \InvalidArgumentException('Enter an item name of 1–100 bytes.');
    if(!$this->rows('SELECT category_id FROM sc_stock_categories WHERE category_id=? FOR UPDATE','i',[$category]))throw new \InvalidArgumentException('Select an existing category.');
    $prices=[];foreach(['cost_price','retail_price'] as $f){$v=$data[$f]??'';if(!is_string($v)||!preg_match('/^\d{1,7}(\.\d{1,2})?$/',$v))throw new \InvalidArgumentException('Prices must be positive decimals with at most two decimal places.');$prices[]=$v;}
    $supplier=is_string($data['supplier']??null)?trim($data['supplier']):'';
    if(strlen($supplier)>100)throw new \InvalidArgumentException('Supplier must be at most 100 bytes.');
    $reorder=self::number($data['reorder_level']??'0');$archived=isset($data['archived'])?1:0;
    if($this->rows('SELECT item_id FROM sc_stock_items WHERE category_id=? AND item_name=? AND item_id<>?','isi',[$category,$name,$id]))throw new \InvalidArgumentException('An item with this name already exists in the category.');
    if($id){
     $row=$this->rows('SELECT qty_on_hand FROM sc_stock_items WHERE item_id=? FOR UPDATE','i',[$id])[0]??null;
     if(!$row)throw new \InvalidArgumentException('Item not found.');
     if($archived&&(int)$row['qty_on_hand']!==0)throw new \InvalidArgumentException('Stock must be zero before archiving.');
     $this->query('UPDATE sc_stock_items SET item_name=?,category_id=?,cost_price=?,retail_price=?,supplier=? WHERE item_id=?','sisssi',[$name,$category,$prices[0],$prices[1],$supplier,$id]);
    }else{
     $this->query("INSERT INTO sc_stock_items(item_name,category_id,cost_price,retail_price,qty_on_hand,order_qty,received_qty,description,supplier) VALUES(?,?,?,?,0,0,0,'',?)",'sisss',[$name,$category,$prices[0],$prices[1],$supplier]);$id=(int)$this->db->insert_id;
    }
    $this->query('INSERT INTO portal_inventory_settings(item_id,reorder_level,archived) VALUES(?,?,?) ON DUPLICATE KEY UPDATE reorder_level=VALUES(reorder_level),archived=VALUES(archived)','iii',[$id,$reorder,$archived]);
   }
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
}
