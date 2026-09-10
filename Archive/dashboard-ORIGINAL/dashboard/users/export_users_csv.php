<?php
include '../config.php'; // Include your database connection file

// Fetch all users
$sql = "SELECT * FROM users_tbl";
$query = mysqli_query($con, $sql);

// Set the headers for CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="all_users.csv"');

// Open the output stream
$output = fopen('php://output', 'w');

// Add the column headers
fputcsv($output, array('Sr.#', 'Employee', 'Designation', 'Dept.', 'Role', 'Start Date', 'ID No.', 'Email', 'Addr.', 'Contact No.', 'Next of Kin', 'Next of Kin No.'));

// Fetch and write the data
$count = 1;
while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, array(
        $count,
        $row['fullname'],
        $row['user_des'],
        $row['user_scale'],
        $row['user_role'],
        $row['date_started'],
        $row['id_number'],
        $row['email'],
        $row['address'],
        $row['contact_number'],
        $row['next_of_kin'],
        $row['next_of_kin_number']
    ));
    $count++;
}

// Close the output stream
fclose($output);
?>
