<?php
include '../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $assigned_to = $_POST['assigned_to'];
    $assigned_by = $_POST['assigned_by'];
    $due_date = $_POST['due_date'];

    // Update the task
    $sql = "UPDATE advanced_tasks_tbl 
            SET assigned_to = ?, assigned_by = ?, due_date = ?, status = 'assigned' 
            WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iisi", $assigned_to, $assigned_by, $due_date, $task_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Task successfully assigned!";
    } else {
        $_SESSION['error'] = "Error assigning task: " . $stmt->error;
    }

    header("Location: admin_tasks.php"); // change to your admin page
    exit();
}
?>
