<?php
include '../header.php';

// Fetch all groups from the database
$groups_result = mysqli_query($con, "SELECT id, group_name FROM groups");
?>

<div class="container mt-4">
    <h2>Group Management</h2>
    
    <!-- Button to Add New Group -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">Add New Group</button>

    <!-- Groups List -->
    <div id="groupsList">
        <?php while ($group = mysqli_fetch_assoc($groups_result)): ?>
            <div class="card group-card mt-2">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($group['group_name']); ?></h5>
                    <p class="card-text">Created by User</p>
                    <div class="group-actions">
                        <a href="send_group_message.php?group_id=<?php echo $group['id']; ?>" class="btn btn-info">View Messages</a>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#manageMembersModal" data-group-id="<?php echo $group['id']; ?>">Manage Members</button>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteGroupModal" data-group-id="<?php echo $group['id']; ?>">Delete Group</button>
                        <!-- View Group Button -->
                        <a href="view_group_details.php?group_id=<?php echo $group['id']; ?>" class="btn btn-primary">View Group</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGroupModalLabel">Add New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addGroupForm" method="POST" action="add_group.php">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="groupDescription" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Group</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Manage Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1" aria-labelledby="manageMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageMembersModalLabel">Manage Group Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="modalGroupName">Group Name</h6>
                <ul class="list-group" id="membersList">
                    <!-- Members will be dynamically loaded here -->
                </ul>
                <div class="mt-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">Add New Member</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMemberForm" method="POST" action="add_member.php">
                    <div class="mb-3">
                        <label for="memberEmail" class="form-label">Member Email</label>
                        <input type="email" class="form-control" id="memberEmail" name="member_email" required>
                        <input type="hidden" id="hiddenGroupId" name="group_id">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Group Modal -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1" aria-labelledby="deleteGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGroupModalLabel">Delete Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this group?</p>
                <form id="deleteGroupForm" method="POST" action="delete_group.php">
                    <input type="hidden" id="deleteGroupId" name="group_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // Populate modals with correct data using JavaScript
    $('#manageMembersModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var groupId = button.data('group-id');
        var modal = $(this);

        // Fetch group name and members using AJAX
        $.ajax({
            url: 'fetch_group_data.php',
            type: 'POST',
            data: { group_id: groupId },
            success: function(response) {
                var data = JSON.parse(response);
                modal.find('#modalGroupName').text(data.group_name);
                modal.find('#membersList').html(data.members_html);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error: ' + textStatus);
            }
        });
    });

    $('#addMemberModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var groupId = button.data('group-id');
        var modal = $(this);
        modal.find('#hiddenGroupId').val(groupId);
    });

    $('#deleteGroupModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var groupId = button.data('group-id');
        var modal = $(this);
        modal.find('#deleteGroupId').val(groupId);
    });
</script>
<?php include '../footer.php';?>


