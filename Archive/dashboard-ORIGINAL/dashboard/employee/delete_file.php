<?php
include '../header.php';
include '../config.php'; // Ensure this includes your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file_id and project_id are set
    if (!isset($_POST['file_id']) || !isset($_POST['project_id'])) {
        die("Error: File ID or Project ID not provided.");
    }

    $file_id = $_POST['file_id'];
    $project_id = $_POST['project_id'];

    // Fetch file information from the database
    $file_sql = "SELECT file_path FROM project_message_files WHERE file_id = ?";
    $stmt = $con->prepare($file_sql);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $file_result = $stmt->get_result();

    if ($file_result->num_rows === 0) {
        die("Error: File not found in the database.");
    }

    $file = $file_result->fetch_assoc();

    // Delete the file from the server
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }

    // Delete the file record from the database
    $delete_file_sql = "DELETE FROM project_message_files WHERE file_id = ?";
    $stmt = $con->prepare($delete_file_sql);
    $stmt->bind_param("i", $file_id);

    if ($stmt->execute()) {
        // Redirect back to the project messages page
        header("Location: project_messages.php?project_id=$project_id");
        exit();
    } else {
        // Handle the error
        die("Error: " . $stmt->error);
    }
}
?>
