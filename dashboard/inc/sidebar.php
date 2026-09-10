<?php
// Shared contextual navigation replaces this legacy quick-links block.
if (defined('PORTAL_CONTEXT_NAV')) {
    echo '<span class="portal-legacy-menu" hidden></span>';
    return;
}

// Get the current user ID
$user_id = $_SESSION['u_data'][5] ?? 0;

// Optimized Query: Get counts in one go to save database resources
$counts_query = "
    SELECT 
        (SELECT COUNT(*) FROM private_messages WHERE (receiver_id = $user_id OR sender_id = $user_id)) as message_count,
        (SELECT COUNT(*) FROM notifications WHERE user_id = $user_id AND is_read = FALSE) as notification_count
";
$result = mysqli_query($con, $counts_query);
$data = mysqli_fetch_assoc($result);
$message_count = $data['message_count'] ?? 0;
$notification_count = $data['notification_count'] ?? 0;
?>

<style>
/* Modern Quick Links Styling */
.quick-links-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid #f0f0f0;
}

.quick-links-title {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    color: #adb5bd;
    margin-bottom: 1.2rem;
}

.custom-nav .nav-item {
    margin-bottom: 8px;
}

.custom-nav .nav-link {
    display: flex;
    align-items: center;
    padding: 10px 15px !important;
    border-radius: 10px;
    transition: all 0.3s ease;
    color: #495057 !important;
    position: relative;
}

/* Hover Effect */
.custom-nav .nav-link:hover {
    background: #f8f9fa;
    color: #0d6efd !important;
    transform: translateX(5px);
}

.custom-nav i {
    font-size: 1.2rem;
    margin-right: 12px;
    width: 25px;
    text-align: center;
    transition: transform 0.3s ease;
}

.custom-nav .nav-link:hover i {
    transform: scale(1.2);
}

/* Notification Badge Styling */
.badge-pulse {
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    padding: 3px 7px;
    border-radius: 20px;
    position: absolute;
    right: 10px;
    box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 71, 87, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
}

/* Mobile Responsiveness: Horizontal Scroll Layout */
@media (max-width: 768px) {
    .quick-links-card { padding: 1rem; border-radius: 0; border-left: 0; border-right: 0; }
    .custom-nav {
        display: flex !important;
        flex-direction: row !important;
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 5px;
    }
    .custom-nav::-webkit-scrollbar { display: none; } /* Hide scrollbar */
    .custom-nav .nav-item { margin-bottom: 0; margin-right: 5px; }
    .custom-nav .nav-link { flex-direction: column; font-size: 0.7rem; padding: 8px !important; width: 80px; text-align: center; }
    .custom-nav i { margin-right: 0; margin-bottom: 5px; font-size: 1.1rem; }
    .badge-pulse { position: static; margin-top: 2px; }
}
</style>

<div class="quick-links-card shadow-sm mb-4">
    <h3 class="quick-links-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Links</h3>
    
    <ul class="custom-nav nav flex-column">
        
    
    
    
    
    

        <li class="nav-item">
            <a href="/dashboard/messages/message_board.php" class="nav-link">
                <i class="bi bi-alarm text-success"></i>
                <span>Broadcast Message</span>
            </a>
        </li>
        
    <li class="nav-item">
            <a href="/dashboard/users/checklist.php" class="nav-link">
                <i class="bi bi-calendar-check text-success"></i>
                <span>Checklist</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="/dashboard/employee/my_profile.php" class="nav-link">
                <i class="bi bi-person-circle text-primary"></i> 
                <span>Profile</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/messages/message_board.php" class="nav-link">
                <i class="bi bi-chat-dots text-info"></i> 
                <span>Board</span>
                <?php if ($notification_count > 0): ?>
                    <span class="badge-pulse"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/messages/view_private_messages.php" class="nav-link">
                <i class="bi bi-shield-lock text-danger"></i> 
                <span>Inbox</span>
                <?php if ($message_count > 0): ?>
                    <span class="badge-pulse"><?php echo $message_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/suggestions/suggestion.php" class="nav-link">
                <i class="bi bi-balloon-heart text-secondary"></i> 
                <span>Suggest</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/users/leave_app.php" class="nav-link">
                <i class="bi bi-rocket-takeoff-fill text-primary"></i> 
                <span>Leave</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/orders/orderForm.php" class="nav-link">
                <i class="bi bi-bag-plus text-success"></i> 
                <span>Orders</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/dashboard/bulletins/publish.php" class="nav-link">
                <i class="bi bi-megaphone text-warning"></i> 
                <span>Notice</span>
            </a>
        </li>
    </ul>
</div>

<div class="container px-0">
    <?php include '../bulletins/widget.php'; ?>
</div>