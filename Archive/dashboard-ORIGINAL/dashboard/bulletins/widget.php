

<?php
if (defined('BULLETIN_WIDGET_LOADED')) return;
define('BULLETIN_WIDGET_LOADED', true);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/normalize_user_session.php';
$user = normalize_user_session($_SESSION['u_data'] ?? null);
if (!$user) return;

$user_id = (int)$user['id'];
$user_role = (int)$user['user_role'];
$now = date('Y-m-d H:i:s');
$db = $con;

$sql = "
SELECT b.id, b.title, b.body, b.priority, b.created_at
FROM bulletins b
LEFT JOIN bulletin_reads br 
  ON br.bulletin_id = b.id AND br.user_id = ?
WHERE b.is_active = 1
  AND (b.start_at IS NULL OR b.start_at <= ?)
  AND (b.end_at IS NULL OR b.end_at >= ?)
  AND (
      b.target_type = 'all'
      OR (b.target_type = 'role' AND b.target_value = ?)
      OR (b.target_type = 'user' AND b.target_value = ?)
  )
ORDER BY b.priority DESC, b.created_at DESC
LIMIT 5
";
$stmt = $db->prepare($sql);
$stmt->bind_param('issii', $user_id, $now, $now, $user_role, $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<style>
.bulletin-widget {
  margin-bottom: 0;
}
.bulletin-widget .list-group-item {
  padding: 0.15rem 0.15rem;
  margin: 0;
  border: none;
  border-bottom: 0.5px solid #e9ecef;
}
.bulletin-widget .list-group-item:last-child {
  border-bottom: none;
}
.bulletin-widget .list-group-item a {
  display: block;
  line-height: 0.9;
}
.bulletin-widget .list-group-item small {
  font-size: 0.75rem;
}
</style>

<div class="bulletin-widget">
<?php if ($res->num_rows === 0): ?>
  <div class="alert alert-secondary small mb-0">No new bulletins.</div>
<?php else: ?>
  <ul class="list-group small mb-0">
    <?php while ($b = $res->fetch_assoc()): ?>
      <li class="list-group-item">
        <a href="/dashboard/bulletins/view.php?id=<?= (int)$b['id'] ?>" class="fw-semibold text-decoration-none">
          <?= htmlspecialchars($b['title']) ?>
          <?php if ($b['priority']): ?><span class="badge badge-sm bg-danger ms-1">High</span><?php endif; ?>
        </a>
        <small class="text-muted"><?= date('M d, Y g:i A', strtotime($b['created_at'])) ?></small>
      </li>
    <?php endwhile; ?>
  </ul>
<?php endif; ?>
</div>

