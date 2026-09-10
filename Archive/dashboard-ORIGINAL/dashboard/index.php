<?php
// Route before rendering HTML or opening a database connection.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['u_data'])) {
    header('Location: ../index.php');
    exit;
}

$role = (int) ($_SESSION['u_data'][4] ?? -1);
$destination = $role === 0
    ? 'admin/admin_profile.php'
    : 'employee/emp_profile.php';

header('Location: ' . $destination);
exit;
