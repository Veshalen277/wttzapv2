<?php
// message_actions.php
// Requires: $con, $_SESSION already available via header.php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  return;
}

if (!$con) {
  $_SESSION['msg'] = "Database connection error.";
  $_SESSION['msg_type'] = "error";
  header("Location: message_board.php");
  exit();
}

$user_id = (int)($_SESSION['u_data'][5] ?? 0);
if ($user_id <= 0) {
  $_SESSION['msg'] = "Invalid session user.";
  $_SESSION['msg_type'] = "error";
  header("Location: message_board.php");
  exit();
}

/**
 * 1) Post new message (from message_form.php likely)
 */
if (isset($_POST['message_text'])) {
  $message_text_raw = trim((string)$_POST['message_text']);

  if ($message_text_raw === '') {
    $_SESSION['msg'] = "Message cannot be empty.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  // Keep your original approach, but avoid double-encoding:
  $message_text = mysqli_real_escape_string($con, $message_text_raw);

  $sql = "INSERT INTO messages (user_id, message_text, posted_at)
          VALUES ($user_id, '$message_text', NOW())";
  $query = mysqli_query($con, $sql);

  if ($query) {
    $message_id = (int)mysqli_insert_id($con);

    // Note: this currently inserts notification for same user only (your original behavior)
    $notification_sql = "INSERT INTO notifications (user_id, message_id, is_read)
                         VALUES ($user_id, $message_id, 0)";
    mysqli_query($con, $notification_sql);

    $_SESSION['msg'] = "Message posted successfully.";
    $_SESSION['msg_type'] = "success";
  } else {
    $_SESSION['msg'] = "Error posting message: " . mysqli_error($con);
    $_SESSION['msg_type'] = "error";
  }

  header("Location: message_board.php");
  exit();
}

/**
 * 2) Bulk delete messages
 */
if (isset($_POST['delete_messages'])) {
  $message_ids = $_POST['message_ids'] ?? [];

  if (!is_array($message_ids) || empty($message_ids)) {
    $_SESSION['msg'] = "No messages selected for deletion.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  $message_ids = array_map('intval', $message_ids);
  $message_ids = array_values(array_filter($message_ids, fn($v) => $v > 0));

  if (empty($message_ids)) {
    $_SESSION['msg'] = "No valid messages selected.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  $message_ids_str = implode(',', $message_ids);

  // delete replies first
  mysqli_query($con, "DELETE FROM replies WHERE message_id IN ($message_ids_str)");

  // delete notifications linked to these messages (recommended, otherwise orphan rows)
  mysqli_query($con, "DELETE FROM notifications WHERE message_id IN ($message_ids_str)");

  // delete messages
  $delete_query = mysqli_query($con, "DELETE FROM messages WHERE id IN ($message_ids_str)");

  if ($delete_query) {
    $_SESSION['msg'] = "Selected messages deleted successfully.";
    $_SESSION['msg_type'] = "success";
  } else {
    $_SESSION['msg'] = "Error deleting messages: " . mysqli_error($con);
    $_SESSION['msg_type'] = "error";
  }

  header("Location: message_board.php");
  exit();
}

/**
 * 3) Reply submit (IMPORTANT: your UI posts submit_reply + reply_text_{id}
 * so we handle that pattern properly)
 */
if (isset($_POST['submit_reply'])) {
  $message_id = (int)$_POST['submit_reply'];
  $field = 'reply_text_' . $message_id;
  $reply_text_raw = trim((string)($_POST[$field] ?? ''));

  if ($message_id <= 0) {
    $_SESSION['msg'] = "Invalid message.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  if ($reply_text_raw === '') {
    $_SESSION['msg'] = "Reply cannot be empty.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  $reply_text = mysqli_real_escape_string($con, $reply_text_raw);

  $sql_reply = "INSERT INTO replies (message_id, user_id, reply_text, replied_at)
                VALUES ($message_id, $user_id, '$reply_text', NOW())";
  $query_reply = mysqli_query($con, $sql_reply);

  if ($query_reply) {
    $_SESSION['msg'] = "Reply posted successfully.";
    $_SESSION['msg_type'] = "success";
  } else {
    $_SESSION['msg'] = "Error posting reply: " . mysqli_error($con);
    $_SESSION['msg_type'] = "error";
  }

  header("Location: message_board.php");
  exit();
}

/**
 * 4) Delete a reply
 */
/**
 * 4) Delete a reply
 * New UI sends reply id as the value of delete_reply (no nested form, no hidden input)
 */
if (isset($_POST['delete_reply'])) {
  $reply_id = (int)$_POST['delete_reply'];

  if ($reply_id <= 0) {
    $_SESSION['msg'] = "Invalid reply.";
    $_SESSION['msg_type'] = "error";
    header("Location: message_board.php");
    exit();
  }

  $delete_query = mysqli_query($con, "DELETE FROM replies WHERE id = $reply_id");

  if ($delete_query) {
    $_SESSION['msg'] = "Reply deleted successfully.";
    $_SESSION['msg_type'] = "success";
  } else {
    $_SESSION['msg'] = "Error deleting reply: " . mysqli_error($con);
    $_SESSION['msg_type'] = "error";
  }

  header("Location: message_board.php");
  exit();
}

// If POST comes in but none matched:
return;