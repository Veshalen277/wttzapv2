<?php
include '../config.php';
include '../functions.php';
include '../vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get filter values
$date_filter = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d', strtotime('-1 day'));
$task_filter = isset($_POST['task']) ? $_POST['task'] : '';

// Fetch work records for the email report
$result = fetchWorkRecords(0, 999, $date_filter, $task_filter, $con); // Adjust pagination as needed

// Generate report content
$report = "Batch Work Report\n\n";
$report .= "Date: " . htmlspecialchars($date_filter) . "\n";
$report .= "Task: " . htmlspecialchars($task_filter) . "\n\n";

while ($row = mysqli_fetch_assoc($result)) {
    $report .= "Employee ID: " . htmlspecialchars($row['employee_id']) . "\n";
    $report .= "Work Description: " . htmlspecialchars($row['work_desc']) . "\n";
    $report .= "Work Date: " . htmlspecialchars($row['work_date']) . "\n";
    $report .= "Assigned To: " . htmlspecialchars($row['assigned_to']) . "\n";
    $report .= "Department: " . htmlspecialchars($row['department']) . "\n";
    $report .= "Task: " . htmlspecialchars($row['task']) . "\n\n";
}

// Send email
$mail = new PHPMailer(true);
try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'mail.wttzap.co.za';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@wttzap.co.za';
    $mail->Password   = '!Mv130369$';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Sender
    $mail->setFrom('info@wttzap.co.za', 'WTTZAP Batch Work Report');
    
    // Add recipient (you can customize this)
    $mail->addAddress('admin@timefliesza.co.za');

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'Batch Work Report for ' . date('Y-m-d', strtotime('-1 day'));
    $mail->Body    = $report;

    $mail->send();
    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Report emailed successfully!</div>';
} catch (Exception $e) {
    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Email could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
}

// Redirect back to the assigned work page
header("Location: assigned_work.php?date=" . urlencode($date_filter) . "&task=" . urlencode($task_filter));
exit();
