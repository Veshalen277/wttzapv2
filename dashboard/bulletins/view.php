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
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div class='alert alert-danger'>Invalid bulletin ID</div>";
    include __DIR__ . '/../footer.php';
    exit;
}

$db = $con;

// Fetch bulletin by ID
$sql = "
SELECT * FROM bulletins 
WHERE id = ? AND is_active = 1
  AND (start_at IS NULL OR start_at <= NOW())
  AND (end_at IS NULL OR end_at >= NOW())
LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$bulletin = $res->fetch_assoc();

if (!$bulletin) {
    echo "<div class='alert alert-warning'>Bulletin not found or inactive.</div>";
    include __DIR__ . '/../footer.php';
    exit;
}

// Mark as read (upsert)
$mark = $db->prepare("
  INSERT INTO bulletin_reads (bulletin_id, user_id, read_at)
  VALUES (?, ?, NOW())
  ON DUPLICATE KEY UPDATE read_at = NOW()
");
$mark->bind_param('ii', $id, $user_id);
$mark->execute();
?>

<div class="container mt-4">
  <a href="index.php" class="btn btn-sm btn-secondary mb-3">&larr; Back to Bulletins</a>
  <div class="card">
    <div class="card-body">
      <h4>
        <?= htmlspecialchars($bulletin['title']) ?>
        <?php if ($bulletin['priority']): ?><span class="badge bg-danger">High</span><?php endif; ?>
        <?php if ($bulletin['sticky']): ?><span class="badge bg-warning text-dark">Pinned</span><?php endif; ?>
      </h4>
      <hr>
      <div><?= nl2br(htmlspecialchars($bulletin['body'])) ?></div>
      <hr>
      <small class="text-muted d-block">
        Sent by 
        <strong><?= htmlspecialchars($bulletin['sender_name'] ?? 'System') ?></strong><br>
        Created at <?= date('M d, Y h:i A', strtotime($bulletin['created_at'])) ?><br>
        <?php if ($bulletin['start_at']): ?>
          Active from <?= date('M d, Y h:i A', strtotime($bulletin['start_at'])) ?>
        <?php endif; ?>
        <?php if ($bulletin['end_at']): ?>
          to <?= date('M d, Y h:i A', strtotime($bulletin['end_at'])) ?>
        <?php endif; ?>
      </small>
    </div>
  </div>
</div>


<?php include __DIR__ . '/../footer.php'; ?>

