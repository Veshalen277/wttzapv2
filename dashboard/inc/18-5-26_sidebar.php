<?php
// Get the current user ID
$user_id = $_SESSION['u_data'][5];

// Query to count all messages for the user (private messages)
$message_count_query = "
    SELECT COUNT(*) AS message_count 
    FROM private_messages 
    WHERE receiver_id = $user_id OR sender_id = $user_id";
$result_message_check = mysqli_query($con, $message_count_query);
$message_count = mysqli_fetch_assoc($result_message_check)['message_count'];

// Query to count unread notifications for public messages
$notification_count_query = "
    SELECT COUNT(*) AS notification_count 
    FROM notifications 
    WHERE user_id = $user_id AND is_read = FALSE";
$result_notification_check = mysqli_query($con, $notification_count_query);
$notification_count = mysqli_fetch_assoc($result_notification_check)['notification_count'];
?>
<style>
/* Add media query for mobile-specific styling */
@media (max-width: 768px) {
    .custom-nav {
        display: flex !important;
        flex-direction: row !important;
    }
    .custom-nav .nav-item {
        flex: 1;
        text-align: center;
    }
    .custom-nav .nav-link {
        padding: 0.5rem;
    }
    .custom-nav i {
        font-size: 0.9rem; /* Adjust icon size for better visibility */
    }
}


</style>
<ul class="custom-nav nav flex-column flex-md-column flex-sm-row justify-content-between d-md-block">
    <h3>Quick Links</h3>
    <li class="nav-item mb-2">
        <a href="/dashboard/users/checklist.php" class="nav-link p-0 text-body-secondary">
           <i class="bi bi-calendar-check"></i>
            <span class="d-none d-md-inline">Checklist</span>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/employee/my_profile.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-person-circle"></i> 
            <span class="d-none d-md-inline">Edit Profile</span>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/messages/message_board.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-chat-dots"></i> 
            <span class="d-none d-md-inline">Messages</span>
            <?php if ($notification_count > 0): ?>
                <span class="badge badge-danger"><?php echo $notification_count; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/messages/private_messages.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-incognito"></i> 
            <span class="d-none d-md-inline">Send Private Message</span>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/messages/view_private_messages.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-chat"></i> 
            <span class="d-none d-md-inline">Private Messages</span>
            <?php if ($message_count > 0): ?>
                <span class="badge badge-danger"><?php echo $message_count; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/suggestions/suggestion.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-balloon-heart"></i> 
            <span class="d-none d-md-inline">Suggest</span>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="/dashboard/users/leave_app.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-rocket-takeoff-fill"></i> 
            <span class="d-none d-md-inline">Leave Application</span>
        </a>
    </li>
        <li class="nav-item mb-2">
        <a href="/dashboard/orders/orderForm.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-clock"></i> 
            <span class="d-none d-md-inline">Place Order</span>
        </a>
    </li>
        </li>
        <li class="nav-item mb-2">
        <a href="/dashboard/employee/project_dashboard.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-building"></i> 
            <span class="d-none d-md-inline">Projects</span>
        </a>
    </li>
        <li class="nav-item mb-2">
        <a href="/dashboard/bulletins/publish.php" class="nav-link p-0 text-body-secondary">
            <i class="bi bi-building"></i> 
            <span class="d-none d-md-inline">Post Notice</span>
        </a>
    </li>




    
</ul>

             <div class="container">
           <?php include '../bulletins/widget.php'; ?>

</div>