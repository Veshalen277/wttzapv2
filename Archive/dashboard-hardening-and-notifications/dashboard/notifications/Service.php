<?php
namespace PortalNotifications;
final class Service
{
 private \mysqli $db;
 public function __construct(\mysqli $db){$this->db=$db;}
 private function statement(string $sql,string $types='',array $args=[]):\mysqli_stmt{$s=$this->db->prepare($sql);if($types!=='')$s->bind_param($types,...$args);$s->execute();return $s;}
 public function generatePoints():int {
  $this->db->begin_transaction();
  try{
   $s=$this->statement("SELECT cursor_id FROM portal_notification_state WHERE name='points' FOR UPDATE");$state=$s->get_result()->fetch_assoc();$s->close();if(!$state)throw new \RuntimeException('Notification baseline not installed.');
   $s=$this->statement("SELECT l.id,l.user_id,l.delta,e.kind FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id WHERE l.id>? AND l.delta<>0 AND NOT EXISTS (SELECT 1 FROM portal_notifications n WHERE n.user_id=l.user_id AND n.source='points' AND n.source_ref=CAST(l.id AS CHAR)) ORDER BY l.id LIMIT 1000","i",[(int)$state['cursor_id']]);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
   $rules=require __DIR__.'/../points/rules.php';$cursor=(int)$state['cursor_id'];$count=0;
   foreach($rows as $row){$cursor=(int)$row['id'];$delta=(int)$row['delta'];if($delta===0)continue;
    $label=$rules['rules'][$row['kind']]['label']??'Participation';
    $title=$delta>0?'You earned '.$delta.' '.($delta===1?'point':'points'):'Your points were adjusted';
    $body=$label.': '.($delta>0?'+':'').$delta.' points. Open your points history to see the recorded change.';
    $ref=(string)$cursor;$user=(int)$row['user_id'];
    $s=$this->statement("INSERT IGNORE INTO portal_notifications(user_id,source,source_ref,title,body,target) VALUES(?,'points',?,?,?,'/dashboard/points/index.php')",'isss',[$user,$ref,$title,$body]);$count+=$s->affected_rows;$s->close();
   }
   // Baseline stays fixed: late commits with lower IDs are still picked up.
   $this->db->commit();return $count;
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 public function unread(int $user):int{$s=$this->statement('SELECT COUNT(*) n FROM portal_notifications WHERE user_id=? AND read_at IS NULL','i',[$user]);try{return (int)$s->get_result()->fetch_assoc()['n'];}finally{$s->close();}}
}
