<?php
include '../header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $item_name = htmlspecialchars(trim($_POST['item_name']));
    $category_id = intval($_POST['category_id']); // Ensures category_id is an integer

    // Check if fields are not empty
    if (!empty($item_name) && $category_id > 0) {
        // Insert into stock_items table
        $sql = "INSERT INTO sc_stock_items (item_name, category_id) VALUES (?, ?)";
        
        // Prepare and bind parameters
        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $item_name, $category_id);
            
            // Execute the query
            if (mysqli_stmt_execute($stmt)) {
                echo "Item added successfully.";
                
                // Redirect to add_stock.php after 2 seconds (optional)
                header("refresh:2;url=add_stock.php");
                exit(); // Ensure no further output is sent
            } else {
                echo "Error: " . mysqli_error($con);
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            echo "Error preparing statement: " . mysqli_error($con);
        }
    } else {
        echo "Please fill in all fields.";
    }
}

// Close the database connection
mysqli_close($con);

include '../footer.php';
?>
