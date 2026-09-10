<?php
include '../header.php'; // Ensure your session is included

// Check if the form was submitted and if the file is present
if (isset($_POST['submit']) && isset($_FILES['profileImage'])) {
    $file = $_FILES['profileImage'];

    // Debugging the file upload details
    echo "<pre>";
    echo "File details: ";
    print_r($file);  // Debugging file array details (this will show name, tmp_name, size, error code, type, etc.)
    echo "</pre>";

    // Get the file details
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // Allowable file types (you can adjust these as needed)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

    // Debugging: Check if file type is allowed
    echo "File type: " . $fileType . "<br>";

    // Check if the file type is valid
    if (in_array($fileType, $allowedTypes)) {
        // Check for errors in the file upload process
        if ($fileError === 0) {
            // Check for image dimensions (500x500 limit)
            list($width, $height) = getimagesize($fileTmpName);
            echo "Image dimensions: $width x $height<br>";  // Debugging image dimensions

            if ($width <= 500 && $height <= 500) {
                // Generate a unique name for the file to prevent overwriting
                $uniqueFileName = uniqid('', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);

                // Specify the upload directory (relative to the location of my_profile.php)
                $uploadDir = 'uploads/'; // Relative path to the uploads folder

                // Make sure the directory exists, and if not, create it
                if (!is_dir($uploadDir)) {
                    echo "Directory does not exist, creating it...<br>"; // Debugging directory creation
                    mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
                } else {
                    echo "Directory exists.<br>"; // Debugging: check if the directory already exists
                }

                // Define the file destination path
                $fileDestination = $uploadDir . $uniqueFileName;

                // Move the uploaded file to the destination
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Get the user ID from session
                    $userId = $_SESSION['u_data'][5]; // Assuming user ID is stored in session at index 5

                    // Update the database with the new profile photo path (store the relative path)
                    $filePath = 'uploads/' . $uniqueFileName;

                    echo "File path: " . $filePath . "<br>";  // Debugging file path

                    $sql = "UPDATE users_tbl SET profile_photo = '$filePath' WHERE id = $userId";

                    if (mysqli_query($con, $sql)) {
                        $_SESSION['msg'] = "Profile photo uploaded successfully!";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['msg'] = "Error updating profile photo in the database.";
                        $_SESSION['msg_type'] = "error";
                        echo "Database update error: " . mysqli_error($con);  // Debugging database error
                    }
                } else {
                    $_SESSION['msg'] = "Error moving the uploaded file.";
                    $_SESSION['msg_type'] = "error";
                    echo "Error moving uploaded file. Check permissions on uploads folder.<br>";  // Debugging move error
                }
            } else {
                $_SESSION['msg'] = "Image must be 500x500 or smaller.";
                $_SESSION['msg_type'] = "error";
                echo "Image is too large: $width x $height<br>";  // Debugging image size issue
            }
        } else {
            $_SESSION['msg'] = "There was an error uploading your file. Error code: $fileError";
            $_SESSION['msg_type'] = "error";
            echo "File upload error: $fileError<br>";  // Debugging error code
        }
    } else {
        $_SESSION['msg'] = "Invalid file type. Only JPG, PNG, or GIF are allowed.";
        $_SESSION['msg_type'] = "error";
        echo "Invalid file type: " . $fileType . "<br>";  // Debugging invalid file type
    }
}

// Redirect back to the profile page after upload
header("Location: my_profile.php");
exit();
?>
