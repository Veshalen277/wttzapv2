<?php
// /dashboard/post_react.php
require_once __DIR__ . '/header.php'; // must set $con and session

if (!isset($_SESSION['u_data'])) {
  header("Location: /dashboard/login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /dashboard/");
  exit();
}

if (!$con) {
  die("DB connection error");
}

$user_id = (int)($_SESSION['u_data'][5] ?? 0);
if ($user_id <= 0) {
  die("Invalid user");
}

$item_type = $_POST['item_type'] ?? 'post';          // 'post' | 'work'
$item_id   = (int)($_POST['item_id'] ?? 0);          // id of messages.id or work_tbl.id
$reaction  = $_POST['reaction'] ?? '';               // like | dislike | heart
$return    = $_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/');

$allowedTypes = ['post','work'];
$allowedReactions = ['like','dislike','heart'];

if (!in_array($item_type, $allowedTypes, true)) die("Bad item_type");
if ($item_id <= 0) die("Bad item_id");
if (!in_array($reaction, $allowedReactions, true)) die("Bad reaction");

// If user clicks same reaction again -> remove (toggle off)
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
  // Upsert reaction
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
exit();
<!-- </?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php'; // $con
// Optionally: check auth, user_role etc.

$logged_in_user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;

if ($logged_in_user_id <= 0) {
    die('Not logged in.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feed.php');
    exit;
}

$post_id  = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reaction = isset($_POST['reaction']) ? trim($_POST['reaction']) : '';

$allowed = ['like', 'dislike', 'heart'];

if ($post_id <= 0 || !in_array($reaction, $allowed, true)) {
    header('Location: feed.php');
    exit;
}

// Check if user already reacted to this post
$user_id = $logged_in_user_id;

$sqlCheck = "
    SELECT reaction_id, reaction_type
    FROM post_reactions
    WHERE post_id = $post_id
      AND user_id = $user_id
    LIMIT 1
";

$resCheck = mysqli_query($con, $sqlCheck);

if ($resCheck && mysqli_num_rows($resCheck) > 0) {
    $row = mysqli_fetch_assoc($resCheck);
    $reaction_id   = (int)$row['reaction_id'];
    $current_type  = $row['reaction_type'];

    if ($current_type === $reaction) {
        // Same reaction clicked again -> toggle off (remove reaction)
        $sqlDelete = "DELETE FROM post_reactions WHERE reaction_id = $reaction_id LIMIT 1";
        mysqli_query($con, $sqlDelete);
    } else {
        // Different reaction -> switch type
        $reactionEsc = mysqli_real_escape_string($con, $reaction);
        $sqlUpdate = "
            UPDATE post_reactions
            SET reaction_type = '$reactionEsc'
            WHERE reaction_id = $reaction_id
            LIMIT 1
        ";
        mysqli_query($con, $sqlUpdate);
    }
} else {
    // No reaction yet -> insert new
    $reactionEsc = mysqli_real_escape_string($con, $reaction);
    $sqlInsert = "
        INSERT INTO post_reactions (post_id, user_id, reaction_type, created_at)
        VALUES ($post_id, $user_id, '$reactionEsc', NOW())
    ";
    mysqli_query($con, $sqlInsert);
}

// return to feed for now
// header('Location: feed.php');
// exit; REDIRECTED BELOW 

// ... after you’ve processed the reaction:

$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/employee/emp_profile.php');

header('Location: ' . $redirect);
exit; -->
