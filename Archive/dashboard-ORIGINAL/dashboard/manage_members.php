<?php
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);
    
    // Fetch group details
    $group_result = mysqli_query($con, "SELECT group_name FROM groups WHERE id = $group_id");
    $group = mysqli_fetch_assoc($group_result);
    
    // Fetch group members
    $members_result = mysqli_query($con, "SELECT u.fullname, u.email FROM group_members gm JOIN users_tbl u ON gm.user_id = u.id WHERE gm.group_id = $group_id");
    
    $members_html = '';
    while ($member = mysqli_fetch_assoc($members_result)) {
        $members_html .= '<li class="list-group-item">' . htmlspecialchars($member['fullname']) . ' (' . htmlspecialchars($member['email']) . ') <button class="btn btn-danger btn-sm float-end" data-member-email="' . htmlspecialchars($member['email']) . '">Remove</button></li>';
    }
} else {
    // Redirect or handle error if no group_id is provided
    header("Location: view_groups.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h1>Manage Members - <?php echo htmlspecialchars($group['group_name']); ?></h1>
    
    <form id="addMemberForm" method="POST" action="add_member.php" class="mt-4">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <div class="mb-3">
            <label for="memberEmail" class="form-label">Member Email</label>
            <input type="email" class="form-control" id="memberEmail" name="member_email" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Member</button>
    </form>
    
    <h3 class="mt-4">Current Members</h3>
    <ul class="list-group" id="membersList">
        <?php echo $members_html; ?>
    </ul>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#membersList').on('click', '.btn-danger', function() {
        var email = $(this).data('member-email');
        var groupId = <?php echo $group_id; ?>;
        
        if (confirm('Are you sure you want to remove this member?')) {
            $.post('remove_member.php', { email: email, group_id: groupId }, function(response) {
                if (response.success) {
                    alert('Member removed successfully.');
                    location.reload();
                } else {
                    alert('Error removing member: ' + response.error);
                }
            }, 'json');
        }
    });
});
</script>
</body>
</html>
