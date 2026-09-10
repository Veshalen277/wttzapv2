<?php
// Include database connection
include 'config.php';

// Set the content type to CSV and force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=orders_data.csv');

// Open output stream for CSV
$output = fopen('php://output', 'w');

// Output the column headers
fputcsv($output, array('Full Name', 'Product', 'Quantity', 'Department', 'Submitted At'));

// Fetch orders data
$sql = "SELECT fullname, product, quantity, department, submitted_at FROM orders";
$result = $con->query($sql);

// Output the rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Close output stream
fclose($output);

// Close database connection
$con->close();
exit();
?>
