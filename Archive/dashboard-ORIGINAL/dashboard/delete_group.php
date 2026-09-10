<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_id'])) {
    $group_id = intval($_POST['group_id']);

    $stmt = $con->prepare("DELETE FROM group_members WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare("DELETE FROM groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Group deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error deleting group: " . $stmt->error;
        $_SESSION['msg_type'] = "error";
    }

    $stmt->close();
    header("Location: view_groups.php");
    exit();
}
?>
