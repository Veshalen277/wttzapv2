<?php
include '../header.php';
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $name = mysqli_real_escape_string($con, $_POST['project_name']);
    $description = mysqli_real_escape_string($con, $_POST['project_description']);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $budget = $_POST['budget'] ?? 0.00;
    $users = $_POST['users'] ?? [];

    // Update project
    $update_sql = "
        UPDATE projects_tbl SET
            project_name = ?,
            project_description = ?,
            start_date = ?,
            end_date = ?,
            status = ?,
            priority = ?,
            budget = ?
        WHERE project_id = ?
    ";

    $stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($stmt, 'ssssssdi', $name, $description, $start_date, $end_date, $status, $priority, $budget, $project_id);
    mysqli_stmt_execute($stmt);

    // Clear existing associations
    mysqli_query($con, "DELETE FROM project_users_tbl WHERE project_id = $project_id");

    // Add new associations
    foreach ($users as $user_id) {
        $user_id = (int)$user_id;
        mysqli_query($con, "INSERT INTO project_users_tbl (project_id, user_id) VALUES ($project_id, $user_id)");
    }

    header("Location: project_dashboard.php");
    exit;
}
?>
