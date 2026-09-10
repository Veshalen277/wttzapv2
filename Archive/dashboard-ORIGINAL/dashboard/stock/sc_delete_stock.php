<?php
include '../header.php';

// Check if the item ID is set and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid item ID.</div>';
    include 'footer.php';
    exit();
}

$item_id = intval($_GET['id']);

// Prepare to delete the item
$sql = "DELETE FROM sc_stock_items WHERE item_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $item_id);

if ($stmt->execute()) {
    echo '<div class="alert alert-success">Item deleted successfully.</div>';
    header("Location: https://wttzap.co.za/dashboard/stock/sc_manage_categories.php");
 
    exit;
} else {
    echo '<div class="alert alert-danger">Error deleting item.</div>';
    header("Location: https://wttzap.co.za/dashboard/stock/sc_manage_categories.php");
 
exit;
}

$stmt->close();
include '../footer.php';
?>
