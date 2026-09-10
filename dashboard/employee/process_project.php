<!-- </?php
include '../header.php';
// Start the sessionsession_start();
include '../config.php'; // Ensure this includes your database connection

if (!isset($_SESSION["u_data"]) || !isset($_SESSION["u_data"][5])) {
    die("User not logged in or session data is incomplete.");
}

$user_id = $_SESSION["u_data"][5]; // Retrieve the user ID from the session array

// Validate user existence
$user_check_sql = "SELECT id FROM users_tbl WHERE id = ?";
$stmt = $con->prepare($user_check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute(); 
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: The user with ID $user_id does not exist in the database.");
}

// Proceed with project creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = $_POST['project_name'];
    $project_description = $_POST['project_description'];
    $users = $_POST['users']; // Array of user IDs to associate with the project

    // Insert the project into the projects table
    $insert_project_sql = "INSERT INTO projects_tbl (project_name, project_description, created_by) VALUES (?, ?, ?)";
    $stmt = $con->prepare($insert_project_sql);
    $stmt->bind_param("ssi", $project_name, $project_description, $user_id);
    $stmt->execute();

    $project_id = $stmt->insert_id; // Get the ID of the newly created project

    // Associate each selected user with the project
    foreach ($users as $user_id) {
        $insert_user_project_sql = "INSERT INTO user_project (user_id, project_id) VALUES (?, ?)";
        $user_project_stmt = $con->prepare($insert_user_project_sql);
        $user_project_stmt->bind_param("ii", $user_id, $project_id);
        $user_project_stmt->execute();
    }

    header("Location: project_dashboard.php");
    exit();
}
?> -->
<?php
session_start();
include '../config.php'; // Database connection

if (!isset($_SESSION["u_data"]) || !isset($_SESSION["u_data"][5])) {
    die("User not logged in or session data is incomplete.");
}

$user_id = $_SESSION["u_data"][5]; // Retrieve the user ID from the session array

// Validate user existence
$user_check_sql = "SELECT id FROM users_tbl WHERE id = ?";
$stmt = $con->prepare($user_check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: The user with ID $user_id does not exist in the database.");
}

// Proceed with project creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = $_POST['project_name'];
    $project_description = $_POST['project_description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $budget = $_POST['budget'];
    $users = isset($_POST['users']) ? $_POST['users'] : []; // Ensure users is set to an empty array if not provided

    // Insert the project into the projects table
    $insert_project_sql = "INSERT INTO projects_tbl (project_name, project_description, start_date, end_date, status, priority, budget, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($insert_project_sql);
    $stmt->bind_param("ssssssdi", $project_name, $project_description, $start_date, $end_date, $status, $priority, $budget, $user_id);

    if ($stmt->execute()) {
        $project_id = $stmt->insert_id; // Get the ID of the newly created project

        // Associate each selected user with the project
        foreach ($users as $user_id) {
            $insert_user_project_sql = "INSERT INTO user_project (user_id, project_id) VALUES (?, ?)";
            $user_project_stmt = $con->prepare($insert_user_project_sql);
            $user_project_stmt->bind_param("ii", $user_id, $project_id);
            $user_project_stmt->execute();
        }

        header("Location: project_dashboard.php");
        exit();
    } else {
        // Handle the error
        die("Error: " . $stmt->error);
    }
}
?>
