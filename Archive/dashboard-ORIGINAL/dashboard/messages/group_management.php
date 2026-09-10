<?php
include '../header.php'; // Ensure session management and connection are handled here

// Handle group deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_group'])) {
    $group_id = intval($_POST['group_id']);

    // Delete group members first
    $sql_delete_members = "DELETE FROM group_members WHERE group_id = $group_id";
    mysqli_query($con, $sql_delete_members);

    // Delete group messages
    $sql_delete_messages = "DELETE FROM group_messages WHERE group_id = $group_id";
    mysqli_query($con, $sql_delete_messages);

    // Delete the group
    $sql_delete_group = "DELETE FROM groups WHERE id = $group_id";
    if (mysqli_query($con, $sql_delete_group)) {
        $_SESSION['msg'] = "Group deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error deleting group: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: group_management.php");
    exit();
}

// Handle group name update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_group'])) {
    $group_id = intval($_POST['group_id']);
    $group_name = mysqli_real_escape_string($con, htmlspecialchars($_POST['group_name']));

    $sql_update_group = "UPDATE groups SET group_name = '$group_name' WHERE id = $group_id";
    if (mysqli_query($con, $sql_update_group)) {
        $_SESSION['msg'] = "Group updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error updating group: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: group_management.php");
    exit();
}

// Fetch groups for display
$groups_result = mysqli_query($con, "SELECT * FROM groups ORDER BY created_at DESC");
?>

<div class="container mt-4">
    <h2>Group Management</h2>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-{$_SESSION['msg_type']}'>".$_SESSION['msg']."</div>";
        unset($_SESSION['msg']);
    }
    ?>

    <!-- Table of groups -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Group Name</th>
                <th>Created By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($group = mysqli_fetch_assoc($groups_result)): ?>
                <tr>
                    <td><?php echo $group['id']; ?></td>
                    <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                    <td><?php echo htmlspecialchars($group['created_by']); ?></td>
                    <td>
                        <!-- Edit Group -->
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editGroupModal" data-id="<?php echo $group['id']; ?>" data-name="<?php echo htmlspecialchars($group['group_name']); ?>">Edit</button>

                        <!-- Delete Group -->
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            <button type="submit" name="delete_group" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this group?');">Delete</button>
                        </form>

                        <!-- Manage Members -->
                        <a href="manage_group_members.php?group_id=<?php echo $group['id']; ?>" class="btn btn-info btn-sm">Manage Members</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Edit Group Modal -->
    <div class="modal fade" id="editGroupModal" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGroupModalLabel">Edit Group</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="group_id" id="editGroupId">
                        <div class="form-group">
                            <label for="editGroupName">Group Name</label>
                            <input type="text" class="form-control" id="editGroupName" name="group_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_group" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    $('#editGroupModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var groupId = button.data('id');
        var groupName = button.data('name');

        var modal = $(this);
        modal.find('#editGroupId').val(groupId);
        modal.find('#editGroupName').val(groupName);
    });
</script>
<?php include '../footer.php';?>