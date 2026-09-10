<!-- </?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_name'])) {
    $group_name = mysqli_real_escape_string($con, htmlspecialchars($_POST['group_name']));
    $description = mysqli_real_escape_string($con, htmlspecialchars($_POST['description']));
    $created_by = $_SESSION['u_data'][0]; // Adjust this if your user ID is in a different session index

    $stmt = $con->prepare("INSERT INTO groups (group_name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $group_name, $description, $created_by);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Group added successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error adding group: " . $stmt->error;
        $_SESSION['msg_type'] = "error";
    }

    $stmt->close();
    header("Location: view_groups.php");
    exit();
}
?>
</?php include '../footer.php';?> -->

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_name'])) {
    $group_name = mysqli_real_escape_string($con, htmlspecialchars($_POST['group_name']));
    $description = mysqli_real_escape_string($con, htmlspecialchars($_POST['description']));
    $created_by = $_SESSION['u_data'][0]; // Adjust this if your user ID is in a different session index

    // Check if the user ID exists in the users_tbl
    $checkUser = $con->prepare("SELECT id FROM users_tbl WHERE id = ?");
    $checkUser->bind_param("i", $created_by);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['msg'] = "Error: User does not exist.";
        $_SESSION['msg_type'] = "error";
        header("Location: view_groups.php");
        exit();
    }

    // Prepare the statement to insert the new group
    $stmt = $con->prepare("INSERT INTO groups (group_name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $group_name, $description, $created_by);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Group added successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error adding group: " . $stmt->error;
        $_SESSION['msg_type'] = "error";
    }

    $stmt->close();
    header("Location: view_groups.php");
    exit();
}
?>
<?php include '../footer.php'; ?>
