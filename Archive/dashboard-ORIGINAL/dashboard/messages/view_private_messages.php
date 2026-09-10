<?php 
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['u_data'][5];

// Fetch messages - logic remains the same
$sql = "SELECT pm.id, pm.sender_id, pm.receiver_id, pm.message_text, pm.sent_at, pm.parent_message_id,
               (CASE WHEN pm.sender_id = $user_id THEN 'You' ELSE u.fullname END) AS sender,
               (SELECT COUNT(*) FROM private_messages WHERE parent_message_id = pm.id) AS reply_count
        FROM private_messages pm
        LEFT JOIN users_tbl u ON pm.sender_id = u.id
        WHERE pm.receiver_id = $user_id OR pm.sender_id = $user_id
        ORDER BY pm.sent_at ASC";
$result = mysqli_query($con, $sql);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

// POST Handling (Reply/Delete) logic stays as you had it...
// [Your existing POST logic here]
?>

<style>
    .messaging-container { background: #f0f2f5; min-height: 80vh; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
    .chat-header { background: #fff; padding: 15px 20px; border-bottom: 1px solid #ddd; }
    .chat-body { flex: 1; padding: 20px; overflow-y: auto; max-height: 600px; background: #fff; }
    
    /* Chat Bubbles */
    .msg-wrapper { display: flex; flex-direction: column; margin-bottom: 15px; }
    .msg { max-width: 75%; padding: 10px 15px; border-radius: 18px; position: relative; font-size: 0.9rem; line-height: 1.4; }
    .msg-received { align-self: flex-start; background: #e4e6eb; color: #050505; border-bottom-left-radius: 4px; }
    .msg-sent { align-self: flex-end; background: #0084ff; color: #fff; border-bottom-right-radius: 4px; }
    
    .msg-info { font-size: 0.7rem; margin-bottom: 4px; color: #65676b; }
    .msg-sent .msg-info { color: #e0e0e0; text-align: right; }
    
    .date-divider { text-align: center; margin: 20px 0; position: relative; }
    .date-divider span { background: #fff; padding: 0 10px; font-size: 0.75rem; color: #888; font-weight: bold; position: relative; z-index: 1; }
    .date-divider::after { content: ""; position: absolute; top: 50%; left: 0; right: 0; border-top: 1px solid #eee; }

    .reply-box { background: #fff; padding: 15px; border-top: 1px solid #eee; }
    .btn-delete { font-size: 0.7rem; padding: 2px 8px; opacity: 0.5; transition: 0.3s; }
    .btn-delete:hover { opacity: 1; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <div class="col-12 col-md-3">
            <h3 class="h6 fw-bold text-uppercase text-muted">Navigation</h3>
            <div class="bg-white border p-2 mb-3 shadow-sm">
                <?php include '../inc/sidebar.php'; ?>
            </div>
        </div>

        <div class="col-12 col-md-9">
            <div class="messaging-container shadow-sm border">
                <div class="chat-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-dots-fill text-primary"></i> Private Messages</h5>
                    <a href="/dashboard/messages/private_messages.php" class="btn btn-sm btn-primary">+ New Conversation</a>
                </div>

                <div class="chat-body">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-left-dots text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No messages yet.</p>
                        </div>
                    <?php else: 
                        $current_date = "";
                        foreach ($messages as $msg): 
                            $msg_date = date('F j, Y', strtotime($msg['sent_at']));
                            if ($current_date != $msg_date): $current_date = $msg_date; ?>
                                <div class="date-divider"><span><?= $current_date ?></span></div>
                            <?php endif; 

                            $is_mine = ($msg['sender_id'] == $user_id);
                            ?>
                            
                            <div class="msg-wrapper">
                                <div class="msg <?= $is_mine ? 'msg-sent' : 'msg-received' ?>">
                                    <div class="msg-info">
                                        <strong><?= htmlspecialchars($msg['sender']) ?></strong> • <?= date('H:i', strtotime($msg['sent_at'])) ?>
                                    </div>
                                    <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                                    
                                    <form method="post" class="mt-2 text-end">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" name="delete_message" class="btn-delete btn btn-link text-danger p-0 border-0" onclick="return confirm('Delete this message?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
<?php
$last_msg = end($messages);

$receiver_id_for_reply = 0;
$parent_id = 0;

if ($last_msg) {
    $parent_id = (int)$last_msg['id'];

    // reply goes to the other person
    if ((int)$last_msg['sender_id'] === (int)$user_id) {
        $receiver_id_for_reply = (int)$last_msg['receiver_id'];
    } else {
        $receiver_id_for_reply = (int)$last_msg['sender_id'];
    }
}
?>
                <!-- <div class="reply-box">
                    <form method="post" action="">
                        <div class="input-group">
                            </?php 
                                // Get the last message ID to auto-reply to the thread
                                $last_msg = end($messages);
                                $parent_id = $last_msg ? $last_msg['id'] : 0;
                            ?>
                            <input type="hidden" name="parent_message_id" value="</?= $parent_id ?>">
                            <textarea name="reply_text" class="form-control border-0 bg-light" rows="1" placeholder="Type a message..." style="resize: none;" required></textarea>
                            <button class="btn btn-primary px-4" type="submit"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div> -->
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_close($con);
include '../footer.php'; 
?>