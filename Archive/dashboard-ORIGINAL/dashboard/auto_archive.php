<?php
// Database connection
include 'header.php';

// Log file path (adjust as necessary)
$log_file = 'public_html/dashboard/reports/archive_reports.log';

// Function to log messages to file
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

// Function to archive reports for the previous month
function archiveReports($main_table, $archive_table) {
    global $con, $log_file;

    // Get the last day of the previous month
    $last_day_previous_month = date('Y-m-t', strtotime('last day of previous month'));

    // Archive reports
    $archive_query = "INSERT INTO $archive_table 
                        SELECT *, NOW() as archived_at 
                        FROM $main_table 
                        WHERE report_date <= '$last_day_previous_month'";

    if ($con->query($archive_query) === TRUE) {
        // Delete archived records from the main table
        $delete_query = "DELETE FROM $main_table WHERE report_date <= '$last_day_previous_month'";
        if ($con->query($delete_query)) {
            logMessage("Reports archived successfully for $main_table.");
        } else {
            logMessage("Error deleting archived records from $main_table: " . $con->error);
        }
    } else {
        logMessage("Error archiving reports for $main_table: " . $con->error);
    }
}

// Archive Smoking Coffee reports
archiveReports('smoking_coffee_daily_reports', 'smoking_coffee_daily_reports_archive');

// Archive Laletsa reports
archiveReports('laletsa_daily_reports', 'laletsa_daily_reports_archive');

// Close connection
$con->close();
?>
