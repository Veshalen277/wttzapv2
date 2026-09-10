<?php
include __DIR__ . '/../header.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/normalize_user_session.php';
$user = normalize_user_session($_SESSION['u_data'] ?? null);

if (!$user) {
    echo "<div class='alert alert-danger'>You must be logged in</div>";
    include __DIR__ . '/../footer.php';
    exit;
}

$user_id = (int)$user['id'];
$user_role = (int)$user['user_role'];
$now = date('Y-m-d H:i:s');

$db = $con;

// Handle delete (only admin)
if ($user_role === 7 && isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $stmt = $db->prepare("DELETE FROM bulletins WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Bulletin deleted.</div>";
    } else {
        echo "<div class='alert alert-danger'>Delete failed: " . htmlspecialchars($db->error) . "</div>";
    }
}

// Get bulletins
$sql = "
SELECT b.*, 
       u.fullname AS sender_name,
       CASE WHEN br.read_at IS NOT NULL THEN 1 ELSE 0 END AS is_read
FROM bulletins b
LEFT JOIN users_tbl u ON u.id = b.created_by
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
ORDER BY b.sticky DESC, b.priority DESC, b.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->bind_param('issii', $user_id, $now, $now, $user_role, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
  <h3>Company Bulletins</h3>
  <hr>
  <?php while ($b = $result->fetch_assoc()): ?>
    <div class="card mb-3 <?= $b['sticky'] ? 'border-warning' : '' ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <h5 class="card-title mb-0">
            <a href="view.php?id=<?= (int)$b['id'] ?>">
              <?= htmlspecialchars($b['title']) ?>
            </a>
            <?php if ($b['priority']): ?><span class="badge bg-danger">High</span><?php endif; ?>
            <?php if ($b['sticky']): ?><span class="badge bg-warning text-dark">Pinned</span><?php endif; ?>
          </h5>
          <?php if ($user_role === 7): ?>
            <a href="?del=<?= (int)$b['id'] ?>" 
               onclick="return confirm('Delete this bulletin?')" 
               class="btn btn-sm btn-outline-danger">Delete</a>
          <?php endif; ?>
        </div>

        <p class="card-text mt-2"><?= nl2br(htmlspecialchars($b['body'])) ?></p>

        <small class="text-muted d-block">
          Posted by 
          <strong><?= htmlspecialchars($b['sender_name'] ?? 'System') ?></strong>
          on <?= date('M d, Y h:i A', strtotime($b['created_at'])) ?>
          <?= $b['start_at'] ? '| Active from ' . date('M d, Y h:i A', strtotime($b['start_at'])) : '' ?>
          <?= $b['end_at'] ? ' to ' . date('M d, Y h:i A', strtotime($b['end_at'])) : '' ?>
          <?= !empty($b['is_read']) ? ' | ✅ Read' : '' ?>
        </small>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
