 <?php

include 'config.php';
include 'header.php';
$sql="SELECT * FROM users_tbl";
$query=mysqli_query($con,$sql);
$rows=mysqli_num_rows($query);
  if (isset($_SESSION['u_data'])) {
     $user=$_SESSION['u_data'];
   }
// Check if the "id" parameter is set in the URL
if (!isset($_GET['id'])) {
    echo "Employee ID is missing.";
    exit; // Terminate script execution
}
$id = $_GET['id'];
$sql = "SELECT * FROM users_tbl WHERE id=$id";
$query = mysqli_query($con, $sql);
if (!$query) {
    // Handle SQL query execution error
    echo "Error: " . mysqli_error($con);
    exit; // Terminate script execution
}
$result = mysqli_fetch_assoc($query);
// Check if the form is submitted
if (isset($_POST['assign_task'])) {
    // Process form submission
    $employee_id = $result['id']; // Employee ID from the URL
    $task = $_POST['user_res']; // Task description from the form

    // Insert task assignment into the database
    $sql = "INSERT INTO users_tbl (id, user_res) VALUES ('$employee_id', '$task')";
    $query = mysqli_query($con, $sql);

    if ($query) {
        $_SESSION['success'] = "Task assigned successfully!";
        // Redirect to the same page after submission
        header("location:users_list.php");
        exit; // Terminate script execution
    } else {
        // Handle SQL query execution error
        $_SESSION['error'] = "Failed to assign task: " . mysqli_error($con);
        // Redirect to the same page after submission
        header("location:users_list.php");
        exit; // Terminate script execution
    }
}
?>
<div class="container mt-2">
    <form action="ass_emp.php" method="POST">
        <div class="row m-2 p-3 register_form border border-secondary">
            <h5 class="text-center">Assign Task</h5>
            <div class="col-md-5 m-auto">
                <div class="mb-2">
                    <label>Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" value="<?= $result['id'] ?>" readonly>
                </div>
            </div>
            <div class="col-md-5 m-auto">
                <div class="mb-2">
                    <label>Task</label>
                    <textarea class="form-control" name="user_res" rows="4" required>
                    <?= $result['user_res'] ?>
                    </textarea>
                </div>
                <div class="mb-2">
                    <button class="btn btn-primary" name="assign_task">Assign Task</button>
                    <a href="users_list.php" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?> 
