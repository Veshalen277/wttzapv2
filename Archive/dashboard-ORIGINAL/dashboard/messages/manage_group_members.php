<?php
include '../header.php';


// Fetch groups from the database
$sql = "SELECT g.id, g.group_name, g.created_at, u.fullname AS created_by
        FROM groups g
        INNER JOIN users_tbl u ON g.created_by = u.id
        ORDER BY g.created_at DESC";
$result = mysqli_query($con, $sql);
?>

<div class="container-fluid mt-3 p-5 bg-white border ">
    <h2>Group Management</h2>
    
    <!-- Button to Add New Group -->
    <button class="btn btn-primary" data-toggle="modal" data-target="#addGroupModal">Add New Group</button>

    <!-- Groups List -->
    <div id="groupsList">
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='card group-card mb-3'>";
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>{$row['group_name']}</h5>";
                echo "<p class='card-text'>Created by: {$row['created_by']} on {$row['created_at']}</p>";
                echo "<div class='group-actions'>";
                echo "<button class='btn btn-warning' data-toggle='modal' data-target='#manageMembersModal'>Manage Members</button>";
                echo "<button class='btn btn-danger'>Delete Group</button>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No groups found.</p>";
        }
        ?>
    </div>
</div>

<!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-labelledby="addGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGroupModalLabel">Add New Group</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addGroupForm" method="POST" action="add_group.php"> <!-- Assuming you have a separate file for adding groups -->
                    <div class="form-group">
                        <label for="groupName">Group Name</label>
                        <input type="text" class="form-control" id="groupName" name="group_name" required>
                    </div>
                    <div class="form-group">
                        <label for="groupDescription">Description</label>
                        <textarea class="form-control" id="groupDescription" name="group_description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Group</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Manage Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1" role="dialog" aria-labelledby="manageMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageMembersModalLabel">Manage Group Members</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Group Name</h6>
                <ul class="list-group">
                    <!-- You may want to dynamically load members here as well -->
                    <li class="list-group-item">User 1 <button class="btn btn-danger btn-sm float-right">Remove</button></li>
                    <li class="list-group-item">User 2 <button class="btn btn-danger btn-sm float-right">Remove</button></li>
                </ul>
                <div class="mt-3">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addMemberModal">Add New Member</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Add New Member</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addMemberForm" method="POST" action="add_member.php"> <!-- Assuming you have a separate file for adding members -->
                    <div class="form-group">
                        <label for="memberEmail">Member Email</label>
                        <input type="email" class="form-control" id="memberEmail" name="member_email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
