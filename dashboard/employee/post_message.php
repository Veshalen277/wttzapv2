<!-- </?php
include '../header.php';

include '../config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user_id is set in the session
    if (!isset($_SESSION['u_data']) || !isset($_SESSION['u_data'][5])) {
        die("User not logged in or session data is incomplete.");
    }

    $project_id = $_POST['project_id'];
    $message = $_POST['message'];
    $user_id = $_SESSION['u_data'][5]; // Retrieve the user ID from the session array

    // Debugging: Output the project_id
    echo "Project ID: " . $project_id . "<br>";

    // Check if the project_id exists in the projects_tbl table
    $project_check_sql = "SELECT project_id FROM projects_tbl WHERE project_id = ?";
    $stmt = $con->prepare($project_check_sql);
    if (!$stmt) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Debugging: Output the actual SQL query for manual verification
        echo "Query: SELECT project_id FROM projects_tbl WHERE project_id = $project_id<br>";
        die("Error: The project with ID $project_id does not exist in the database.");
    }

    // Use prepared statements to prevent SQL injection
    $insert_message_sql = "INSERT INTO project_messages (project_id, user_id, message) VALUES (?, ?, ?)";
    $stmt = $con->prepare($insert_message_sql);
    $stmt->bind_param("iis", $project_id, $user_id, $message); // "iis" indicates two integers and a string

    if ($stmt->execute()) {
        // Redirect to the project messages page
        header("Location: project_messages.php?project_id=$project_id");
        exit();
    } else {
        // Handle the error
        die("Error: " . $stmt->error);
    }
}



include '../footer.php';
?> -->


<?php
include '../header.php';
include '../config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user_id is set in the session
    if (!isset($_SESSION['u_data']) || !isset($_SESSION['u_data'][5])) {
        die("User not logged in or session data is incomplete.");
    }

    $project_id = $_POST['project_id'];
    $message = $_POST['message'];
    $user_id = $_SESSION['u_data'][5]; // Retrieve the user ID from the session array

    // Check if the project_id exists in the projects_tbl table
    $project_check_sql = "SELECT project_id FROM projects_tbl WHERE project_id = ?";
    $stmt = $con->prepare($project_check_sql);
    if (!$stmt) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: The project with ID $project_id does not exist in the database.");
    }

    // Use prepared statements to prevent SQL injection
    $insert_message_sql = "INSERT INTO project_messages (project_id, user_id, message) VALUES (?, ?, ?)";
    $stmt = $con->prepare($insert_message_sql);
    $stmt->bind_param("iis", $project_id, $user_id, $message);

    if ($stmt->execute()) {
        $message_id = $stmt->insert_id; // Get the ID of the newly created message

        // Handle file uploads
        if (isset($_FILES['files'])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            foreach ($_FILES['files']['name'] as $key => $name) {
                $file_name = basename($_FILES['files']['name'][$key]);
                $target_file = $target_dir . uniqid() . '_' . $file_name;

                if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target_file)) {
                    // Insert file information into the project_message_files table
                    $insert_file_sql = "INSERT INTO project_message_files (message_id, file_path, file_name) VALUES (?, ?, ?)";
                    $stmt = $con->prepare($insert_file_sql);
                    $stmt->bind_param("iss", $message_id, $target_file, $file_name);
                    $stmt->execute();
                }
            }
        }

        // Redirect to the project messages page
        header("Location: project_messages.php?project_id=$project_id");
        exit();
    } else {
        // Handle the error
        die("Error: " . $stmt->error);
    }
}
include '../footer.php';
?>
