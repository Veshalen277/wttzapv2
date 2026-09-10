<?php
include '../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$sender_id = (int)$_SESSION['u_data'][5];

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message_text = trim($_POST['message_text'] ?? '');

if ($receiver_id <= 0) {
    $_SESSION['msg'] = "Please select a recipient.";
    $_SESSION['msg_type'] = "error";
    header("Location: private_messages.php");
    exit();
}

if ($receiver_id === $sender_id) {
    $_SESSION['msg'] = "You cannot message yourself.";
    $_SESSION['msg_type'] = "error";
    header("Location: private_messages.php");
    exit();
}

if ($message_text === '') {
    $_SESSION['msg'] = "Message cannot be empty.";
    $_SESSION['msg_type'] = "error";
    header("Location: private_messages.php");
    exit();
}

// Verify recipient exists
$chk = mysqli_prepare($con, "SELECT id FROM users_tbl WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($chk, "i", $receiver_id);
mysqli_stmt_execute($chk);
$chkRes = mysqli_stmt_get_result($chk);
$exists = mysqli_fetch_assoc($chkRes);
mysqli_stmt_close($chk);

if (!$exists) {
    $_SESSION['msg'] = "Recipient not found.";
    $_SESSION['msg_type'] = "error";
    header("Location: private_messages.php");
    exit();
}

// Insert message safely
// $stmt = mysqli_prepare($con, "
//     INSERT INTO private_messages (sender_id, receiver_id, message_text, sent_at)
//     VALUES (?, ?, ?, NOW())
// ");
// mysqli_stmt_bind_param($stmt, "iis", $sender_id, $receiver_id, $message_text);
$parent_id = isset($_POST['parent_message_id']) ? (int)$_POST['parent_message_id'] : 0;

$stmt = mysqli_prepare($con, "
  INSERT INTO private_messages (sender_id, receiver_id, message_text, parent_message_id, sent_at)
  VALUES (?, ?, ?, ?, NOW())
");
mysqli_stmt_bind_param($stmt, "iisi", $sender_id, $receiver_id, $message_text, $parent_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    $_SESSION['msg'] = "Message sent successfully.";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['msg'] = "Error sending message.";
    $_SESSION['msg_type'] = "error";
}

header("Location: private_messages.php");
exit();
}
?>
