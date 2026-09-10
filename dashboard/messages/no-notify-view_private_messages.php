<?php
// Start or resume session
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

// Get the current user ID
$user_id = $_SESSION['u_data'][5];

// Fetch messages for the current user
$sql = "SELECT pm.id, pm.sender_id, pm.receiver_id, pm.message_text, pm.sent_at, pm.parent_message_id,
               (CASE WHEN pm.sender_id = $user_id THEN 'You' ELSE u.fullname END) AS sender
        FROM private_messages pm
        LEFT JOIN users_tbl u ON pm.sender_id = u.id
        WHERE pm.receiver_id = $user_id OR pm.sender_id = $user_id
        ORDER BY pm.sent_at ASC";
$result = mysqli_query($con, $sql);

// Create an array of messages to track parent-child relationships
$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[$row['id']] = $row;
}

// Handle reply form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_text'])) {
    $parent_message_id = mysqli_real_escape_string($con, $_POST['parent_message_id']);
    $reply_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['reply_text']));
    $sender_id = $user_id;

    $sql_reply = "INSERT INTO private_messages (sender_id, receiver_id, message_text, sent_at, parent_message_id) 
                  SELECT '$sender_id', receiver_id, '$reply_text', NOW(), '$parent_message_id'
                  FROM private_messages
                  WHERE id = '$parent_message_id'";
    if (mysqli_query($con, $sql_reply)) {
        $_SESSION['msg'] = "Reply sent successfully.";
        $_SESSION['msg_type'] = "success";
        header("Location: view_private_messages.php");
        exit();
    } else {
        $_SESSION['msg'] = "Error sending reply: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
}

// Handle message deletion
if (isset($_POST['delete_message'])) {
    $message_id_to_delete = intval($_POST['message_id']);
    
    // Delete replies associated with the message
    $delete_replies_query = "DELETE FROM private_messages WHERE parent_message_id = ?";
    $stmt_delete_replies = $con->prepare($delete_replies_query);
    $stmt_delete_replies->bind_param("i", $message_id_to_delete);
    $stmt_delete_replies->execute();
    
    // Now delete the message itself
    $delete_message_query = "DELETE FROM private_messages WHERE id = ?";
    $stmt_delete_message = $con->prepare($delete_message_query);
    $stmt_delete_message->bind_param("i", $message_id_to_delete);
    $stmt_delete_message->execute();
    
    $_SESSION['msg'] = "Message deleted successfully.";
    $_SESSION['msg_type'] = "success";
    header("Location: view_private_messages.php");
    exit();
}

?>
<div class="container-fluid">
    <div class="row">
                <div class="col-md-3 col-sm-12">
            <?php include '../inc/sidebar.php';?>
        </div>

        <div class="col-md-9 col-sm-12">
<div class="container mt-4">
    <h2>Private Messages</h2>

    <?php
    // Display any session messages
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-{$_SESSION['msg_type']}'>".$_SESSION['msg']."</div>";
        unset($_SESSION['msg']);
    }

    // Check if there are messages
    if (empty($messages)) {
        echo "<div class='alert alert-info'>Looks like your inbox is as empty as a philosopher's coffee cup! Time to start a <a href='/dashboard/messages/private_messages.php'>conversation!</a></div>";
    } else {
    ?>
    
    <div id="accordion">
        <?php foreach ($messages as $message): ?>
            <?php
            $message_id = $message['id'];
            ?>

            <div class="card">
                <div class="card-header" id="heading<?php echo $message_id; ?>">
                    <h5 class="mb-0">
                        <a href="view_private_message.php?message_id=<?php echo $message_id; ?>" class="btn btn-link">
                            <?php echo htmlspecialchars($message['sender']) . " (" . $message['sent_at'] . ")"; ?>
                        </a>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                            <button type="submit" name="delete_message" class="btn btn-danger btn-sm float-right" onclick="return confirm('Are you sure you want to delete this message?');">
                                Delete
                            </button>
                        </form>
                    </h5>
                </div>

                <div class="collapse" aria-labelledby="heading<?php echo $message_id; ?>" data-parent="#accordion">
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($message['message_text']); ?></p>

                        <?php
                        // Display replies for this message
                        foreach ($messages as $reply) {
                            if ($reply['parent_message_id'] == $message_id) {
                                echo "<div class='reply'>";
                                echo "<strong>{$reply['sender']}</strong> ({$reply['sent_at']}):<br>";
                                echo "<p>{$reply['message_text']}</p>";
                                echo "</div>";
                            }
                        }
                        ?>

                        <!-- Reply form for each message -->
                        <h4>Reply to this message:</h4>
                        <form method="post" action="">
                            <input type="hidden" name="parent_message_id" value="<?php echo $message_id; ?>">
                            <textarea name="reply_text" rows="4" cols="50" placeholder="Write your reply here" required></textarea><br>
                            <button type="submit" class="btn btn-primary">Send Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php } // End of message check ?>
</div>
        </div>
    </div>
</div>



<?php include '../footer.php'; ?>
</body>
</html>

<?php
// Close the database connection
mysqli_close($con);
?>
