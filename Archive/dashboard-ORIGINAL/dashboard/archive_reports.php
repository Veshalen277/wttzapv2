<?php
ob_start(); // Start output buffering
include 'header.php'; // Ensure this file has your $con connection object
if ($role!=2) {

  header("location:../dashboard/404.php");

}
// Function to fetch distinct user scales
function fetchUserScales($con) {
    $scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";
    $scale_query = mysqli_query($con, $scale_sql);
    return mysqli_fetch_all($scale_query, MYSQLI_ASSOC);
}

// Function to handle archive logic
function handleArchive($con) {
    if (isset($_POST['archive_btn'])) {
        $archive_id = $_POST['archive_id'];
        $current_month = date('Y-m');

        // Update query to archive report
        $archive_sql = "UPDATE reports SET status = 'archived', archive_month = ? WHERE id = ?";
        
        $stmt = $con->prepare($archive_sql);
        $stmt->bind_param("si", $current_month, $archive_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success mt-3" role="alert">Report archived successfully</div>';
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error archiving report: ' . $con->error . '</div>';
        }

        $stmt->close();
    }
}

// Function to handle restoration logic
function handleRestore($con) {
    if (isset($_POST['restore_btn'])) {
        $restore_id = $_POST['restore_id'];

        // Update query to set the status to 'active' for restoration
        $restore_sql = "UPDATE reports SET status = 'active', archive_month = NULL WHERE id = ?";
        
        $stmt = $con->prepare($restore_sql);
        $stmt->bind_param("i", $restore_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success mt-3" role="alert">Report restored successfully</div>';
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error restoring report: ' . $con->error . '</div>';
        }

        $stmt->close();
    }
}

// Function to filter and fetch archived reports
function fetchArchivedReports($con) {
    $sql = "SELECT r.*, u.fullname, u.user_scale
            FROM reports r
            LEFT JOIN users_tbl u ON r.user_id = u.id
            WHERE r.status = 'archived'"; // Fetch only archived reports

    if (isset($_POST['filter'])) {
        $filter_conditions = [];

        if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $filter_conditions[] = "r.report_date BETWEEN '$start_date' AND '$end_date'";
        }

        if (!empty($_POST['user_scale'])) {
            $user_scale = $_POST['user_scale'];
            $filter_conditions[] = "u.user_scale = '$user_scale'";
        }

        if (!empty($filter_conditions)) {
            $sql .= " AND " . implode(" AND ", $filter_conditions);
        }
    }

    $result = $con->query($sql);
    return $result;
}

// Fetch distinct user scales
$scales = fetchUserScales($con);

// Handle archive logic
handleArchive($con);

// Handle restoration logic
handleRestore($con);

// Fetch and filter archived reports
$result = fetchArchivedReports($con);
?>

<div class="container-fluid mt-4 table-responsive px-5 py-2 bg-white">
    <h4 class="text-center mb-3">Archived Income Reports</h4>

    <form method="post">
        <div class="row mb-3">
            <!-- Filter by date range -->
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" class="form-control" id="start_date" name="start_date">
            </div>

            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" class="form-control" id="end_date" name="end_date">
            </div>

            <!-- Filter by user scale -->
            <div class="col-md-3">
                <label for="user_scale" class="form-label">Dept:</label>
                <select class="form-control" id="user_scale" name="user_scale">
                    <option value="">All Scales</option>
                    <?php foreach ($scales as $scale): ?>
                        <option value="<?= $scale['user_scale'] ?>" <?= isset($_POST['user_scale']) && $_POST['user_scale'] == $scale['user_scale'] ? 'selected' : '' ?>>
                            <?= $scale['user_scale'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 align-self-end">
                <button type="submit" name="filter" class="btn btn-primary">Apply Filters</button>
            </div>
        </div>
    </form>

    <?php 
    if ($result->num_rows > 0) {
        echo "<table class='table table-hover table-bordered bg-white table-sm p-4'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Report Date</th>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>User Department</th>
                        <th>Income Cash</th>
                        <th>Income Card</th>
                        <th>Income Other</th>
                        <th>Total Income</th>
                        <th>Expense Cash</th>
                        <th>Expense Card</th>
                        <th>Total Expenses</th>
                        <th>Net Total</th>
                        <th>Action</th> <!-- Column for restore button -->
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['id'] . "</td>
                    <td>" . $row['report_date'] . "</td>
                    <td>" . $row['user_id'] . "</td>
                    <td>" . $row['fullname'] . "</td>
                    <td>" . $row['user_scale'] . "</td>
                    <td>" . $row['income_cash'] . "</td>
                    <td>" . $row['income_card'] . "</td>
                    <td>" . $row['income_other'] . "</td>
                    <td>" . $row['total_income'] . "</td>
                    <td>" . $row['expense_cash'] . "</td>
                    <td>" . $row['expense_card'] . "</td>
                    <td>" . $row['total_expenses'] . "</td>
                    <td>" . $row['net_total'] . "</td>
                    <td>
                        <form method='post'>
                            <input type='hidden' name='restore_id' value='" . $row['id'] . "'>
                            <button type='submit' name='restore_btn' class='btn btn-success btn-sm'>Restore</button>
                        </form>
                    </td>
                </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p class='mt-3'>0 archived reports</p>";
    }

    $con->close();
    ?>

</div>

<?php include 'footer.php'; ?>
<?php ob_end_flush(); ?>
