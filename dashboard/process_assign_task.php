<!-- </?php
include 'config.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare and bind parameters
    $stmt = $con->prepare("INSERT INTO tasks (task_name, task_description, user_id, priority, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $task_name, $task_description, $user_id, $priority, $deadline);

    // Retrieve form data
    $task_name = $_POST['task_name'];
    $task_description = $_POST['task_description'];
    $user_id = $_POST['user_id']; // Assuming the user ID is named 'employee_id'
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];

    // Execute the statement
    if ($stmt->execute()) {
        echo "Task assigned successfully";
    } else {
        echo "Error: Unable to assign task";
    }

    // Close statement
    $stmt->close();
}

// Close connection
$con->close();
?> -->
<?php
include 'config.php'; // Assuming this file contains your database connection
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $user_id = $_POST['user_id'];
    $task_description = $_POST['task_description'];

    // Sanitize data (for basic protection against SQL injection)
    $user_id = mysqli_real_escape_string($con, $user_id);
    $task_description = mysqli_real_escape_string($con, $task_description);

    // Update task description in database
    $sql = "UPDATE user_tbl SET user_res='$task_description' WHERE user_id='$user_id'";

    if (mysqli_query($con, $sql)) {
        echo "Task description updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
    }
}

include 'footer.php';
?>
