<?php
include __DIR__ . '/../header.php';   // starts session, db, etc.



if ($role != 7 && $role != 5  && $role != 2 && $role != 0) {
    header("location:../dashboard/404.php");
}
if (isset($_SESSION['u_data'])) {

  $user=$_SESSION['u_data'];
}


// ---------- Helpers ----------
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

function csrf_ok($t){ return hash_equals($_SESSION['csrf'] ?? '', (string)$t); }

// ---------- Normalize user session ----------
require_once __DIR__ . '/normalize_user_session.php';
$user = normalize_user_session($_SESSION['u_data'] ?? null);

// ---------- Access control ----------
if (!$user || intval($user['user_role']) !== 7) {
  http_response_code(403);
  echo '<div class="alert alert-danger">Access denied</div>';
  include __DIR__ . '/../footer.php';
  exit;
}

// ---------- Handle form ----------
$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['__submit_bulletin'])) {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $flash = '<div class="alert alert-danger">CSRF failed</div>';
  } else {
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $target = $_POST['target_type'] ?? 'all';
    $priority = isset($_POST['priority']) ? 1 : 0;
    $sticky   = isset($_POST['sticky']) ? 1 : 0;
    $start_at = $_POST['start_at'] ?: null;
    $end_at   = $_POST['end_at']   ?: null;

    $target_value = null;
    if ($target === 'role') $target_value = trim((string)($_POST['target_value_role'] ?? ''));
    if ($target === 'dept') $target_value = trim((string)($_POST['target_value_dept'] ?? ''));
    if ($target === 'user') $target_value = trim((string)($_POST['target_value_user'] ?? ''));

    if ($title === '' || $body === '') {
      $flash = '<div class="alert alert-warning">Title and Body are required</div>';
    } else {
      try {
        // 🔧 Ensure MySQLi connection exists
        $mysqli = $GLOBALS['mysqli'] ?? $GLOBALS['con'] ?? null;
        if (!$mysqli || !($mysqli instanceof mysqli)) {
          throw new Exception('Database connection missing or invalid.');
        }

        // ✅ SQL with 9 placeholders before NOW()
        $stmt = $mysqli->prepare("
          INSERT INTO bulletins
            (title, body, target_type, target_value, priority, sticky, is_active, start_at, end_at, created_by, created_at)
          VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
          throw new Exception('Prepare failed: ' . $mysqli->error);
        }

        // ✅ 9 placeholders → 9 types
        $stmt->bind_param(
          'ssssiiisi',
          $title,          // s
          $body,           // s
          $target,         // s
          $target_value,   // s
          $priority,       // i
          $sticky,         // i
          $start_at,       // s
          $end_at,         // s
          $user['id']      // i
        );

        if (!$stmt->execute()) {
          throw new Exception('Execute failed: ' . $stmt->error);
        }

        $flash = '<div class="alert alert-success">Bulletin published successfully <a href="index.php">View Notice</a> </div>';
      } catch (Throwable $e) {
        $flash = '<div class="alert alert-danger">Error saving bulletin: ' . htmlspecialchars($e->getMessage()) . '</div>';
      }
    }
  }
}
?>

<div class="container" style="max-width:900px;margin:24px auto">
  <h2>Publish Bulletin</h2>
  <?= $flash ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="__submit_bulletin" value="1">

    <div class="mb-3">
      <label>Title</label>
      <input type="text" name="title" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Body</label>
      <textarea name="body" class="form-control" rows="6" required></textarea>
    </div>

    <div class="mb-3">
      <label>Target Audience</label><br>
      <label><input type="radio" name="target_type" value="all" checked> All Users</label><br>
      <label><input type="radio" name="target_type" value="role"> By Role</label>
      <input type="text" name="target_value_role" placeholder="Role ID"><br>
      <label><input type="radio" name="target_type" value="dept"> By Department</label>
      <input type="text" name="target_value_dept" placeholder="Dept name"><br>
      <label><input type="radio" name="target_type" value="user"> Specific User</label>
      <input type="text" name="target_value_user" placeholder="User ID">
    </div>

    <div class="mb-3">
      <label><input type="checkbox" name="priority" value="1"> High Priority</label>
      &nbsp;&nbsp;
      <label><input type="checkbox" name="sticky" value="1"> Pinned</label>
    </div>

    <div class="mb-3">
      <label>Schedule</label><br>
      <input type="datetime-local" name="start_at"> Start
      &nbsp;&nbsp;
      <input type="datetime-local" name="end_at"> End
    </div>

    <button class="btn btn-primary" type="submit">Publish</button>
  </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
