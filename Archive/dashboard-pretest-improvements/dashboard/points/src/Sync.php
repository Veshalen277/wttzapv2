<?php
namespace Participation;
final class Sync
{
 private Store $store; private array $policy; private array $users;
 public function __construct(Store $store,array $policy){$this->store=$store;$this->policy=$policy;$this->users=array_fill_keys(array_column($store->rows('SELECT id FROM users_tbl'),'id'),true);}
 private function pick(array $candidates,array $columns):?string {foreach($candidates as $c)if(in_array($c,$columns,true))return $c;return null;}
 private function identifier(string $name):string {if(!preg_match('/^[a-zA-Z0-9_]+$/',$name))throw new \RuntimeException('Unsafe source identifier');return '`'.$name.'`';}
 private function ownerEligible(string $source,array $row):bool {
  if(!in_array($source,['item_reactions','post_reactions'],true))return true;
  $type=$source==='post_reactions'?'post':($row['item_type']??'');
  $id=(int)($row['post_id']??$row['item_id']??0);
  if(!in_array($type,['post','work'],true))return false;
  $table=$type==='post'?($source==='item_reactions'?'messages':'posts'):'work_tbl';$owner=$type==='post'?'user_id':'employee_id';
  $columns=$this->store->columns($table);
  if(!in_array('id',$columns,true)||!in_array($owner,$columns,true))return false;
  $found=$this->store->rows('SELECT '.$this->identifier($owner).' owner FROM '.$this->identifier($table).' WHERE id=?','i',[$id])[0]??null;
  return $found && (int)$found['owner']!==(int)$row['_user'];
 }
 public function source(string $name,array $definition,int $run):void {
  if($definition['table']==='portal_points_actions') {
   $prefixes=['stock_change'=>'portal_points_stock','profile_update'=>'portal_points_profile','project_update'=>'portal_points_project_update','file_upload'=>'portal_points_project_file_insert'];
   $triggers=array_column($this->store->rows('SELECT TRIGGER_NAME FROM information_schema.triggers WHERE trigger_schema=DATABASE()'),'TRIGGER_NAME');
   $prefix=$prefixes[$name]??'unconfigured';
   if(!array_filter($triggers,fn($trigger)=>strpos($trigger,$prefix)===0)) { $this->status($name,$run,'not_instrumented','Run install.php --with-actions; this source has no actor/time adapter',0); return; }
  }
  if($name==='post_reactions' && $this->store->columns('item_reactions')) { $this->status($name,$run,'superseded','item_reactions is canonical; legacy reactions need review before import to avoid double credit',0); return; }
  $columns=$this->store->columns($definition['table']);$count=0;$invalid=0;$undated=0;
  if(!$columns){$this->status($name,$run,'missing','Table not installed; existing awards retained',0);return;}
  $user=$this->pick($definition['user'],$columns);$date=$this->pick($definition['date'],$columns);$body=isset($definition['body'])?$this->pick($definition['body'],$columns):null;$keys=null;
  foreach($definition['key'] as $candidate)if(!array_diff($candidate,$columns)){$keys=$candidate;break;}
  if(!$user||!$keys||(isset($definition['body'])&&!$body)||(isset($definition['filter'])&&!in_array($definition['filter'][0],$columns,true))){$this->status($name,$run,'incompatible','Required owner, identity, body or state column missing; no guessing',0);return;}
  $sql='SELECT *,'.$this->identifier($user).' _user,'.($date?$this->identifier($date):'NULL').' _date FROM '.$this->identifier($definition['table']);
  if(isset($definition['filter']))$sql.=' WHERE '.$this->identifier($definition['filter'][0]).'=?';
  $sql.=' ORDER BY '.($date?$this->identifier($date).',':'').implode(',',array_map(fn($k)=>$this->identifier($k),$keys));
  $this->store->db->begin_transaction();
  try {
   $statement=isset($definition['filter'])?$this->store->query($sql,'s',[(string)$definition['filter'][1]]):$this->store->query($sql);$result=$statement->get_result();
   while($row=$result->fetch_assoc()){
    $uid=filter_var($row['_user'],FILTER_VALIDATE_INT);
    if(!$uid||!isset($this->users[$uid])){$invalid++;continue;}
    $ref=json_encode(array_map(fn($key)=>(string)$row[$key],$keys),JSON_THROW_ON_ERROR);
    if(strlen($ref)>255){$invalid++;continue;}
    $when=Policy::date($row['_date'],$definition['timezone']??'Africa/Johannesburg');if(!$when)$undated++;
    $length=$body?mb_strlen(trim(strip_tags((string)$row[$body]))):0;
    $this->store->observe($name,$ref,$uid,$definition['rule'],$when,$length,$this->ownerEligible($name,$row),$run,$this->policy);$count++;
   }
   $result->free();$statement->close();
   // Only a COMPLETE, valid snapshot can revoke missing rows. Failed sources never revoke points.
   if(!empty($definition['reversible']) && $invalid===0){
    $events=$this->store->rows('SELECT * FROM portal_points_events WHERE source=? AND seen_run<>? AND active=1 FOR UPDATE','si',[$name,$run]);
    foreach($events as $event)$this->store->change($event,false,'Source removed or no longer qualifies');
   }
   $this->status($name,$run,($invalid||$undated)?'partial':'ok',"$invalid invalid owner/identity; $undated undated rows",$count);
   $this->store->db->commit();
  }catch(\Throwable $error){$this->store->db->rollback();error_log('Points source '.$name.': '.$error->getMessage());$this->status($name,$run,'error','Sync failed; previous awards retained. Check server logs.',$count);}
 }
 private function status(string $name,int $run,string $status,string $detail,int $count):void {
  $this->store->write('INSERT INTO portal_points_sources(source,run_id,status,detail,processed) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE run_id=VALUES(run_id),status=VALUES(status),detail=VALUES(detail),processed=VALUES(processed),checked_at=NOW()','sissi',[$name,$run,$status,$detail,$count]);
 }
}
