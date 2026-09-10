<?php
include '../config.php'; // Include your database connection

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="stock_items_with_categories.csv"');

$output = fopen('php://output', 'w');

// Add CSV header
fputcsv($output, [
    'Item ID', 
    'Item Name', 
    'Category Name', 
    'Quantity On Hand', 
    'Order Quantity', 
    'Received Quantity', 
    'Cost Price', 
    'Retail Price', 
    'Description', 
    'Supplier'
]);

// Query to get all items with category names instead of IDs
$sql = "
    SELECT
        si.item_id,
        si.item_name,
        sc.category_name,
        si.qty_on_hand,
        si.order_qty,
        si.received_qty,
        si.cost_price,
        si.retail_price,
        si.description,
        si.supplier
    FROM
        sc_stock_items si
    LEFT JOIN
        sc_stock_categories sc ON si.category_id = sc.category_id
    ORDER BY
        sc.category_name, si.item_id
";

$result = $con->query($sql);

if ($result) {
    // Fetch data and write to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>
