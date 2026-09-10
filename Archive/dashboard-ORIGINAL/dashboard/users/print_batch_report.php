<?php
include '../config.php'; // Include your database connection
include '../functions.php';
// Get filter values
$date_filter = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d', strtotime('-1 day'));
$task_filter = isset($_POST['task']) ? $_POST['task'] : '';

// Fetch work records for the print view
$result = fetchWorkRecords(0, 999, $date_filter, $task_filter, $con); // Adjust pagination as needed

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Report - Print</title>
    <link rel="stylesheet" href="../path/to/bootstrap.min.css"> <!-- Update path -->
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Batch Work Report</h2>
    <h4>Date: <?php echo htmlspecialchars($date_filter); ?></h4>
    <h4>Task: <?php echo htmlspecialchars($task_filter); ?></h4>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Work Description</th>
                <th>Work Date</th>
                <th>Assigned To</th>
                <th>Department</th>
                <th>Task</th>
                <th>Task Date</th>
                <th>Time Taken</th>
                <th>Attachment Status</th>
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
                    <td>
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
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <button class="btn btn-primary no-print" onclick="window.print();">Print this report</button>
</div>

</body>
</html>
