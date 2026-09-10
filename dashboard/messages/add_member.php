<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_id']) && isset($_POST['member_email'])) {
    $group_id = intval($_POST['group_id']);
    $member_email = mysqli_real_escape_string($con, htmlspecialchars($_POST['member_email']));

    // Check if the group ID exists
    $group_check = mysqli_query($con, "SELECT id FROM groups WHERE id = $group_id");
    if (mysqli_num_rows($group_check) == 0) {
        $_SESSION['msg'] = "Group not found. Please try again.";
        $_SESSION['msg_type'] = "error";
        header("Location: view_groups.php");
        exit();
    }

    // Check if the user exists
    $user_result = mysqli_query($con, "SELECT id FROM users_tbl WHERE email = '$member_email'");
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        $user_id = $user['id'];

        // Check if the user is already a member of the group
        $check_result = mysqli_query($con, "SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
        if (mysqli_num_rows($check_result) == 0) {
            // Add the user to the group
            $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $group_id, $user_id);

            if ($stmt->execute()) {
                $_SESSION['msg'] = "Member added successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Error adding member: " . $stmt->error;
                $_SESSION['msg_type'] = "error";
            }

            $stmt->close();
        } else {
            $_SESSION['msg'] = "User is already a member of this group.";
            $_SESSION['msg_type'] = "warning";
        }
    } else {
        $_SESSION['msg'] = "User not found.";
        $_SESSION['msg_type'] = "error";
    }

    header("Location: view_groups.php");
    exit();
}
?>
