<?php

include '../header.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and cast form data
    $report_date   = $_POST['report_date'];
    $user_id       = (int) $_POST['user_id'];
    $user_dept     = (int) $_POST['user_dept'];
    $income_cash   = (float) $_POST['income_cash'];
    $income_card   = (float) $_POST['income_card'];
    $income_other  = (float) $_POST['income_other'];
    $expense_cash  = (float) $_POST['expense_cash'];
    $airtime       = (float) $_POST['airtime'];
    $notes         = $_POST['notes'];

    // Calculate totals
    $total_income   = $income_cash + $income_card + $income_other;
    $total_expenses = $expense_cash + $airtime;
    $net_total      = $total_income - $total_expenses;

    // Prepare statement
    $stmt = $con->prepare("INSERT INTO reports 
        (report_date, user_id, user_dept, income_cash, income_card, income_other, total_income, 
         expense_cash, airtime, notes, total_expenses, net_total) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sissdddddsdd", 
        $report_date, $user_id, $user_dept, $income_cash, $income_card, $income_other, 
        $total_income, $expense_cash, $airtime, $notes, $total_expenses, $net_total);

    // Execute and redirect
    if ($stmt->execute()) {
        header("Location: report_form.php?message=Report submitted successfully");
        exit;
    } else {
        $error_message = urlencode("Error submitting report: " . $stmt->error);
        header("Location: report_form.php?error=$error_message");
        exit;
    }

    // Clean up
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>
