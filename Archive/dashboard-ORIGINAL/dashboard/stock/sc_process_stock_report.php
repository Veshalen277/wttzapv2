<?php
include '../header.php'; // Include your header and database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and validate POST data
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $qty_on_hand = isset($_POST['qty_on_hand']) ? intval($_POST['qty_on_hand']) : null;
    $order_qty = isset($_POST['order_qty']) ? intval($_POST['order_qty']) : null;
    $received_qty = isset($_POST['received_qty']) ? intval($_POST['received_qty']) : null;

    // Check if all required fields are provided
    if ($item_id && $qty_on_hand !== null && $order_qty !== null && $received_qty !== null) {
        // Prepare SQL statement to update data
        $sql = "UPDATE sc_stock_items SET qty_on_hand = ?, order_qty = ?, received_qty = ? WHERE item_id = ?";
        if ($stmt = mysqli_prepare($con, $sql)) {
            // Bind parameters and execute the statement
            mysqli_stmt_bind_param($stmt, 'iiii', $qty_on_hand, $order_qty, $received_qty, $item_id);

            if (mysqli_stmt_execute($stmt)) {
                echo "Stock updated successfully.";
            } else {
                echo "Error: " . mysqli_stmt_error($stmt);
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        } else {
            echo "Error preparing statement: " . mysqli_error($con);
        }
    } else {
        echo "Please ensure all fields are filled in.";
    }
}

// Close the database connection
mysqli_close($con);

include '../footer.php'; // Include your footer
?>
