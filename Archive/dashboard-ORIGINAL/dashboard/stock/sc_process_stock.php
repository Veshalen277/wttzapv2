<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../header.php';

// Initialize variables
$item_name = $category_id = $cost_price = $retail_price = $qty_on_hand = $order_qty = $received_qty = $description = $supplier = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and validate POST data
    $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : null;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $cost_price = isset($_POST['cost_price']) ? floatval($_POST['cost_price']) : null;
    $retail_price = isset($_POST['retail_price']) ? floatval($_POST['retail_price']) : null;
    $qty_on_hand = isset($_POST['qty_on_hand']) ? intval($_POST['qty_on_hand']) : null;
    $order_qty = isset($_POST['order_qty']) ? intval($_POST['order_qty']) : null;
    $received_qty = isset($_POST['received_qty']) ? intval($_POST['received_qty']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : null;

    // Check if all required fields are provided
    if ($item_name && $category_id !== null && $cost_price !== null && $retail_price !== null && $qty_on_hand !== null && $order_qty !== null && $received_qty !== null) {

        // Prepare SQL statement to check if item exists
        $check_sql = "SELECT item_id FROM sc_stock_items WHERE item_name = ? AND category_id = ?";

        if ($check_stmt = mysqli_prepare($con, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, 'si', $item_name, $category_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                // Item exists, update it
                $update_sql = "UPDATE sc_stock_items SET cost_price = ?, retail_price = ?, qty_on_hand = ?, order_qty = ?, received_qty = ?, description = ?, supplier = ? WHERE item_name = ? AND category_id = ?";

                if ($update_stmt = mysqli_prepare($con, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, 'ddiiiisss', $cost_price, $retail_price, $qty_on_hand, $order_qty, $received_qty, $description, $supplier, $item_name, $category_id);

                    try {
                        if (mysqli_stmt_execute($update_stmt)) {
                            $_SESSION['message'] = "Stock item updated successfully.";
                            $_SESSION['message_type'] = "success";
                        } else {
                            throw new Exception("Error executing update: " . mysqli_stmt_error($update_stmt));
                        }
                    } catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        $_SESSION['message_type'] = "error";
                    }

                    mysqli_stmt_close($update_stmt);
                } else {
                    $_SESSION['message'] = "Error preparing update statement: " . mysqli_error($con);
                    $_SESSION['message_type'] = "error";
                }
            } else {
                // Item does not exist, insert it
                $insert_sql = "INSERT INTO sc_stock_items (item_name, category_id, cost_price, retail_price, qty_on_hand, order_qty, received_qty, description, supplier) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                if ($insert_stmt = mysqli_prepare($con, $insert_sql)) {
                    mysqli_stmt_bind_param($insert_stmt, 'siddiiiss', $item_name, $category_id, $cost_price, $retail_price, $qty_on_hand, $order_qty, $received_qty, $description, $supplier);

                    try {
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $_SESSION['message'] = "New stock item added successfully.";
                            $_SESSION['message_type'] = "success";
                        } else {
                            throw new Exception("Error executing insert: " . mysqli_stmt_error($insert_stmt));
                        }
                    } catch (Exception $e) {
                        if (mysqli_errno($con) == 1062) { // Duplicate entry error code
                            $_SESSION['message'] = "This item is already included in the inventory.";
                        } else {
                            $_SESSION['message'] = $e->getMessage();
                        }
                        $_SESSION['message_type'] = "error";
                    }

                    mysqli_stmt_close($insert_stmt);
                } else {
                    $_SESSION['message'] = "Error preparing insert statement: " . mysqli_error($con);
                    $_SESSION['message_type'] = "error";
                }
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $_SESSION['message'] = "Error preparing check statement: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Please ensure all fields are filled in.";
        $_SESSION['message_type'] = "error";
    }

    // Close the database connection
    mysqli_close($con);

    // Redirect back to the add stock page with the selected category
    header("Location: sc_add_stock.php?category_id=$category_id&message=" . urlencode($_SESSION['message']) . "&message_type=" . urlencode($_SESSION['message_type']));
    exit;
}
?>
