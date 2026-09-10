<?php
include 'header.php'; // Ensure this file has your $con connection object

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form values
    $report_id = $_POST['report_id'];
    $report_date = $_POST['report_date'];
    $user_id = $_POST['user_id']; // This should be validated or removed if not used
    $user_dept = $_POST['user_dept'];
    $income_cash = $_POST['income_cash'];
    $income_card = $_POST['income_card'];
    $income_other = $_POST['income_other'];
    $expense_cash = $_POST['expense_cash'];
    $airtime = $_POST['airtime'];
    $notes = $_POST['notes'];

    // Calculate totals
    $total_income = $income_cash + $income_card + $income_other;
    $total_expenses = $expense_cash + $airtime;
    $net_total = $total_income - $total_expenses;

    // Prepare SQL statement
    $sql = "UPDATE reports SET 
                report_date = ?, 
                user_dept = ?, 
                income_cash = ?, 
                income_card = ?, 
                income_other = ?, 
                total_income = ?, 
                expense_cash = ?, 
                airtime = ?, 
                total_expenses = ?, 
                net_total = ?, 
                notes = ? 
            WHERE id = ?";

    $stmt = $con->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param(
            "ssddddddddds", 
            $report_date, 
            $user_dept, 
            $income_cash, 
            $income_card, 
            $income_other, 
            $total_income, 
            $expense_cash, 
            $airtime, 
            $total_expenses, 
            $net_total, 
            $notes, 
            $report_id
        );

        if ($stmt->execute()) {
            header("Location: view_income_reports.php");
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error updating report: ' . htmlspecialchars($con->error) . '</div>';
        }

        $stmt->close();
    } else {
        echo '<div class="alert alert-danger mt-3" role="alert">Failed to prepare the SQL statement.</div>';
    }
} else {
    header("Location: view_reports.php?message=Invalid request");
}
?>
