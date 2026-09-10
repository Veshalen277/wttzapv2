<?php
include 'config.php'; // Database connection

/* Check user role
if ($role != 2) {
    header("location:/dashboard/404.php");
    exit;
}
*/
include 'functions.php';

$date_filter = isset($_POST['date']) ? $_POST['date'] : '';
$task_filter = isset($_POST['task']) ? $_POST['task'] : '';

// Fetch the data to export
$result = fetchWorkRecords(0, PHP_INT_MAX, $date_filter, $task_filter, $con);

if (!$result) {
    echo "Error: " . mysqli_error($con);
    exit;
}

// Set headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="work_records.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headings
fputcsv($output, ['Employee ID', 'Work Description', 'Work Date', 'Assigned To', 'Department', 'Task', 'Task Date', 'Time Taken']);

// Output data rows
while ($row = mysqli_fetch_assoc($result)) {
    $work_date = $row['work_date'];
    $task_date = $row['user_task_datetime'];

    $time_taken = 'N/A';
    if ($work_date && $task_date) {
        $work_date_obj = new DateTime($work_date);
        $task_date_obj = new DateTime($task_date);
        $interval = $work_date_obj->diff($task_date_obj);
        $time_taken = $interval->format('%d days, %h hours, %i minutes');
    }

    fputcsv($output, [
        $row['employee_id'],
        $row['work_desc'],
        $row['work_date'],
        $row['assigned_to'],
        $row['department'],
        $row['task'],
        $row['user_task_datetime'],
        $time_taken
    ]);
}

// Close output stream
fclose($output);
exit;
?>
