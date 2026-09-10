<?php
include '../config.php'; // your DB connection
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['report_title'];
    $body = $_POST['report_body'];
    $dept = $_POST['department'] ?? '';
    $manager_id = $_POST['manager_id'];
    $visibility = $_POST['visibility_level'];
    $attachment_path = '';

    // Handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $file = $_FILES['attachment'];
        $target_dir = "../uploads/";
        $filename = time() . '_' . basename($file['name']);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $attachment_path = 'uploads/' . $filename;
        }
    }

    $sql = "INSERT INTO manager_report_tbl 
            (manager_id, report_title, report_body, department, attachment_path, visibility_level)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("issssi", $manager_id, $title, $body, $dept, $attachment_path, $visibility);

    if ($stmt->execute()) {
        $_SESSION['success'] = "<div class='alert alert-success'>Report submitted.</div>";
    } else {
        $_SESSION['error'] = "<div class='alert alert-danger'>Error submitting report.</div>";
    }

    header("Location: admin_profile.php");
    exit();
}
?>
