<?php
include '../header.php'; // Ensure this includes the database connection

$error_message = "";
$success_message = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the category
    $sql = "DELETE FROM sc_stock_categories WHERE category_id = ?";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Category deleted successfully.";
        } else {
            $error_message = "Error deleting category: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $con->error;
    }
}

// Redirect back with success/error message
header("Location: sc_manage_categories.php?success_message=" . urlencode($success_message) . "&error_message=" . urlencode($error_message));
exit();

include '../footer.php';
?>
