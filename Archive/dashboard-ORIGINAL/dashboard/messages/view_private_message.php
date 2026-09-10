<?php
// Include necessary files (header, database connection)
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

// Retrieve message details based on message_id from URL parameter
if (isset($_GET['message_id'])) {
    $message_id = $_GET['message_id'];

    // Fetch message details
    $sql_message = "SELECT pm.id, u.fullname, pm.message_text, pm.sent_at 
                    FROM private_messages pm 
                    INNER JOIN users_tbl u ON pm.sender_id = u.id 
                    WHERE pm.id = $message_id";
    $result_message = mysqli_query($con, $sql_message);
    
    if (!$result_message) {
        die("Query failed: " . mysqli_error($con));
    }
    
    $row_message = mysqli_fetch_assoc($result_message);
    
    // If message not found
    if (!$row_message) {
        header("Location: view_private_messages.php");
        exit();
    }

    // Fetch replies for this message
    $sql_replies = "SELECT pm.id, u.fullname, pm.message_text, pm.sent_at 
                    FROM private_messages pm 
                    INNER JOIN users_tbl u ON pm.sender_id = u.id 
                    WHERE pm.parent_message_id = $message_id 
                    ORDER BY pm.sent_at ASC";
    $result_replies = mysqli_query($con, $sql_replies);
} else {
    header("Location: view_private_messages.php");
    exit();
}
// Handle reply form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_text'])) {
    $reply_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['reply_text']));
    $sender_id = $_SESSION['u_data'][5]; // Get current user ID

    // Step 1: Get the receiver_id from the original message
    $receiver_query = "SELECT receiver_id FROM private_messages WHERE id = $message_id";
    $receiver_result = mysqli_query($con, $receiver_query);
    $receiver_row = mysqli_fetch_assoc($receiver_result);
    
    if ($receiver_row) {
        $receiver_id = $receiver_row['receiver_id'];

        // Step 2: Insert the reply using the fetched receiver_id
        $sql_reply = "INSERT INTO private_messages (sender_id, receiver_id, message_text, sent_at, parent_message_id) 
                      VALUES ('$sender_id', '$receiver_id', '$reply_text', NOW(), $message_id)";
        
        if (mysqli_query($con, $sql_reply)) {
            $_SESSION['msg'] = "Reply sent successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: view_private_message.php?message_id=$message_id");
            exit();
        } else {
            $_SESSION['msg'] = "Error sending reply: " . mysqli_error($con);
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['msg'] = "Error retrieving receiver ID.";
        $_SESSION['msg_type'] = "error";
    }
}


?>

<div class="container-fluid mt-3 p-5 bg-white border ">
    <div class="row">
        <div class="col-md-3 col-sm-12">
            <?php include '../inc/sidebar.php';?>
        </div>
        <div class="col-md-9 col-sm-12">
            <div class="container">
    <h3>View Private Message</h3>
    <strong>User: <?php echo htmlspecialchars($row_message['fullname']); ?></strong><br>
    <strong>Message:</strong> <?php echo htmlspecialchars($row_message['message_text']); ?><br>
    <strong>Sent At:</strong> <?php echo $row_message['sent_at']; ?>

    <h4>Replies:</h4>
    <?php if (mysqli_num_rows($result_replies) > 0) { ?>
        <ul>
            <?php while ($reply_row = mysqli_fetch_assoc($result_replies)) { ?>
                <li>
                    <strong><?php echo htmlspecialchars($reply_row['fullname']); ?></strong>: <?php echo htmlspecialchars($reply_row['message_text']); ?> <em>(<?php echo $reply_row['sent_at']; ?>)</em>
                </li>
            <?php } ?>
        </ul>
    <?php } else {
        echo "<p>No replies yet.</p>";
    } ?>

    <!-- Reply form -->
    <h4>Reply to this message:</h4>
    <form method="post" action="">
        <textarea name="reply_text" rows="4" cols="50" placeholder="Write your reply here" required></textarea><br>
        <button type="submit" class="btn btn-primary">Send Reply</button>
    </form>
</div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../footer.php';
?>
