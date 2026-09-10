<?php

include '../header.php';


// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input values
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $item_name = isset($_POST['item_name']) ? mysqli_real_escape_string($con, trim($_POST['item_name'])) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $cost_price = isset($_POST['cost_price']) ? floatval($_POST['cost_price']) : 0;
    $retail_price = isset($_POST['retail_price']) ? floatval($_POST['retail_price']) : 0;
    $qty_on_hand = isset($_POST['qty_on_hand']) ? intval($_POST['qty_on_hand']) : 0;
    $order_qty = isset($_POST['order_qty']) ? intval($_POST['order_qty']) : 0;
    $received_qty = isset($_POST['received_qty']) ? intval($_POST['received_qty']) : 0;
    $description = isset($_POST['description']) ? mysqli_real_escape_string($con, trim($_POST['description'])) : '';
    $supplier = isset($_POST['supplier']) ? mysqli_real_escape_string($con, trim($_POST['supplier'])) : '';

    // Validate required fields
    if ($id <= 0 || empty($item_name) || $category_id <= 0 || $qty_on_hand < 0 || $order_qty < 0 || $received_qty < 0) {
        echo "Invalid input. Please check the form values and try again.";
        exit;
    }

    // Debugging: Print out the variables to check their values
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Update the stock item in the database
    $sql = "UPDATE sc_stock_items 
            SET item_name = '$item_name', 
                category_id = $category_id, 
                cost_price = $cost_price, 
                retail_price = $retail_price, 
                qty_on_hand = $qty_on_hand, 
                order_qty = $order_qty, 
                received_qty = $received_qty, 
                description = '$description', 
                supplier = '$supplier' 
            WHERE item_id = $id";

    if (mysqli_query($con, $sql)) {
        // Redirect to a success page or back to the stock items list
        header("Location: sc_manage_categories.php"); // Update this to the correct path of your stock list page
        exit;
    } else {
        // Print SQL error for debugging
        echo "Error updating record: " . mysqli_error($con);
    }
} else {
    echo "Invalid request method.";
}

?>
