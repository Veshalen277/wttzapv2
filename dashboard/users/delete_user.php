<?php
include '../header.php';

if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: ../404.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID!";
    header("Location: users_list.php");
    exit();
}

$user_id = intval($_GET['id']);

// Check if the user exists
$check_user = mysqli_query($con, "SELECT * FROM users_tbl WHERE id = $user_id");
if (mysqli_num_rows($check_user) == 0) {
    $_SESSION['error'] = "User not found!";
    header("Location: users_list.php");
    exit();
}

// Manually delete related records (Adjust based on your DB structure)
mysqli_query($con, "DELETE FROM leave_applications WHERE user_id = $user_id");
mysqli_query($con, "DELETE FROM acknowledgments WHERE user_id = $user_id");

mysqli_query($con, "DELETE FROM messages WHERE user_id = $user_id");
mysqli_query($con, "DELETE FROM private_messages WHERE sender_id = $user_id");
mysqli_query($con, "DELETE FROM notifications WHERE user_id = $user_id");
mysqli_query($con, "DELETE FROM suggestions WHERE user_id = $user_id");
mysqli_query($con, "DELETE FROM groups WHERE created_by = $user_id");
mysqli_query($con, "DELETE FROM group_members WHERE user_id = $user_id");
mysqli_query($con, "DELETE FROM work_tbl WHERE employee_id = $user_id");

// Now delete the user
//$delete_user = mysqli_query($con, "DELETE FROM users_tbl WHERE id = $user_id");
$delete_user = mysqli_query($con, "
    UPDATE users_tbl
    SET
        is_active = 0,
        archived_at = NOW()
    WHERE id = $user_id
");
if ($delete_user) {
$_SESSION['success'] = "Employee archived successfully.";
} else {
    $_SESSION['error'] = "Failed to delete user!";
}

header("Location: users_list.php");
exit();
?>
