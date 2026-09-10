<?php
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST['item_id'];
    $qty_on_hand = $_POST['qty_on_hand'];
    $order_qty = $_POST['order_qty'];
    $received_qty = $_POST['received_qty'];

    // Update stock_items table
    $sql = "UPDATE fs_stock_items SET qty_on_hand = $qty_on_hand, order_qty = $order_qty, received_qty = $received_qty WHERE item_id = $item_id";
    if (mysqli_query($con, $sql)) {
        echo "Stock updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
    }
}

include 'footer.php';
?>
