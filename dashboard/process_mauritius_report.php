<?php
include 'header.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $report_date = $_POST['report_date'];
    $user_id = $_POST['user_id'];
    $user_dept = $_POST['user_dept'];
    $income_cash = $_POST['income_cash'];
    $income_card = $_POST['income_card'];
    $blinq = $_POST['blinq'];
    $expense_cash = $_POST['expense_cash'];
    $airtime = $_POST['airtime'];
    $notes = $_POST['notes']; // Notes field

    // Calculate total income and total expenses
    $total_income = $income_cash + $income_card + $blinq;
    $total_expenses = $expense_cash + $airtime;

    // Calculate net total
    $net_total = $total_income - $total_expenses;

    // Prepare and bind
    $stmt = $con->prepare("
        INSERT INTO mauritius_reports 
        (report_date, user_id, user_dept, income_cash, income_card, blinq, total_income, 
         expense_cash, airtime, total_expenses, net_total, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiiiddddsdd", 
                      $report_date, $user_id, $user_dept, $income_cash, $income_card, $blinq, 
                      $total_income, $expense_cash, $airtime, $total_expenses, $net_total, $notes);

    // Execute the statement
    if ($stmt->execute()) {
        echo "New report created successfully";
        header("Location: view_tfm_reports.php"); // Adjust the redirect URL as needed
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>
