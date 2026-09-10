<?php
namespace Participation;
final class Store
{
 public \mysqli $db;
 private array $columnCache=[];
 public function __construct(\mysqli $db){$this->db=$db;}
 public function query(string $sql,string $types='',array $values=[]): \mysqli_stmt {
  $s=$this->db->prepare($sql);if(!$s)throw new \RuntimeException('Points query could not be prepared.');
  try {if($types!=='')$s->bind_param($types,...$values);if(!$s->execute())throw new \RuntimeException('Points query failed.');return $s;}catch(\Throwable $e){$s->close();throw $e;}
 }
 public function rows(string $sql,string $types='',array $values=[]):array {$s=$this->query($sql,$types,$values);try{return $s->get_result()->fetch_all(MYSQLI_ASSOC);}finally{$s->close();}}
 public function write(string $sql,string $types='',array $values=[]):int {$s=$this->query($sql,$types,$values);$id=(int)$s->insert_id;$s->close();return $id;}
 public function columns(string $table):array {return $this->columnCache[$table] ??= array_column($this->rows('SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=?','s',[$table]),'COLUMN_NAME');}
 public function installed():bool {return count($this->columns('portal_points_ledger'))>=7;}
 public function observe(string $source,string $ref,int $user,string $kind,?string $date,int $length,bool $eligible,int $run,array $policy):void {
  $key=hash('sha256',json_encode([$source,$ref,$user],JSON_THROW_ON_ERROR));
  $existing=$this->rows('SELECT * FROM portal_points_events WHERE event_key=? FOR UPDATE','s',[$key])[0]??null;
  if($existing){
   // Repair earlier zero awards once reaction ownership can be verified.
   if ($eligible && $date && (int)$existing['awarded']===0 &&
       strpos($existing['reason'],'Ineligible:')===0) {
    $day=substr($existing['occurred_at'] ?: $date,0,10);
    $used=(int)$this->rows('SELECT COALESCE(SUM(awarded),0) n FROM portal_points_events WHERE user_id=? AND kind=? AND occurred_at>=? AND occurred_at<DATE_ADD(?,INTERVAL 1 DAY)','isss',[$user,$kind,$day,$day])[0]['n'];
    [$award,$reason]=Policy::award($policy['rules'][$kind],$used,$date,$length,true);
    $revision=(int)$existing['revision']+1;$when=$existing['occurred_at'] ?: $date;
    $this->write('UPDATE portal_points_events SET awarded=?,active=1,revision=?,reason=?,seen_run=?,occurred_at=? WHERE id=?','iisisi',[$award,$revision,$reason,$run,$when,$existing['id']]);
    $this->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,?,?,?,?,?)','iiiiss',[$existing['id'],$revision,$user,$award,$when,'Eligibility resolved: '.$reason]);
    return;
   }
   // A genuinely missing timestamp may become available later (for example first observed completion).
   if(!$existing['occurred_at'] && $date){
    $day=substr($date,0,10);
    $used=(int)$this->rows('SELECT COALESCE(SUM(awarded),0) n FROM portal_points_events WHERE user_id=? AND kind=? AND occurred_at>=? AND occurred_at<DATE_ADD(?,INTERVAL 1 DAY)','isss',[$user,$kind,$day,$day])[0]['n'];
    [$award,$reason]=Policy::award($policy['rules'][$kind],$used,$date,$length,$eligible);
    $revision=(int)$existing['revision']+1;
    $this->write('UPDATE portal_points_events SET occurred_at=?,awarded=?,active=1,revision=?,rule_version=?,reason=?,seen_run=? WHERE id=?','siiisii',[$date,$award,$revision,$policy['version'],$reason,$run,$existing['id']]);
    $this->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,?,?,?,?,?)','iiiiss',[$existing['id'],$revision,$user,$award,$date,'Timestamp resolved: '.$reason]);
    return;
   }
   $this->write('UPDATE portal_points_events SET seen_run=? WHERE id=?','ii',[$run,$existing['id']]);
   // Existing reactions can become self-reactions when ownership changes.
   // Retain the original budget reservation to prevent delete/restore farming.
   if (!$eligible) {
    if ((int)$existing['active']) $this->change($existing,false,'Source no longer eligible');
   } elseif (!(int)$existing['active']) {
    $this->change($existing,true,'Source restored; original award restored');
   }
   return;
  }
  $used=0;
  if($date){$day=substr($date,0,10);$used=(int)$this->rows('SELECT COALESCE(SUM(awarded),0) n FROM portal_points_events WHERE user_id=? AND kind=? AND occurred_at>=? AND occurred_at<DATE_ADD(?,INTERVAL 1 DAY)','isss',[$user,$kind,$day,$day])[0]['n'];}
  [$award,$reason]=Policy::award($policy['rules'][$kind],$used,$date,$length,$eligible);
  $id=$this->write('INSERT INTO portal_points_events(event_key,source,source_ref,user_id,kind,occurred_at,awarded,rule_version,reason,seen_run) VALUES(?,?,?,?,?,?,?,?,?,?)','sssissiisi',[$key,$source,$ref,$user,$kind,$date,$award,$policy['version'],$reason,$run]);
  $this->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,1,?,?,?,?)','iiiss',[$id,$user,$award,$date,$reason]);
 }
 public function change(array $event,bool $active,string $reason):void {
  $revision=(int)$event['revision']+1;$delta=(int)$event['awarded']*($active?1:-1);
  $this->write('UPDATE portal_points_events SET active=?,revision=? WHERE id=?','iii',[$active?1:0,$revision,$event['id']]);
  $this->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,?,?,?,?,?)','iiiiss',[$event['id'],$revision,$event['user_id'],$delta,$event['occurred_at'],$reason]);
 }
}
