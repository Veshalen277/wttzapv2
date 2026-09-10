<?php
// Start or resume session
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$con) {
        $_SESSION['msg'] = "Database connection error.";
        $_SESSION['msg_type'] = "error";
    } else {
        $sender_id = $_SESSION['u_data'][5];
        $parent_message_id = mysqli_real_escape_string($con, $_POST['parent_message_id']);
        $reply_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['reply_text']));
        
        $sql = "INSERT INTO private_messages (sender_id, receiver_id, message_text, sent_at, parent_message_id) 
                SELECT '$sender_id', receiver_id, '$reply_text', NOW(), '$parent_message_id'
                FROM private_messages
                WHERE id = '$parent_message_id'";
        $query = mysqli_query($con, $sql);
        
        if ($query) {
            $_SESSION['msg'] = "Reply sent successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Error sending reply: " . mysqli_error($con);
            $_SESSION['msg_type'] = "error";
        }
        
        header("Location: view_private_messages.php");
        exit();
    }
}
?>
