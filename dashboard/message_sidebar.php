<div id="sidebar" class="p-3 bg-white text-secondary border border-danger">
    <div class="sidebar-header">
        <?php
        // Set the default timezone to your preferred timezone
        date_default_timezone_set('UTC');

        // Fetch current date and time
        $currentDateTime = date('Y-m-d H:i:s');

        // Output the result
        echo "Current Date/Time: " . $currentDateTime;
        ?>
    </div>

    <h3 class="text-danger">Your Messages</h3>

    <?php
    // Pagination configuration for sidebar
    $limit_sidebar = 15; // Number of messages per page in sidebar
    $sidebar_page = isset($_GET['message_sidebar']) ? intval($_GET['message_sidebar']) : 1; // Current page for sidebar, default is 1
    $start_sidebar = ($sidebar_page - 1) * $limit_sidebar; // Calculate starting point for sidebar messages

    // Query to fetch messages for sidebar
    $user_id_sidebar = $_SESSION['u_data'][5]; // Assuming 'id' is the user ID in $_SESSION['u_data']
    $sql_user_messages_sidebar = "SELECT messages.id, messages.message_text, messages.posted_at, users_tbl.fullname 
                                  FROM messages 
                                  INNER JOIN users_tbl ON messages.user_id = users_tbl.id 
                                  WHERE messages.user_id = $user_id_sidebar 
                                  ORDER BY messages.posted_at DESC 
                                  LIMIT $start_sidebar, $limit_sidebar";
    $result_user_messages_sidebar = mysqli_query($con, $sql_user_messages_sidebar);
    $total_messages_sidebar = mysqli_num_rows($result_user_messages_sidebar);

    if ($total_messages_sidebar > 0) {
        echo "<div class='message-list p-1 border border-secondary'>";
        while ($row = mysqli_fetch_assoc($result_user_messages_sidebar)) {
            echo "<div class='message bg-white'>";
            echo "<p><a href='view_message.php?message_id={$row['id']}'>" . (isset($row['message_text']) && strlen($row['message_text']) > 10 ? substr($row['message_text'], 0, 10) . '...' : $row['message_text']) . "</a></p>";
            echo "<p class='message-time'>Posted At: {$row['posted_at']}</p>";
            echo "<p class='message-sender'>Posted By: {$row['fullname']}</p>";

            // Query to fetch replies to each message in the sidebar
            $message_id = $row['id'];
            $sql_replies_sidebar = "SELECT replies.id, replies.reply_text, replies.replied_at, users_tbl.fullname 
                                    FROM replies 
                                    INNER JOIN users_tbl ON replies.user_id = users_tbl.id 
                                    WHERE replies.message_id = $message_id 
                                    ORDER BY replies.replied_at ASC";

            $result_replies_sidebar = mysqli_query($con, $sql_replies_sidebar);

            if (mysqli_num_rows($result_replies_sidebar) > 0) {
                echo "<div class='message-replies'>";
                echo "<small class='text-muted'>Replies:</small>";
                while ($reply_row = mysqli_fetch_assoc($result_replies_sidebar)) {
                    echo "<p class='reply-text'><span>{$reply_row['fullname']}</span> ({$reply_row['replied_at']}): {$reply_row['reply_text']}</p>";
                }
                echo "</div>";
            } else {
                echo "<p>No replies yet.</p>";
            }
            echo "</div>";
            echo "<hr>"; // Corrected the closing tag for <hr>
        }
        echo "</div>";

        // Calculate total pages
        $total_pages = ceil($total_messages_sidebar / $limit_sidebar);

        // Display pagination links
        echo "<div class='pagination'>";
        if ($sidebar_page > 1) {
            echo "<a href='message_board.php?message_sidebar=" . ($sidebar_page - 1) . "' class='btn btn-outline-primary'>Previous</a>";
        }

        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<a href='message_board.php?message_sidebar=$i' class='" . ($sidebar_page == $i ? 'btn btn-primary' : 'btn btn-outline-primary') . "'>$i</a>";
        }

        if ($sidebar_page < $total_pages) {
            echo "<a href='message_board.php?message_sidebar=" . ($sidebar_page + 1) . "' class='btn btn-outline-primary'>Next</a>";
        }
        echo "</div>";

    } else {
        echo "<p>No messages found.</p>";
    }
    ?>
</div>
