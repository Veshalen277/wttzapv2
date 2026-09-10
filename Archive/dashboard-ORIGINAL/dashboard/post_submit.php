<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

$logged_in_user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

    if ($logged_in_user_id > 0 && $message_text !== '') {

        // optional: get user_scale from users_tbl, or just use 'staff'
        $user_scale = 'staff';
        $scale_sql = "SELECT user_scale FROM users_tbl WHERE id = $logged_in_user_id LIMIT 1";
        $scale_res = mysqli_query($con, $scale_sql);
        if ($scale_res && mysqli_num_rows($scale_res) > 0) {
            $row = mysqli_fetch_assoc($scale_res);
            if (!empty($row['user_scale'])) {
                $user_scale = $row['user_scale'];
            }
        }

        // insert post
        $msg = mysqli_real_escape_string($con, $message_text);
        $scale = mysqli_real_escape_string($con, $user_scale);

        $insert_sql = "
            INSERT INTO messages (user_id, message_text, posted_at, user_scale)
            VALUES ($logged_in_user_id, '$msg', NOW(), '$scale')
        ";

        if (!mysqli_query($con, $insert_sql)) {
            die("Insert error: " . mysqli_error($con));
        }
    }
}

// header("Location: feed.php");
// exit;
$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '/dashboard/employee/emp_profile.php');
header('Location: ' . $redirect);
exit;

