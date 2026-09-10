<?php
include 'header.php'; // This handles the database connection

if (isset($_POST['group_id'])) {
    $group_id = mysqli_real_escape_string($con, $_POST['group_id']);

    // Fetch group name
    $group_query = mysqli_query($con, "SELECT group_name FROM groups WHERE id = '$group_id'");
    $group = mysqli_fetch_assoc($group_query);

    // Fetch group members
    $members_query = mysqli_query($con, "SELECT user_id FROM group_members WHERE group_id = '$group_id'");
    $members_html = '';
    while ($member = mysqli_fetch_assoc($members_query)) {
        $members_html .= '<li class="list-group-item">' . htmlspecialchars($member['user_id']) . '</li>';
    }

    $response = [
        'group_name' => $group['group_name'],
        'members_html' => $members_html
    ];

    echo json_encode($response);
}
?>
