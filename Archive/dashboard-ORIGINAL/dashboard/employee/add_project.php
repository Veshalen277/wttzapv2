<?php
include '../header.php';
include '../functions.php';
require '../config.php'; // DB connection

// Fetch users for association
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl WHERE user_role >= 1");
?>

<div class="container py-5">
    <h4 class="mb-4">Add New Project</h4>
    <form action="process_project.php" method="post">
        <div class="form-group">
            <label for="project_name">Project Name</label>
            <input type="text" class="form-control" id="project_name" name="project_name" required>
        </div>
        <div class="form-group">
            <label for="project_description">Description</label>
            <textarea class="form-control" id="project_description" name="project_description" required></textarea>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date">
        </div>
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="Pending">Pending</option>
                <option value="Active">Active</option>
                <option value="Completed">Completed</option>
                <option value="On Hold">On Hold</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select class="form-control" id="priority" name="priority">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>
        <div class="form-group">
            <label for="budget">Budget</label>
            <input type="number" class="form-control" id="budget" name="budget" step="0.01">
        </div> 
        <div class="form-group">
            <label for="users">Associate Users:</label>
            <select multiple class="form-control" name="users[]" id="users">
                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['fullname']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Project</button>
    </form>
</div>

<?php include '../footer.php'; ?>


<!-- </?php
include '../header.php';
include '../functions.php';
require '../config.php'; // DB connection

// Fetch users for association
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl WHERE user_role >= 1");
?>

<div class="container py-5">
    <h4 class="mb-4">Add New Project</h4>
<form action="process_project.php" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="project_name">Project Name</label>
        <input type="text" class="form-control" id="project_name" name="project_name" required>
    </div>
    <div class="form-group">
        <label for="project_description">Description</label>
        <textarea class="form-control" id="project_description" name="project_description" required></textarea>
    </div>
    <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" class="form-control" id="start_date" name="start_date">
    </div>
    <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" class="form-control" id="end_date" name="end_date">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
            <option value="Pending">Pending</option>
            <option value="Active">Active</option>
            <option value="Completed">Completed</option>
            <option value="On Hold">On Hold</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>
    <div class="form-group">
        <label for="project_image">Project Image</label>
        <input type="file" class="form-control-file" id="project_image" name="project_image">
    </div>
    <div class="form-group">
        <label for="priority">Priority</label>
        <select class="form-control" id="priority" name="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>
    </div>
    <div class="form-group">
        <label for="budget">Budget</label>
        <input type="number" class="form-control" id="budget" name="budget" step="0.01">
    </div>
    <div class="form-group">
        <label for="users">Associate Users:</label>
        <select multiple class="form-control" name="users[]" id="users">
            </?php while ($user = mysqli_fetch_assoc($users_result)): ?>
            <option value="</?= $user['id'] ?>"></?= htmlspecialchars($user['fullname']) ?></option>
            </?php endwhile; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save Project</button>
</form>

</div>
</?php include '../footer.php'; ?> -->
<!-- </?php
include '../header.php';
include '../functions.php';
require '../config.php'; // DB connection

// Fetch users for association
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl WHERE user_role >= 1");
?>

<div class="container py-5">
    <h4 class="mb-4">Add New Project</h4>
    <form action="process_project.php" method="post">
        <div class="form-group">
            <label for="project_name">Project Name</label>
            <input type="text" class="form-control" id="project_name" name="project_name" required>
        </div>
        <div class="form-group">
            <label for="project_description">Description</label>
            <textarea class="form-control" id="project_description" name="project_description" required></textarea>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date">
        </div>
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="Pending">Pending</option>
                <option value="Active">Active</option>
                <option value="Completed">Completed</option>
                <option value="On Hold">On Hold</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select class="form-control" id="priority" name="priority">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>
        <div class="form-group">
            <label for="budget">Budget</label>
            <input type="number" class="form-control" id="budget" name="budget" step="0.01">
        </div>
        <div class="form-group">
            <label for="users">Associate Users:</label>
            <select multiple class="form-control" name="users[]" id="users">
                </?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                <option value="</?= $user['id'] ?>"></?= htmlspecialchars($user['fullname']) ?></option>
                </?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Project</button>
    </form>
</div>

</?php include '../footer.php'; ?> -->
