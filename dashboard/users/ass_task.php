<?php 
include '../header.php';

// Assuming $con is a valid database connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT * FROM users_tbl WHERE id=$id";
$query = mysqli_query($con, $sql);
$result = mysqli_fetch_assoc($query);

if (!$result) {
    echo "User not found.";
    exit;
}

// if (isset($_POST['assign_task'])) {
//     $task_description = mysqli_real_escape_string($con, $_POST['task_description']);

//     $update = "UPDATE users_tbl SET user_res='$task_description' WHERE id='$id'";
//     $query = mysqli_query($con, $update);

//     if ($query) {
//         $_SESSION['update'] = "Task assigned successfully!";
//         header("location:users_list.php");
//     } else {
//         echo "Error: " . mysqli_error($con);
//     }
// }
if (isset($_POST['assign_task'])) {
    $task_description = mysqli_real_escape_string($con, $_POST['task_description']);
    
    // Get the current date and time
    $task_datetime = date('Y-m-d H:i:s');

    $update = "UPDATE users_tbl SET user_res='$task_description', task_datetime='$task_datetime' WHERE id='$id'";
    $query = mysqli_query($con, $update);

    if ($query) {
        $_SESSION['update'] = "Task assigned successfully!";
        header("location: users_list.php");
        exit; // Always exit after header redirect
    } else {
        echo "Error: " . mysqli_error($con);
    }
}


?>

<div class="container mt-2">
    <form action="" method="POST">
        <div class="row m-2 p-3 register_form border border-secondary">
            <h5 class="text-center">Assign Task to <?=$result['fullname']?></h5>

            <div class="col-md-10 m-auto">
                <div class="mb-2">
                    <label>Task Description:</label>
                    <textarea class="form-control" rows="6" name="task_description" required></textarea>
                </div>
            </div>

            <div class="col-md-10 m-auto text-center">
                <button class="btn btn-primary" name="assign_task">Assign Task</button> 
                <a href="users_list.php" class="btn btn-secondary">Back</a> 
            </div>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
