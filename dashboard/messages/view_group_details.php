<?php
include '../header.php';

if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);

    // Fetch group details
    $group_result = mysqli_query($con, "SELECT group_name FROM groups WHERE id = $group_id");
    $group = mysqli_fetch_assoc($group_result);

    // Fetch group members
    $members_result = mysqli_query($con, "SELECT u.fullname, u.email FROM group_members gm JOIN users_tbl u ON gm.user_id = u.id WHERE gm.group_id = $group_id");
    ?>
    


    <div class="container-fluid mt-3 p-5 bg-white border ">
<div class="container p-3">
        <div class="row">
    <h1><?php echo htmlspecialchars($group['group_name']); ?></h1>
    <h3>Members</h3>
    <ul>
        <?php while ($member = mysqli_fetch_assoc($members_result)) { ?>
            <li><?php echo htmlspecialchars($member['fullname']) . ' (' . htmlspecialchars($member['email']) . ')'; ?></li>
        <?php } ?>
    </ul>
        </div>
            <a href="view_groups.php" class="btn btn-secondary">Back to Groups</a>
</div>
</div>


    <?php
} else {
    echo "Group not found.";
}
?>
<?php include '../footer.php'?>