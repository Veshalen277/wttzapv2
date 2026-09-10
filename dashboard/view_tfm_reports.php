<?php
include 'header.php'; 

// Fetch user data from session
$user = $_SESSION['u_data']; 

// Check if user data is available
if (!isset($user)) {
    header("Location: /dashboard/404.php");
    exit;
}

// Extract user role and scale from the session data
$user_role = $user[4]; // Assuming user role is stored in $user[4]
$user_scale = $user[2]; // Assuming user scale is stored in $user[2]

// Define allowed roles
$allowed_roles = ['2']; // Roles that should have access

// Check access based on user role
if (in_array($user_role, $allowed_roles)) {
    // If user role is in the allowed roles, they are allowed to access the page
} else if ($user_role == '0' && $user_scale == 'TFM') {
    // If user role is 0 and scale is TFM, allow access
} else {
    // If none of the conditions are met, deny access
    header("Location: /dashboard/404.php");
    exit;
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
        $archive_sql = "UPDATE mauritius_reports SET status = 'archived', archive_month = ? WHERE id = ?";
        
        $stmt = $con->prepare($archive_sql);
        $stmt->bind_param("si", $current_month, $archive_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success mt-3" role="alert">Report archived successfully</div>';
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error archiving report: ' . htmlspecialchars($con->error) . '</div>';
        }
        
        $stmt->close();
    }
}

// Function to handle restoration logic
function handleRestore($con) {
    if (isset($_POST['restore_btn'])) {
        $restore_id = $_POST['restore_id'];

        // Update query to set the status to 'active' for restoration
        $restore_sql = "UPDATE mauritius_reports SET status = 'active', archive_month = NULL WHERE id = ?";
        
        $stmt = $con->prepare($restore_sql);
        $stmt->bind_param("i", $restore_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success mt-3" role="alert">Report restored successfully</div>';
        } else {
            echo '<div class="alert alert-danger mt-3" role="alert">Error restoring report: ' . htmlspecialchars($con->error) . '</div>';
        }
        
        $stmt->close();
    }
}

// Function to filter and fetch Mauritius reports
function fetchMauritiusReports($con) {
    $sql = "SELECT r.*, u.fullname, u.user_scale
            FROM mauritius_reports r
            LEFT JOIN users_tbl u ON r.user_id = u.id
            WHERE r.status != 'archived' AND u.user_scale = 'TFM'"; // Fetch only TFM reports

    $filter_conditions = [];
    $params = [];
    $types = '';

    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $filter_conditions[] = "r.report_date BETWEEN ? AND ?";
        $params[] = $_POST['start_date'];
        $params[] = $_POST['end_date'];
        $types .= 'ss';
    }

    if (!empty($filter_conditions)) {
        $sql .= " AND " . implode(" AND ", $filter_conditions);
    }

    $stmt = $con->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Fetch distinct user scales
$scales = fetchUserScales($con);

// Handle archive logic
handleArchive($con);

// Handle restoration logic
handleRestore($con);

// Fetch and filter Mauritius reports
$result = fetchMauritiusReports($con);

// Initialize totals
$totals = [
    'income_cash' => 0,
    'income_card' => 0,
    'blinq' => 0,
    'total_income' => 0,
    'expense_cash' => 0,
    'airtime' => 0,
    'total_expenses' => 0,
    'net_total' => 0
];
?>

<div class="container-fluid mt-4 px-3 py-2 bg-white">
    <h4 class="text-center mb-3">Mauritius Income Reports</h4>

    <!-- Print All Button -->
    <div class="mb-3 text-center">
        <button onclick="printTable()" class="btn btn-secondary">Print All Reports</button>
    </div>

    <form method="post">
        <div class="row mb-3">
            <!-- Filter by date range -->
            <div class="col-md-6 col-lg-3 mb-2">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
            </div>

            <div class="col-md-6 col-lg-3 mb-2">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
            </div>

            <!-- Filter by user scale -->
            <div class="col-md-6 col-lg-3 mb-2">
                <label for="user_scale" class="form-label">Departments:</label>
                <select class="form-control" id="user_scale" name="user_scale" disabled>
                    <option value="TFM">TFM</option>
                </select>
            </div>

            <div class="col-md-6 col-lg-3 d-flex align-items-end mb-2">
                <button type="submit" name="filter" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </div>
    </form>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table id='reportsTable' class='table table-hover table-bordered bg-white table-sm'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Report Date</th>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>User Department</th>
                        <th>Income Cash</th>
                        <th>Income Card</th>
                        <th>Blinq</th>
                        <th>Total Income</th>
                        <th>Expense Cash</th>
                        <th>Airtime</th>
                        <th>Total Expenses</th>
                        <th>Net Total</th>
                        <th>Action</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Calculate totals
                            $totals['income_cash'] += $row['income_cash'];
                            $totals['income_card'] += $row['income_card'];
                            $totals['blinq'] += $row['blinq'];
                            $totals['total_income'] += $row['total_income'];
                            $totals['expense_cash'] += $row['expense_cash'];
                            $totals['airtime'] += $row['airtime'];
                            $totals['total_expenses'] += $row['total_expenses'];
                            $totals['net_total'] += $row['net_total'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['report_date']) ?></td>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['user_scale']) ?></td>
                            <td><?= htmlspecialchars($row['income_cash']) ?></td>
                            <td><?= htmlspecialchars($row['income_card']) ?></td>
                            <td><?= htmlspecialchars($row['blinq']) ?></td>
                            <td><?= htmlspecialchars($row['total_income']) ?></td>
                            <td><?= htmlspecialchars($row['expense_cash']) ?></td>
                            <td><?= htmlspecialchars($row['airtime']) ?></td>
                            <td><?= htmlspecialchars($row['total_expenses']) ?></td>
                            <td><?= htmlspecialchars($row['net_total']) ?></td>
                            <td class='action'>
                                <form method='post' class='d-inline'>
                                    <a href='tfm_report_form.php?id=<?= htmlspecialchars($row['id']) ?>' class='btn btn-primary btn-sm'>Edit</a>
                                    <input type='hidden' name='archive_id' value='<?= htmlspecialchars($row['id']) ?>'>
                                    <button type='submit' name='archive_btn' class='btn btn-danger btn-sm'>Archive</button>
                                </form>
                            </td>
                            <td>
                                <button class='btn btn-info btn-sm' data-bs-toggle='modal' data-bs-target='#viewNotesModal<?= htmlspecialchars($row['id']) ?>'>View Notes</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <!-- Display totals -->
                    <tr>
                        <td colspan='5'><strong>Total</strong></td>
                        <td><?= htmlspecialchars($totals['income_cash']) ?></td>
                        <td><?= htmlspecialchars($totals['income_card']) ?></td>
                        <td><?= htmlspecialchars($totals['blinq']) ?></td>
                        <td><?= htmlspecialchars($totals['total_income']) ?></td>
                        <td><?= htmlspecialchars($totals['expense_cash']) ?></td>
                        <td><?= htmlspecialchars($totals['airtime']) ?></td>
                        <td><?= htmlspecialchars($totals['total_expenses']) ?></td>
                        <td><?= htmlspecialchars($totals['net_total']) ?></td>
                        <td colspan='2'></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class='text-center'>No reports found</p>
    <?php endif; ?>

    <!-- Modal HTML -->
    <?php 
    $result->data_seek(0); // Reset the result pointer to reuse the same result set
    while ($row = $result->fetch_assoc()): ?>
    <div class="modal fade" id="viewNotesModal<?= htmlspecialchars($row['id']) ?>" tabindex="-1" aria-labelledby="viewNotesModalLabel<?= htmlspecialchars($row['id']) ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewNotesModalLabel<?= htmlspecialchars($row['id']) ?>">Notes for Report <?= htmlspecialchars($row['id']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= htmlspecialchars($row['notes'] ?? 'No notes available') ?></p> <!-- Assuming 'notes' is a column in your reports table -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <script>
    function printTable() {
        var printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Print Reports</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
        printWindow.document.write('</head><body >');
        printWindow.document.write(document.querySelector('#reportsTable').outerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    </script>
</div>

<?php include 'footer.php'; ?>
