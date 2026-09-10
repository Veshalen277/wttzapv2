<?php
include 'header.php'; // Include your database connection and other necessary files

// Initialize variables for form data and error messages
$userId = "";
$userScale = "";
$userIdErr = "";
$userScaleErr = "";
$successMessage = "";
$errorMessage = "";

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate user input
function validateInput($data) {
    if (empty($data)) {
        return false;
    }
    return true;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize user ID
    if (empty($_POST["userId"])) {
        $userIdErr = "User ID is required";
    } else {
        $userId = sanitizeInput($_POST["userId"]);
        // Validate user ID format (assuming it's an integer)
        if (!is_numeric($userId)) {
            $userIdErr = "Invalid user ID format";
        }
    }

    // Validate and sanitize user scale
    if (empty($_POST["userScale"])) {
        $userScaleErr = "User Scale is required";
    } else {
        $userScale = sanitizeInput($_POST["userScale"]);
        // Additional validation if needed
    }

    // If all inputs are valid, proceed with database update
    if (validateInput($userId) && validateInput($userScale)) {
        // Prepare SQL update statement
        $sql = "UPDATE users_tbl SET user_scale = ? WHERE id = ?";

        // Prepare and bind parameters
        $stmt = $con->prepare($sql);
        $stmt->bind_param("si", $userScale, $userId);

        // Execute the update statement
        if ($stmt->execute()) {
            $successMessage = "User scale updated successfully.";
        } else {
            $errorMessage = "Error updating user scale: " . $con->error;
        }

        // Close statement
        $stmt->close();
    }
}

// Close connection
$con->close();
?>

<!-- HTML form for updating user scale -->
<h2>Edit User Scale</h2>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
    <label for="userId">User ID:</label>
    <input type="text" id="userId" name="userId" value="<?php echo $userId; ?>" required>
    <span style="color: red;"><?php echo $userIdErr; ?></span><br><br>
    
    <label for="userScale">User Scale:</label>
    <input type="text" id="userScale" name="userScale" value="<?php echo $userScale; ?>" required>
    <span style="color: red;"><?php echo $userScaleErr; ?></span><br><br>
    
    <button type="submit">Update User Scale</button>
</form>

<!-- Display success or error message -->
<?php
if (!empty($successMessage)) {
    echo '<p style="color: green;">' . $successMessage . '</p>';
}
if (!empty($errorMessage)) {
    echo '<p style="color: red;">' . $errorMessage . '</p>';
}

include 'footer.php'; // Include your footer file
?>
