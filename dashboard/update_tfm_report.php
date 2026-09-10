<?php
include 'header.php'; // Ensure this file has your $con connection object

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form values and ensure they are properly set
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $report_date = isset($_POST['report_date']) ? $_POST['report_date'] : '';
    $user_dept = isset($_POST['user_dept']) ? $_POST['user_dept'] : '';
    $income_cash = isset($_POST['income_cash']) ? floatval($_POST['income_cash']) : 0.0;
    $income_card = isset($_POST['income_card']) ? floatval($_POST['income_card']) : 0.0;
    $income_other = isset($_POST['income_other']) ? floatval($_POST['income_other']) : 0.0;
    $expense_cash = isset($_POST['expense_cash']) ? floatval($_POST['expense_cash']) : 0.0;
    $blinq = isset($_POST['blinq']) ? floatval($_POST['blinq']) : 0.0;
    $airtime = isset($_POST['airtime']) ? floatval($_POST['airtime']) : 0.0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $archive_month = isset($_POST['archive_month']) ? $_POST['archive_month'] : '';

    // Calculate totals
    $total_income = $income_cash + $income_card + $income_other;
    $total_expenses = $expense_cash + $blinq + $airtime;
    $net_total = $total_income - $total_expenses;

    // Prepare SQL statement
    $sql = "UPDATE mauritius_reports SET 
                report_date = ?, 
                user_dept = ?, 
                income_cash = ?, 
                income_card = ?, 
                income_other = ?, 
                total_income = ?, 
                expense_cash = ?, 
                blinq = ?, 
                airtime = ?, 
                total_expenses = ?, 
                net_total = ?, 
                notes = ?, 
                status = ?, 
                archive_month = ?
            WHERE id = ?";

    // Prepare the statement
    if ($stmt = $con->prepare($sql)) {
        // Check that types align with parameters
        $stmt->bind_param(
            "ssdddddddsdssi", 
            $report_date, 
            $user_dept, 
            $income_cash, 
            $income_card, 
            $income_other, 
            $total_income, 
            $expense_cash, 
            $blinq, 
            $airtime, 
            $total_expenses, 
            $net_total, 
            $notes, 
            $status, 
            $archive_month, 
            $report_id
        );

        // Execute the statement
        if ($stmt->execute()) {
            header("Location: view_mauritius_reports.php"); // Redirect to view page after success
            exit();
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error updating report: ' . htmlspecialchars($con->error) . '</div>';
        }

        // Close the statement
        $stmt->close();
    } else {
        echo '<div class="alert alert-danger mt-3" role="alert">Failed to prepare the SQL statement.</div>';
    }
} else {
    header("Location: view_mauritius_reports.php?message=Invalid request");
    exit();
}
?>
