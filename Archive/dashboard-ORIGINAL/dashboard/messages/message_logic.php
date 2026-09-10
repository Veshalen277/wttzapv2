<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message_text'])) {
    if (!$con) {
        $_SESSION['msg'] = "Database connection error.";
        $_SESSION['msg_type'] = "error";
    } else {
        $message_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['message_text']));
        $user_id = $_SESSION['u_data'][5]; // User ID

        // Insert the new message
        $sql = "INSERT INTO messages (user_id, message_text, posted_at) VALUES ('$user_id', '$message_text', NOW())";
        $query = mysqli_query($con, $sql);

        if ($query) {
            // Get the ID of the newly inserted message
            $message_id = mysqli_insert_id($con);

            // Insert a notification for the new message
            $notification_sql = "INSERT INTO notifications (user_id, message_id, is_read) VALUES ('$user_id', '$message_id', FALSE)";
            mysqli_query($con, $notification_sql);

            $_SESSION['msg'] = "Message posted successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: message_board.php");
            exit();
        } else {
            $_SESSION['msg'] = "Error posting message: " . mysqli_error($con);
            $_SESSION['msg_type'] = "error";
        }
    }
}
// Handle bulk message deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_messages'])) {
    $message_ids = $_POST['message_ids']; // Array of message IDs to delete

    if (empty($message_ids)) {
        $_SESSION['msg'] = "No messages selected for deletion.";
        $_SESSION['msg_type'] = "error";
    } else {
        $message_ids = array_map('intval', $message_ids); // Sanitize the IDs as integers
        $message_ids_str = implode(',', $message_ids); // Create a comma-separated string of IDs for SQL

        // First delete the replies associated with these messages
        $sql_delete_replies = "DELETE FROM replies WHERE message_id IN ($message_ids_str)";
        mysqli_query($con, $sql_delete_replies); // Execute the delete query for replies

        // Now delete the messages
        $sql_delete = "DELETE FROM messages WHERE id IN ($message_ids_str)";
        $delete_query = mysqli_query($con, $sql_delete);

        if ($delete_query) {
            $_SESSION['msg'] = "Selected messages deleted successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: message_board.php");
            exit();
        } else {
            $_SESSION['msg'] = "Error deleting messages: " . mysqli_error($con);
            $_SESSION['msg_type'] = "error";
        }
    }
}
// Handle replying to messages
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_text'])) {
    $reply_text = mysqli_real_escape_string($con, htmlspecialchars(trim($_POST['reply_text'])));
    $message_id = $_POST['message_id'];
    $user_id = $_SESSION['u_data'][5]; // Assuming 'id' is the user ID in $_SESSION['u_data']

    // Check if the reply text is empty
    if (empty($reply_text)) {
        $_SESSION['msg'] = "Reply cannot be empty.";
        $_SESSION['msg_type'] = "error";
        header("Location: message_board.php");
        exit();
    }

    $sql_reply = "INSERT INTO replies (message_id, user_id, reply_text) VALUES ('$message_id', '$user_id', '$reply_text')";
    $query_reply = mysqli_query($con, $sql_reply);

    if ($query_reply) {
        $_SESSION['msg'] = "Reply posted successfully.";
        $_SESSION['msg_type'] = "success";
        header("Location: message_board.php");
        exit();
    } else {
        $_SESSION['msg'] = "Error posting reply: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
}
// Handle deleting replies
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_reply'])) {
    $reply_id = intval($_POST['reply_id']); // Sanitize the ID

    $sql_delete_reply = "DELETE FROM replies WHERE id = $reply_id";
    $delete_query = mysqli_query($con, $sql_delete_reply);

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
// Pagination configuration
$limit = 10; // Number of messages per page
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page, default is 1
$start = ($page - 1) * $limit; // Calculate the starting point for the query
// Display messages from the database with pagination
$sql = "SELECT messages.id, users_tbl.fullname, users_tbl.user_scale, messages.message_text, messages.posted_at 
        FROM messages 
        INNER JOIN users_tbl ON messages.user_id = users_tbl.id 
        ORDER BY messages.posted_at DESC 
        LIMIT $start, $limit";
$result = mysqli_query($con, $sql);
// Count total messages for pagination
$sql_count = "SELECT COUNT(id) AS total FROM messages";
$result_count = mysqli_query($con, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_pages = ceil($row_count['total'] / $limit);
?>