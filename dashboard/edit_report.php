<?php
include 'header.php'; // Ensure this file has your $con connection object

// Fetch the report data to be edited
if (isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    
    $sql = "SELECT * FROM reports WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    
    if (!$report) {
        echo '<div class="alert alert-danger mt-3" role="alert">Report not found.</div>';
        exit;
    }
} else {
    echo '<div class="alert alert-danger mt-3" role="alert">No report ID provided.</div>';
    exit;
}
?>

<div class="container mt-4">
    <h4>Edit Report</h4>
    <form action="update_report.php" method="POST">
        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
        <label for="report_date">Date:</label>
        <input type="date" id="report_date" name="report_date" value="<?php echo $report['report_date']; ?>" required><br><br>
        <input type="hidden" id="user_id" name="user_id" value="<?php echo $report['user_id']; ?>" readonly>
        <label for="user_dept">Department:</label>
        <input type="text" id="user_dept" name="user_dept" value="<?php echo $report['user_dept']; ?>" required><br><br>

        <label for="income_cash">Income via Cash:</label>
        <input type="number" id="income_cash" name="income_cash" step="0.01" value="<?php echo $report['income_cash']; ?>" required><br><br>

        <label for="income_card">Income via Card:</label>
        <input type="number" id="income_card" name="income_card" step="0.01" value="<?php echo $report['income_card']; ?>" required><br><br>

        <label for="income_other">Sundry income:</label>
        <input type="number" id="income_other" name="income_other" step="0.01" value="<?php echo $report['income_other']; ?>" required><br><br>

        <label for="expense_cash">Expenses via Cash:</label>
        <input type="number" id="expense_cash" name="expense_cash" step="0.01" value="<?php echo $report['expense_cash']; ?>" required><br><br>

        <label for="airtime">Airtime:</label>
        <input type="number" id="airtime" name="airtime" step="5.00" value="<?php echo $report['airtime']; ?>" required><br><br>

        <label for="notes">Notes:</label>
        <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($report['notes']); ?></textarea><br><br>

        <input type="submit" class="btn btn-primary" value="Update Report">
    </form>
</div>

<?php include 'footer.php'; ?>
