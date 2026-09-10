<?php
namespace WorkplaceForum;
final class Repository
{
    private \mysqli $db;
    private int $userId;
    private bool $moderator;
    public function __construct(\mysqli $db, int $userId, bool $moderator)
    { $this->db=$db; $this->userId=$userId; $this->moderator=$moderator; }
    private function run(string $sql, string $types='', array $values=[]): \mysqli_stmt
    {
        $stmt=$this->db->prepare($sql);
        if (!$stmt) throw new \RuntimeException('Forum query could not be prepared.');
        try {
            if ($types !== '') $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) throw new \RuntimeException('Forum query failed.');
            return $stmt;
        } catch (\Throwable $error) { $stmt->close(); throw $error; }
    }
    private function rows(string $sql,string $types='',array $values=[]): array
    { $stmt=$this->run($sql,$types,$values); try { return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); } finally { $stmt->close(); } }
    private function write(string $sql,string $types='',array $values=[]): void
    { $stmt=$this->run($sql,$types,$values); $stmt->close(); }
    public function installed(): bool
    {
        $rows=$this->rows("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('portal_forum_topics','portal_forum_posts','portal_forum_members')");
        return (int)$rows[0]['n'] === 3;
    }
    private function visibility(): string
    {
        // Integer values come only from the authenticated session, never the request.
        $id=$this->userId;
        return $this->moderator ? '1=1' : "(t.visibility='open' OR t.author_id=$id OR EXISTS(SELECT 1 FROM portal_forum_members m WHERE m.topic_id=t.id AND m.user_id=$id))";
    }
    public function feed(string $search,string $filter,int $page,int $size): array
    {
        $where=$this->visibility().' AND t.is_archived=?';
        $types='i'; $values=[$filter==='archived'?1:0];
        if ($filter==='mine') { $where.=' AND t.author_id=?'; $types.='i'; $values[]=$this->userId; }
        if ($search!=='') { $where.=' AND (LOCATE(?,t.title)>0 OR LOCATE(?,t.body)>0)'; $types.='ss'; $values[]=$search; $values[]=$search; }
        $total=(int)$this->rows("SELECT COUNT(*) n FROM portal_forum_topics t WHERE $where",$types,$values)[0]['n'];
        $pages=max(1,(int)ceil($total/$size)); $page=min(max(1,$page),$pages);
        $items=$this->rows("SELECT t.*,COALESCE(u.fullname,'Former user') author_name,(SELECT COUNT(*) FROM portal_forum_posts p WHERE p.topic_id=t.id AND p.is_deleted=0) reply_count FROM portal_forum_topics t LEFT JOIN users_tbl u ON u.id=t.author_id WHERE $where ORDER BY t.updated_at DESC,t.id DESC LIMIT ? OFFSET ?",$types.'ii',array_merge($values,[$size,($page-1)*$size]));
        return compact('items','total','pages','page');
    }
    public function topic(int $id): ?array
    {
        $rows=$this->rows("SELECT t.*,COALESCE(u.fullname,'Former user') author_name FROM portal_forum_topics t LEFT JOIN users_tbl u ON u.id=t.author_id WHERE t.id=? AND ".$this->visibility(),'i',[$id]);
        return $rows[0]??null;
    }
    public function replies(int $topicId,int $page,int $size): array
    {
        $total=(int)$this->rows('SELECT COUNT(*) n FROM portal_forum_posts WHERE topic_id=? AND is_deleted=0','i',[$topicId])[0]['n'];
        $pages=max(1,(int)ceil($total/$size));$page=min(max(1,$page),$pages);
        $items=$this->rows("SELECT p.*,COALESCE(u.fullname,'Former user') author_name FROM portal_forum_posts p LEFT JOIN users_tbl u ON u.id=p.author_id WHERE p.topic_id=? AND p.is_deleted=0 ORDER BY p.id LIMIT ? OFFSET ?",'iii',[$topicId,$size,($page-1)*$size]);
        return compact('items','total','pages','page');
    }
    public function create(string $title,string $body,string $visibility): int
    {
        $this->write('INSERT INTO portal_forum_topics(author_id,title,body,visibility) VALUES(?,?,?,?)','isss',[$this->userId,$title,$body,$visibility]);
        return (int)$this->db->insert_id;
    }
    // Mutations lock the topic and re-check access inside the transaction.
    public function mutate(int $id,string $action,array $input,array $allowedRoles): void
    {
        $this->db->begin_transaction();
        try {
            $topic=$this->rows('SELECT * FROM portal_forum_topics WHERE id=? FOR UPDATE','i',[$id])[0]??null;
            $member=(bool)$this->rows('SELECT user_id FROM portal_forum_members WHERE topic_id=? AND user_id=?','ii',[$id,$this->userId]);
            if (!$topic || !Policy::canView($topic,$this->userId,$this->moderator,$member)) throw new \DomainException('Discussion unavailable.');
            $manage=Policy::canManage($topic,$this->userId,$this->moderator);
            if ($action==='reply') {
                if (!Policy::canReply($topic,true)) throw new \DomainException('This discussion is closed to replies.');
                $body=Input::text($input,'body',10000);
                $this->write('INSERT INTO portal_forum_posts(topic_id,author_id,body) VALUES(?,?,?)','iis',[$id,$this->userId,$body]);
            } elseif ($action==='remove_reply') {
                $postId=Input::id($input['post_id']??null);
                $post=$this->rows('SELECT author_id FROM portal_forum_posts WHERE id=? AND topic_id=?','ii',[$postId,$id])[0]??null;
                if (!$post || (!$this->moderator && (int)$post['author_id']!==$this->userId)) throw new \DomainException('You cannot remove this reply.');
                $this->write('UPDATE portal_forum_posts SET is_deleted=1 WHERE id=? AND topic_id=?','ii',[$postId,$id]);
            } else {
                if (!$manage) throw new \DomainException('Only the discussion owner or a moderator can do that.');
                switch ($action) {
                    case 'edit':
                        $this->write('UPDATE portal_forum_topics SET title=?,body=? WHERE id=?','ssi',[Input::text($input,'title',180),Input::text($input,'body',10000),$id]); break;
                    case 'lock': case 'unlock':
                        $this->write('UPDATE portal_forum_topics SET is_locked=? WHERE id=?','ii',[$action==='lock'?1:0,$id]); break;
                    case 'archive': case 'restore':
                        $this->write('UPDATE portal_forum_topics SET is_archived=? WHERE id=?','ii',[$action==='archive'?1:0,$id]); break;
                    case 'add_member':
                        if ($topic['visibility']!=='private') throw new \DomainException('Open discussions do not need members.');
                        $userId=Input::id($input['user_id']??null);
                        $user=$this->rows('SELECT user_role FROM users_tbl WHERE id=?','i',[$userId])[0]??null;
                        if (!$user || !in_array((int)$user['user_role'],$allowedRoles,true)) throw new \DomainException('That user does not have forum access.');
                        $this->write('INSERT INTO portal_forum_members(topic_id,user_id,added_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)','iii',[$id,$userId,$this->userId]); break;
                    case 'remove_member':
                        $this->write('DELETE FROM portal_forum_members WHERE topic_id=? AND user_id=?','ii',[$id,Input::id($input['user_id']??null)]); break;
                    default: throw new \InvalidArgumentException('Unknown forum action.');
                }
            }
            $this->write('UPDATE portal_forum_topics SET updated_at=NOW() WHERE id=?','i',[$id]);
            $this->db->commit();
        } catch (\Throwable $error) { $this->db->rollback(); throw $error; }
    }
    public function members(int $id): array
    { return $this->rows("SELECT m.user_id,COALESCE(u.fullname,'Former user') fullname FROM portal_forum_members m LEFT JOIN users_tbl u ON u.id=m.user_id WHERE m.topic_id=? ORDER BY fullname",'i',[$id]); }
    public function candidates(array $roles): array
    {
        $marks=implode(',',array_fill(0,count($roles),'?'));
        return $roles ? $this->rows("SELECT id,fullname FROM users_tbl WHERE user_role IN ($marks) ORDER BY fullname",str_repeat('i',count($roles)),$roles) : [];
    }
}
