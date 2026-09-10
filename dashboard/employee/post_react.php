<?php
require_once __DIR__ . '/../header.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function ajax_fail(bool $isAjax, string $msg, int $code = 400, string $redirect = ''): void {
  if ($isAjax) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
  }
  if ($redirect) {
    header("Location: $redirect");
    exit();
  }
  exit();
}

if (!isset($_SESSION['u_data'])) {
  ajax_fail($isAjax, 'Not logged in', 401, '/dashboard/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  ajax_fail($isAjax, 'Bad method', 405, '/dashboard/employee/employee_wall.php');
}

if (!isset($con) || !$con) {
  ajax_fail($isAjax, 'DB connection error', 500);
}

$user_id   = (int)($_SESSION['u_data'][5] ?? 0);
$item_type = (string)($_POST['item_type'] ?? '');
$item_id   = (int)($_POST['item_id'] ?? 0);
$reaction  = (string)($_POST['reaction'] ?? '');
$return    = (string)($_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/employee/employee_wall.php'));

$allowedTypes = ['post','work'];
$allowedReactions = ['like','dislike','heart'];

if ($user_id <= 0) ajax_fail($isAjax, 'Bad user', 400);
if (!in_array($item_type, $allowedTypes, true)) ajax_fail($isAjax, 'Bad item_type', 400);
if ($item_id <= 0) ajax_fail($isAjax, 'Bad item_id', 400);
if (!in_array($reaction, $allowedReactions, true)) ajax_fail($isAjax, 'Bad reaction', 400);

// --- toggle / upsert
$stmt = mysqli_prepare($con, "
  SELECT reaction_type
  FROM item_reactions
  WHERE item_type = ? AND item_id = ? AND user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "sii", $item_type, $item_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if ($existing && ($existing['reaction_type'] === $reaction)) {
  $del = mysqli_prepare($con, "
    DELETE FROM item_reactions
    WHERE item_type = ? AND item_id = ? AND user_id = ?
  ");
  mysqli_stmt_bind_param($del, "sii", $item_type, $item_id, $user_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);
} else {
  $ins = mysqli_prepare($con, "
    INSERT INTO item_reactions (item_type, item_id, user_id, reaction_type, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = NOW()
  ");
  mysqli_stmt_bind_param($ins, "siis", $item_type, $item_id, $user_id, $reaction);
  mysqli_stmt_execute($ins);
  mysqli_stmt_close($ins);
}

// --- AJAX: return JSON and STOP (no redirect)
if ($isAjax) {
  $counts = ['like'=>0,'dislike'=>0,'heart'=>0];
  $userReaction = null;

  $stmt = mysqli_prepare($con, "
    SELECT reaction_type, COUNT(*) cnt
    FROM item_reactions
    WHERE item_type = ? AND item_id = ?
    GROUP BY reaction_type
  ");
  mysqli_stmt_bind_param($stmt, "si", $item_type, $item_id);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($r)) {
    $rt = (string)$row['reaction_type'];
    if (isset($counts[$rt])) $counts[$rt] = (int)$row['cnt'];
  }
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare($con, "
    SELECT reaction_type
    FROM item_reactions
    WHERE item_type = ? AND item_id = ? AND user_id = ?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($stmt, "sii", $item_type, $item_id, $user_id);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  $row = $r ? mysqli_fetch_assoc($r) : null;
  $userReaction = $row['reaction_type'] ?? null;
  mysqli_stmt_close($stmt);

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok' => true,
    'item_type' => $item_type,
    'item_id' => $item_id,
    'user_reaction' => $userReaction,
    'counts' => $counts
  ]);
  exit();
}

// --- normal POST fallback
header("Location: " . $return);
exit();
?>
<!-- <// wORKING - DISPLAYS AND ERROR, BUT WORKS WHEN PAGE IS REFRESEHD ?php
require_once __DIR__ . '/../header.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function ajax_fail(bool $isAjax, string $msg, int $code = 400, string $redirect = ''): void {
  if ($isAjax) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
  }
  if ($redirect) header("Location: $redirect");
  exit();
}

if (!isset($_SESSION['u_data'])) {
  ajax_fail($isAjax, 'Not logged in', 401, '/dashboard/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  ajax_fail($isAjax, 'Bad method', 405, '/dashboard/employee/employee_wall.php');
}

if (!isset($con) || !$con) {
  ajax_fail($isAjax, 'DB connection error', 500);
}

$user_id   = (int)($_SESSION['u_data'][5] ?? 0);
$item_type = (string)($_POST['item_type'] ?? '');
$item_id   = (int)($_POST['item_id'] ?? 0);
$reaction  = (string)($_POST['reaction'] ?? '');
$return    = (string)($_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/employee/employee_wall.php'));

$allowedTypes = ['post','work'];
$allowedReactions = ['like','dislike','heart'];

if ($user_id <= 0) die("Bad user");
if (!in_array($item_type, $allowedTypes, true)) die("Bad item_type");
if ($item_id <= 0) die("Bad item_id");
if (!in_array($reaction, $allowedReactions, true)) die("Bad reaction");

// toggle / upsert
$stmt = mysqli_prepare($con, "
  SELECT reaction_type
  FROM item_reactions
  WHERE item_type = ? AND item_id = ? AND user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "sii", $item_type, $item_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if ($existing && ($existing['reaction_type'] === $reaction)) {
  $del = mysqli_prepare($con, "
    DELETE FROM item_reactions
    WHERE item_type = ? AND item_id = ? AND user_id = ?
  ");
  mysqli_stmt_bind_param($del, "sii", $item_type, $item_id, $user_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);
} else {
  $ins = mysqli_prepare($con, "
    INSERT INTO item_reactions (item_type, item_id, user_id, reaction_type, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = NOW()
  ");
  mysqli_stmt_bind_param($ins, "siis", $item_type, $item_id, $user_id, $reaction);
  mysqli_stmt_execute($ins);
  mysqli_stmt_close($ins);
}

// --------- AJAX response (no refresh) ----------
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
  $counts = ['like'=>0,'dislike'=>0,'heart'=>0];
  $userReaction = null;

  // counts
  $stmt = mysqli_prepare($con, "
    SELECT reaction_type, COUNT(*) cnt
    FROM item_reactions
    WHERE item_type = ? AND item_id = ?
    GROUP BY reaction_type
  ");
  mysqli_stmt_bind_param($stmt, "si", $item_type, $item_id);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($r)) {
    $rt = (string)$row['reaction_type'];
    if (isset($counts[$rt])) $counts[$rt] = (int)$row['cnt'];
  }
  mysqli_stmt_close($stmt);

  // current user reaction
  $stmt = mysqli_prepare($con, "
    SELECT reaction_type
    FROM item_reactions
    WHERE item_type = ? AND item_id = ? AND user_id = ?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($stmt, "sii", $item_type, $item_id, $user_id);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  $row = $r ? mysqli_fetch_assoc($r) : null;
  $userReaction = $row['reaction_type'] ?? null;
  mysqli_stmt_close($stmt);

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok' => true,
    'item_type' => $item_type,
    'item_id' => $item_id,
    'user_reaction' => $userReaction,
    'counts' => $counts
  ]);
  exit();
}

// fallback for normal POST
header("Location: " . $return);
exit();

?> -->

<!-- THIS IS THE VERSION WITHOUT AJAX 
 
</?php
// /dashboard/employee/post_react.php - cake ! This is a wrapper dont touch 
//require_once __DIR__ . '/../post_react.php';



require_once __DIR__ . '/../header.php';

if (!isset($_SESSION['u_data'])) {
  header("Location: /dashboard/login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /dashboard/employee/employee_wall.php");
  exit();
}

$user_id  = (int)($_SESSION['u_data'][5] ?? 0);
$item_type = $_POST['item_type'] ?? '';
$item_id   = (int)($_POST['item_id'] ?? 0);
$reaction  = $_POST['reaction'] ?? '';
$return    = $_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/employee/employee_wall.php');

$allowedTypes = ['post','work'];
$allowedReactions = ['like','dislike','heart'];

if ($user_id <= 0) die("Bad user");
if (!in_array($item_type, $allowedTypes, true)) die("Bad item_type");
if ($item_id <= 0) die("Bad item_id");
if (!in_array($reaction, $allowedReactions, true)) die("Bad reaction");

// toggle / upsert
$stmt = mysqli_prepare($con, "
  SELECT reaction_type
  FROM item_reactions
  WHERE item_type = ? AND item_id = ? AND user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "sii", $item_type, $item_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if ($existing && $existing['reaction_type'] === $reaction) {
  $del = mysqli_prepare($con, "
    DELETE FROM item_reactions
    WHERE item_type = ? AND item_id = ? AND user_id = ?
  ");
  mysqli_stmt_bind_param($del, "sii", $item_type, $item_id, $user_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);
} else {
  $ins = mysqli_prepare($con, "
    INSERT INTO item_reactions (item_type, item_id, user_id, reaction_type, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = NOW()
  ");
  mysqli_stmt_bind_param($ins, "siis", $item_type, $item_id, $user_id, $reaction);
  mysqli_stmt_execute($ins);
  mysqli_stmt_close($ins);
}

header("Location: " . $return);
exit(); -->