<?php
include 'header.php'; // Ensure this file contains your database connection details and establishes $con as the MySQLi connection

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure $_POST values are sanitized or validated as needed
    $cash_sales = $_POST['cash_sales'];
    $card_sales = $_POST['card_sales'];
    $total_sales = $_POST['total_sales'];
    $airtime = $_POST['airtime'];
    $cash = $_POST['cash'];
    $expenses = $_POST['expenses'];
    $notes = $_POST['notes'];
    $wtt_expenses = $_POST['wtt_expenses'];
    $coffee_sold = $_POST['coffee_sold'];

    // Prepare SQL statement to insert data into smoking_coffee_monthly_report table
    $stmt = $con->prepare("INSERT INTO smoking_coffee_monthly_report (cash_sales, card_sales, total_sales, airtime, cash, expenses, notes, wtt_expenses, coffee_sold) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters
    $stmt->bind_param("dddddddsd", $cash_sales, $card_sales, $total_sales, $airtime, $cash, $expenses, $notes, $wtt_expenses, $coffee_sold);

    // Execute the SQL statement
    if ($stmt->execute()) {
        echo "Monthly report submitted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
}

   // Close connection
    $con->close();

    // Redirect to index.php after processing
    header('Location: index.php');
    exit; // Ensure script execution stops after redirection
?>
