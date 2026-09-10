<?php
ob_start();
include 'header.php'; // Ensure this file has your $con connection object
if ($role!==2) {

  header("location:../dashboard/restrict.php");

}
// Function to fetch distinct user scales
function fetchUserScales($con) {
    $scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";
    $scale_query = mysqli_query($con, $scale_sql);
    return mysqli_fetch_all($scale_query, MYSQLI_ASSOC);
}

// Function to get monthly report
function getMonthlyReport($con, $year, $month) {
    $sql = "SELECT 
                MONTH(report_date) AS month,
                YEAR(report_date) AS year,
                SUM(income_cash) AS total_income_cash,
                SUM(income_card) AS total_income_card,
                SUM(income_other) AS total_income_other,
                SUM(total_income) AS total_income,
                SUM(expense_cash) AS total_expense_cash,
                SUM(expense_card) AS total_expense_card,
                SUM(total_expenses) AS total_expenses,
                SUM(net_total) AS total_net
            FROM reports
            WHERE YEAR(report_date) = ? AND MONTH(report_date) = ?
            GROUP BY month, year
            ORDER BY month";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to display monthly report
/*function displayMonthlyReport($result) {
    if ($result->num_rows > 0) {
        echo "<table class='table table-hover table-bordered bg-white'>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Income (Cash)</th>
                        <th>Total Income (Card)</th>
                        <th>Total Income (Other)</th>
                        <th>Total Income</th>
                        <th>Total Expenses (Cash)</th>
                        <th>Total Expenses (Card)</th>
                        <th>Total Expenses</th>
                        <th>Net Total</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            // Generate URL with month and year as parameters
            $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
            $url = "view_report.php?year={$row['year']}&month={$row['month']}";

            echo "<tr onclick=\"window.location.href='$url';\" style='cursor:pointer;'>
                    <td>$month_name</td>
                    <td>{$row['total_income_cash']}</td>
                    <td>{$row['total_income_card']}</td>
                    <td>{$row['total_income_other']}</td>
                    <td>{$row['total_income']}</td>
                    <td>{$row['total_expense_cash']}</td>
                    <td>{$row['total_expense_card']}</td>
                    <td>{$row['total_expenses']}</td>
                    <td>{$row['total_net']}</td>
                </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p class='mt-3'>No reports found for the selected month and year.</p>";
    }
}*/
// Function to display monthly report
function displayMonthlyReport($result) {
    if ($result->num_rows > 0) {
        echo "<table class='table table-hover table-bordered bg-white'>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Income (Cash)</th>
                        <th>Total Income (Card)</th>
                        <th>Total Income (Other)</th>
                        <th>Total Income</th>
                        <th>Total Expenses (Cash)</th>
                        <th>Total Expenses (Card)</th>
                        <th>Total Expenses</th>
                        <th>Net Total</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            // Generate URL with month and year as parameters
            $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
            $url = "view_report.php?year={$row['year']}&month={$row['month']}";

            echo "<tr>
                    <td><a href='$url'>$month_name</a></td>
                    <td><a href='$url'>{$row['total_income_cash']}</a></td>
                    <td><a href='$url'>{$row['total_income_card']}</a></td>
                    <td><a href='$url'>{$row['total_income_other']}</a></td>
                    <td><a href='$url'>{$row['total_income']}</a></td>
                    <td><a href='$url'>{$row['total_expense_cash']}</a></td>
                    <td><a href='$url'>{$row['total_expense_card']}</a></td>
                    <td><a href='$url'>{$row['total_expenses']}</a></td>
                    <td><a href='$url'>{$row['total_net']}</a></td>
                </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p class='mt-3'>No reports found for the selected month and year.</p>";
    }
}

// Fetch distinct user scales
$scales = fetchUserScales($con);

// Handle monthly report generation
if (isset($_POST['generate_monthly_report'])) {
    $selected_year = $_POST['year'];
    $selected_month = $_POST['month'];
    
    if (!empty($selected_year) && !empty($selected_month)) {
        $monthly_report_result = getMonthlyReport($con, $selected_year, $selected_month);
    } else {
        $monthly_report_result = null;
    }
}

?>

<div class="container-fluid mt-4 bg-white">
    <h4 class="text-center">Monthly Income Report</h4>
    
    <form method="post">
        <div class="container-fluid py-3 text-center">
            
            
            
            <div class="row">
                <div class="col-md-6 col-sm-12">
                                <label for="year" class="form-label">Select Year:</label>
            <select class="form-select" id="year" name="year">
                <option value="">Select Year</option>
                <?php for ($y = date("Y"); $y >= 2000; $y--): ?>
                    <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                <?php endfor; ?>
            </select>
                </div>
                <div class="col-md-6 col-sm-12">
                                <label for="month" class="form-label">Select Month:</label>
            <select class="form-select" id="month" name="month">
                <option value="">Select Month</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars(date('F', mktime(0, 0, 0, $m, 1))) ?></option>
                <?php endfor; ?>
            </select>
                </div>

            </div>
            
            
            
            
            




           
        </div>
                        <div class="">
                     <button type="submit" name="generate_monthly_report" class="btn btn-primary mt-2">Find report</button>
                </div>
    </form>

    <?php 
    // Display the monthly report if available
    if (isset($monthly_report_result)) {
        displayMonthlyReport($monthly_report_result);
    }
    ?>
</div>

<?php include 'footer.php'; ?>
<?php ob_end_flush(); ?>
