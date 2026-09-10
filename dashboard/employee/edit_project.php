<?php
include '../header.php';

require '../config.php';

$project_id = $_GET['project_id'] ?? null;

if (!$project_id) {
    die("Invalid project ID.");
}

// Fetch the project details
$project_sql = "SELECT * FROM projects_tbl WHERE project_id = ?";
$stmt = mysqli_prepare($con, $project_sql);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$project_result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($project_result);

if (!$project) {
    die("Project not found.");
}

// Fetch all users for association
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl WHERE user_role >= 1");

// Fetch already associated user IDs
$assoc_users = [];
$assoc_query = mysqli_query($con, "SELECT user_id FROM project_users_tbl WHERE project_id = $project_id");
while ($row = mysqli_fetch_assoc($assoc_query)) {
    $assoc_users[] = $row['user_id'];
}
?>

<div class="container py-5">
    <h4 class="mb-4">Edit Project</h4>
    <form action="update_project.php" method="post">
        <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
        
        <div class="form-group">
            <label for="project_name">Project Name</label>
            <input type="text" class="form-control" id="project_name" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="project_description">Description</label>
            <textarea class="form-control" id="project_description" name="project_description" required><?= htmlspecialchars($project['project_description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $project['start_date'] ?>">
        </div>
        
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $project['end_date'] ?>">
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <?php
                $statuses = ['Pending', 'Active', 'Completed', 'On Hold', 'Cancelled'];
                foreach ($statuses as $status) {
                    $selected = $status == $project['status'] ? 'selected' : '';
                    echo "<option value='$status' $selected>$status</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <select class="form-control" id="priority" name="priority">
                <?php
                $priorities = ['Low', 'Medium', 'High'];
                foreach ($priorities as $priority) {
                    $selected = $priority == $project['priority'] ? 'selected' : '';
                    echo "<option value='$priority' $selected>$priority</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="budget">Budget</label>
            <input type="number" class="form-control" id="budget" name="budget" value="<?= $project['budget'] ?>" step="0.01">
        </div>

        <div class="form-group">
            <label for="users">Associate Users:</label>
            <select multiple class="form-control" name="users[]" id="users">
                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <option value="<?= $user['id'] ?>" <?= in_array($user['id'], $assoc_users) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['fullname']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Update Project</button>
    </form>
</div>

<?php include '../footer.php'; ?>
