<?php

include 'config.php';

// Function to generate report (replace with your actual report generation logic)
function generateReport($date) {
    // Query to fetch report data based on the date
    // Replace this with your actual query
    $query = "SELECT * FROM work_tbl WHERE work_date = '$date'";
    $result = mysqli_query($con, $query);
    
    // Create a report (this could be a CSV, HTML, etc.)
    $report = "Task Report for " . $date . "\n\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $report .= "Task: " . $row['task_name'] . " - Status: " . $row['status'] . "\n";
    }
    return $report;
}

// Function to send email
function sendEmail($report) {
    $to = "admin@timefliesza.co.za";
    $subject = "Daily Work Report";
    $headers = "From: info@wttzap.co.za"; // Change to a valid email
    
    // Mail the report
    mail($to, $subject, $report, $headers);
}

// Get the date for yesterday
$date = date('Y-m-d', strtotime('-1 day'));

// Generate the report and send it
$report = generateReport($date);
sendEmail($report);
?>
