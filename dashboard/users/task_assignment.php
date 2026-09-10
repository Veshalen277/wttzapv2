<?php
// Include necessary files
include '../header.php'; // Ensure this file initializes $con and handles session
include '../functions.php'; // Ensure this file contains required functions

if ($role != 2 && $role != 5) {
    header("Location: /dashboard/404.php");
    exit();
}

// Check if ID is provided via GET
// if (!isset($_GET['id'])) {
//     header("Location: users_list.php"); // Redirect if ID is not provided
//     exit;
// }

// Fetch user data from session
$user = $_SESSION['u_data'] ?? null; 

// Check if user data is available
if ($user === null) {
    header("Location: /dashboard/404.php");
    exit;
}

// Fetch user details based on ID
$id = $_GET['id'];
$result = get_user_by_id($con, $id);
if ($result === null) {
    $_SESSION['error'] = "User not found!";
    header("Location: users_list.php");
    exit;
}

// Handle employee details update
if (isset($_POST['update_emp'])) {
    $post_data = $_POST;
    $post_data['id'] = $id; // Add ID to post data for update

    // Remove unnecessary fields
    unset($post_data['user_pass']);
    unset($post_data['user_role']);

    // Update user details
    $query = update_user_details($con, $post_data);

    if ($query) {
        $_SESSION['update'] = "Record updated!";
        header("Location: users_list.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating record: " . mysqli_error($con);
        header("Location: edit_emp.php?id=$id");
        exit;
    }
}

// Handle task assignment update
if (isset($_POST['assign_task'])) {
    $task_data = $_POST['new_user_res']; // Changed to avoid conflict

    // Update assigned tasks in the database
    $task_query = update_user_tasks($con, $id, $task_data);

    if ($task_query) {
        $_SESSION['task_update'] = "Tasks updated!";
    } else {
        $_SESSION['task_error'] = "Error updating tasks: " . mysqli_error($con);
    }
}
?>

<!-- HTML Form for editing employee details -->
<div class="container mt-2">
    <form action="" method="POST">
        <div class="row m-2 p-3 register_form border border-secondary">
            <h5 class="text-center">Edit Employee</h5>
            <div class="col-md-5 m-auto">
                <div class="mb-2">
                    <label>Fullname</label>
                    <input type="text" name="user_name" placeholder="Fullname" class="form-control" required value="<?= isset($result['fullname']) ? htmlspecialchars($result['fullname']) : '' ?>" maxlength="30" minlength="3">
                </div>
                <!-- Other fields... -->
            </div>

            <div class="col-md-5">
                <div class="mb-2 border border-danger border-3 p-2">
                    <label><?= isset($result['fullname']) ? htmlspecialchars($result['fullname']) : '' ?> has been assigned these tasks:</label>
                    <textarea class="form-control" rows="6" name="existing_tasks" maxlength="300" minlength="10"><?= isset($result['user_res']) ? htmlspecialchars($result['user_res']) : '' ?></textarea>
                </div>

                <div class="mb-2">
                    <button class="btn btn-danger btn-sm text-white" name="update_emp">Update Employee</button>
                </div>
            </div>
        </div>
    </form>

    <!-- New Form for Assigning Tasks -->
    <form action="" method="POST" class="mt-4">
        <h5 class="text-center">Assign Tasks</h5>
        <div class="mb-2">
            <label>Assign New Tasks:</label>
            <textarea class="form-control" rows="4" name="new_user_res" maxlength="300" minlength="10" placeholder="Enter tasks here..."></textarea>
        </div>
        <button class="btn btn-primary" name="assign_task">Assign Tasks</button>
    </form>
</div>

<?php include '../footer.php'; ?>
