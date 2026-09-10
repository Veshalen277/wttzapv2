<?php
// Include necessary files
include '../header.php'; // Ensure this includes database connection and session start


$user_id = $_GET['id'];

// Fetch user details
$sql = "SELECT * FROM users_tbl WHERE id = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Check if user exists
// if (!$user) {
//     $_SESSION['error'] = "User not found.";
//     header("Location: users_list.php");
//     exit;
// }

// Handle form submission
if (isset($_POST['update_tasks'])) {
    $task_description = $_POST['task_description'];

    // Update the user's tasks in the database
    $update_sql = "UPDATE users_tbl SET user_res = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $task_description, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['msg'] = "Tasks updated successfully!";
        header("Location: assigned_work.php"); // Redirect after success
        exit;
    } else {
        $_SESSION['error'] = "Error updating tasks: " . mysqli_error($con);
    }
}
?>

<div class="container mt-2">
    <h2>Edit Tasks for <?= htmlspecialchars($user['fullname']); ?></h2>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label for="task_description">Task Description:</label>
            <textarea class="form-control" name="task_description" rows="6" required><?= htmlspecialchars($user['user_res']); ?></textarea>
        </div>
<button type="submit" class="btn btn-primary btn-sm text-white" name="update_tasks">Update Tasks</button>
<a href="assigned_work.php" class="btn btn-secondary btn-sm text-white">Back</a>


    </form>
</div>

<?php
include '../footer.php'; // Include footer
?>
