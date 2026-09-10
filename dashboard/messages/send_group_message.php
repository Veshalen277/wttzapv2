<?php
include '../header.php'; // Ensure session management and connection are handled here

// Check if group_id is set in the URL
if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);
} else {
    // Handle the case where group_id is not set
    die("Group ID is missing.");
}

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $message_text = mysqli_real_escape_string($con, htmlspecialchars($_POST['message_text']));
    $sender_id = $_SESSION['u_data'][5];

    $sql = "INSERT INTO group_messages (group_id, sender_id, message_text) VALUES ($group_id, $sender_id, '$message_text')";
    if (mysqli_query($con, $sql)) {
        $_SESSION['msg'] = "Message sent successfully.";
        $_SESSION['msg_type'] = "success";
        header("Location: send_group_message.php?group_id=$group_id");
        exit();
    } else {
        $_SESSION['msg'] = "Error sending message: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
}

// Fetch the group name
$group_result = mysqli_query($con, "SELECT group_name FROM groups WHERE id = $group_id");
if ($group_result && mysqli_num_rows($group_result) > 0) {
    $group = mysqli_fetch_assoc($group_result);
} else {
    die("Group not found.");
}

// Fetch messages in the group
$messages_result = mysqli_query($con, "SELECT gm.id, gm.message_text, gm.sent_at, u.fullname
                                      FROM group_messages gm
                                      JOIN users_tbl u ON gm.sender_id = u.id
                                      WHERE gm.group_id = $group_id
                                      ORDER BY gm.sent_at ASC");
?>

<div class="container mt-4">
    <h2>Messages in <?php echo htmlspecialchars($group['group_name'] ?? 'Unknown Group'); ?></h2>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-{$_SESSION['msg_type']}'>".$_SESSION['msg']."</div>";
        unset($_SESSION['msg']);
    }
    ?>

    <div class="messages">
        <?php while ($message = mysqli_fetch_assoc($messages_result)): ?>
            <div class="message">
                <strong><?php echo htmlspecialchars($message['fullname']); ?></strong> (<?php echo $message['sent_at']; ?>):<br>
                <p><?php echo htmlspecialchars($message['message_text']); ?></p>
            </div>
            <hr>
        <?php endwhile; ?>
    </div>

    <form method="post" action="">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <div class="form-group">
            <label for="message_text">Your Message:</label>
            <textarea class="form-control" id="message_text" name="message_text" rows="4" required></textarea>
        </div>
        <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
        <a href="/dashboard/messages/view_groups.php"  class="btn btn-success">Back</a>
    </form>
</div>

<?php include '../footer.php';?>
