<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $task_description = trim($_POST['task_description']);

    if ($user_id && $task_description) {
        $stmt = mysqli_prepare($con, "INSERT INTO adv_user_tasks_tbl (user_id, task_description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $task_description);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Task assigned successfully.";
        } else {
            $_SESSION['error'] = "Database error: " . mysqli_error($con);
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    header("Location: admin_tasks.php"); // or your current page
    exit();
}
?>
