<?php
// assigned_work.php

include '../header.php'; // Ensure header.php includes the database connection and user role check

if ($role != 2 && $role != 5) {
    header("Location: /dashboard/404.php");
    exit();
}

// Functions for managing work records

function fetchWorkRecords($start_from, $records_per_page, $date_filter, $task_filter, $con) {
    $sql = "SELECT w.*, u.task_datetime AS user_task_datetime
            FROM work_tbl w
            LEFT JOIN users_tbl u ON w.employee_id = u.id
            WHERE (w.work_date LIKE ? OR ? = '')
              AND (w.task LIKE ? OR ? = '')
            LIMIT ?, ?";
    
    $stmt = mysqli_prepare($con, $sql);
    $date_filter = '%' . mysqli_real_escape_string($con, $date_filter) . '%';
    $task_filter = '%' . mysqli_real_escape_string($con, $task_filter) . '%';

    mysqli_stmt_bind_param($stmt, "ssssii", $date_filter, $date_filter, $task_filter, $task_filter, $start_from, $records_per_page);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

function countTotalRecords($con) {
    $total_sql = "SELECT COUNT(*) AS total FROM work_tbl";
    $total_result = mysqli_query($con, $total_sql);
    $total_records = mysqli_fetch_assoc($total_result)['total'];
    return $total_records;
}

function deleteRecord($delete_id, $con) {
    $delete_sql = "DELETE FROM work_tbl WHERE id = ?";
    $stmt = mysqli_prepare($con, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    $delete_result = mysqli_stmt_execute($stmt);
    return $delete_result;
}

// Get filter values
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('-1 day')); // Default to yesterday's date
$task_filter = isset($_GET['task']) ? $_GET['task'] : '';

// Pagination variables
$start_from = 0; // Adjust as needed
$records_per_page = 20; // Adjust as needed

// Fetch all work records with filters
$result = fetchWorkRecords($start_from, $records_per_page, $date_filter, $task_filter, $con);

if (!$result) {
    echo "Error: " . mysqli_error($con);
    exit;
}

// Handle record deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id <= 0) {
        $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Invalid ID for deletion.</div>';
    } else {
        if (function_exists('deleteRecord')) {
            $delete_result = deleteRecord($delete_id, $con);
            if ($delete_result) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Record deleted successfully.</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Error deleting record: ' . mysqli_error($con) . '</div>';
            }
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">deleteRecord function not defined.</div>';
        }
    }
    header("Location: assigned_work.php?date=" . urlencode($date_filter) . "&task=" . urlencode($task_filter));
    exit();
}
?>

<div class="container-fluid p-3 bg-white">
    <div class="row">
        <div class="col-md-11 col-sm-12">
            <div class="mt-2">
                <h2>Assigned Work Records</h2>

                <!-- Display notification message -->
                <?php if (isset($_SESSION['msg'])): ?>
                    <?php echo $_SESSION['msg']; ?>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>

                <!-- Button to export CSV -->
                <form method="post" action="export_work_rep_csv.php">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    <input type="hidden" name="task" value="<?php echo htmlspecialchars($task_filter); ?>">
                    <button type="submit" class="btn btn-success btn-sm mb-2">Export to CSV</button>
                </form>
<!-- Button to print the batch report -->
<form method="post" action="print_batch_report.php" target="_blank">
    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
    <input type="hidden" name="task" value="<?php echo htmlspecialchars($task_filter); ?>">
    <button type="submit" class="btn btn-secondary btn-sm mb-2">Print Batch Report</button>
</form>
<!-- Button to email the batch report -->
<form method="post" action="email_batch_report.php">
    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
    <input type="hidden" name="task" value="<?php echo htmlspecialchars($task_filter); ?>">
    <button type="submit" class="btn btn-info btn-sm mb-2">Email Batch Report</button>
</form>

                <!-- Filter form -->
                <form class="form-inline mb-2" method="get" action="assigned_work.php">
                    <label class="mr-2">Filter by Date:</label>
                    <input type="date" class="form-control mr-2" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    <label class="mr-2">Filter by Task:</label>
                    <input type="text" class="form-control mr-2" name="task" value="<?php echo htmlspecialchars($task_filter); ?>" placeholder="Task keyword">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Work Description</th>
                                <th>Work Date</th>
                                <th>Assigned To</th>
                                <th>Department</th>
                                <th>Task (Work associated with)</th>
                                <th>Task Date</th>
                                <th>Time Taken</th>
                                <th>Attachment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['work_desc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['work_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['assigned_to']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo htmlspecialchars($row['task']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_task_datetime'] ?? ''); ?></td>
                                    <td class="bg-danger text-white">
                                        <?php
                                        if ($row['work_date'] && $row['user_task_datetime']) {
                                            $work_date = new DateTime($row['work_date']);
                                            $task_date = new DateTime($row['user_task_datetime']);
                                            $interval = $work_date->diff($task_date);
                                            echo $interval->format('%d days, %h hours, %i minutes');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['attachment_path']): ?>
                                            <i class="bi bi-check-circle text-success" title="Attachment Sent"></i>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle text-danger" title="No Attachment"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-flex">
                                        <a href="profile.php?id=<?= $row['employee_id']; ?>" class="btn btn-sm btn-success mx-1 text-white" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit_emp.php?id=<?= $row['employee_id']; ?>" class="btn btn-sm btn-danger text-white" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="ass_task.php?id=<?= $row['employee_id']; ?>" class="btn btn-sm btn-info mx-1 text-white" title="Assign">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>

                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-<?php echo $row['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                        <!-- Modal for deletion -->
                                        <div class="modal fade" id="deleteModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel-<?php echo $row['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel-<?php echo $row['id']; ?>">Confirm Deletion</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure about this?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="post" action="assigned_work.php">
                                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Yes <i class="bi bi-trash3"></i></button>
                                                            <button type="button" class="btn btn-success text-white btn-sm" data-bs-dismiss="modal">No <i class="bi bi-x-square-fill"></i></button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; // Ensure footer.php is included ?>
