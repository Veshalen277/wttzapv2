<!-- </?php
session_start();
include '../config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];

    // Start a transaction
    $con->begin_transaction();

    try {
        // Delete related messages first
        $delete_messages_sql = "DELETE FROM project_messages WHERE project_id = ?";
        $stmt = $con->prepare($delete_messages_sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Delete the project
        $delete_project_sql = "DELETE FROM projects_tbl WHERE project_id = ?";
        $stmt = $con->prepare($delete_project_sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Commit the transaction
        $con->commit();

        // Redirect to the project dashboard
        header("Location: project_dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $con->rollback();
        die("Error: " . $e->getMessage());
    }
}
?> -->
<?php
session_start();
include '../config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];

    // Start a transaction
    $con->begin_transaction();

    try {
        // Delete related messages first
        $delete_messages_sql = "DELETE FROM project_messages WHERE project_id = ?";
        $stmt = $con->prepare($delete_messages_sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Delete related user_project records
        $delete_user_project_sql = "DELETE FROM user_project WHERE project_id = ?";
        $stmt = $con->prepare($delete_user_project_sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Delete the project
        $delete_project_sql = "DELETE FROM projects_tbl WHERE project_id = ?";
        $stmt = $con->prepare($delete_project_sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Commit the transaction
        $con->commit();

        // Redirect to the project dashboard
        header("Location: project_dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $con->rollback();
        die("Error: " . $e->getMessage());
    }
}
?>
