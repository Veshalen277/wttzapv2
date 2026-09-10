<?php
// Include necessary files (header, database connection)
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Handle form submission for replying to messages
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_text'])) {
    $reply_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['reply_text']));
    $message_id = $_POST['message_id'];
    $user_id = $_SESSION['u_data'][5]; // Assuming 'id' is the user ID in $_SESSION['u_data']

    $sql_reply = "INSERT INTO replies (message_id, user_id, reply_text) VALUES ('$message_id', '$user_id', '$reply_text')";
    $query_reply = mysqli_query($con, $sql_reply);

    if ($query_reply) {
        $_SESSION['msg'] = "Reply posted successfully.";
        $_SESSION['msg_type'] = "success";
        // Redirect back to this message page after replying
        header("Location: view_message.php?message_id=$message_id");
        exit();
    } else {
        $_SESSION['msg'] = "Error posting reply: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
}

// Retrieve message details based on message_id from URL parameter
if (isset($_GET['message_id'])) {
    $message_id = $_GET['message_id'];

    // Fetch message details
    $sql_message = "SELECT messages.id, users_tbl.fullname, users_tbl.user_scale, messages.message_text, messages.posted_at 
                    FROM messages 
                    INNER JOIN users_tbl ON messages.user_id = users_tbl.id 
                    WHERE messages.id = $message_id";
    $result_message = mysqli_query($con, $sql_message);
    $row_message = mysqli_fetch_assoc($result_message);

    // Fetch replies for this message
    $sql_replies = "SELECT replies.id, users_tbl.fullname, replies.reply_text, replies.replied_at 
                    FROM replies 
                    INNER JOIN users_tbl ON replies.user_id = users_tbl.id 
                    WHERE replies.message_id = $message_id 
                    ORDER BY replies.replied_at ASC";
    $result_replies = mysqli_query($con, $sql_replies);
} else {
    // Redirect to message board if message_id is not provided
    header("Location: message_board.php");
    exit();
}

?>

<div class="container-fluid mt-3 p-5 bg-white border ">
    <div class="col-md-10 m-auto emp_profile p-4 border border-secondary p-2">
        <h3>View Message</h3>

        <!-- Display message details -->
        <div class="row m-2 p-3 border border-secondary">
            <strong>User: <?php echo $row_message['fullname']; ?></strong><br>
            <strong>Message:</strong> <?php echo $row_message['message_text']; ?><br>
            <strong>Posted At:</strong> <?php echo $row_message['posted_at']; ?><br>
            <strong>Department:</strong> <?php echo $row_message['user_scale']; ?>
        </div>

        <!-- Display replies -->
        <?php if (mysqli_num_rows($result_replies) > 0) { ?>
            <div class="row m-2 p-3 border border-secondary">
                <h4>Replies:</h4>
                <?php while ($reply_row = mysqli_fetch_assoc($result_replies)) { ?>
                    <div class="border border-secondary p-2 mb-2">
                        <strong><?php echo $reply_row['fullname']; ?></strong> (<?php echo $reply_row['replied_at']; ?>): <?php echo $reply_row['reply_text']; ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- Reply form -->
        <div class="row m-2 p-3 border border-secondary">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?message_id=' . $message_id; ?>">
                <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                <div class="form-group">
                    <label for="reply_text">Reply:</label>
                    <textarea class="form-control" id="reply_text" name="reply_text" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Post Reply</button>
            </form>
        </div>

        <!-- Back to Message Board link -->
        <div class="row m-2">
            <a href="message_board.php" class="btn btn-secondary">Back to Message Board</a>
        </div>
    </div>
</div>




<?php
// Include footer
include '../footer.php';
?>
