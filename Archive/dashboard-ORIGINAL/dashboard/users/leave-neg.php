<?php
include '../header.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email
function sendEmail($email, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za';
        $mail->Password   = '!Mv130369$';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('info@wttzap.co.za', 'Mailer');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function adjustLeaveDays($userId, $days, $action) {
    global $con;
    $balanceSql = "SELECT allocated_leave_days FROM users_tbl WHERE id = $userId";
    $balanceResult = $con->query($balanceSql);

    if ($balanceResult->num_rows > 0) {
        $balanceRow = $balanceResult->fetch_assoc();
        $currentBalance = $balanceRow['allocated_leave_days'];

        if ($action == 'deduct') {
            if ($currentBalance - $days < 0) {
                echo "Error: Insufficient leave balance.";
                return false;
            }
            $newBalance = $currentBalance - $days;
        } elseif ($action == 'add') {
            $newBalance = $currentBalance + $days;
        }

        $updateSql = "UPDATE users_tbl SET allocated_leave_days = $newBalance WHERE id = $userId";
        return $con->query($updateSql);
    }
    echo "User not found or error fetching balance.";
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $leaveId = intval($_POST['leave_id']);

    if ($action == 'delete') {
        $deleteSql = "DELETE FROM leave_applications WHERE id = $leaveId";
        if ($con->query($deleteSql) === TRUE) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "Error deleting leave application: " . $con->error;
        }
    } else {
        $leaveDetailsSql = "SELECT la.user_id, la.start_date, la.end_date, u.email, u.fullname FROM leave_applications la
                            INNER JOIN users_tbl u ON la.user_id = u.id
                            WHERE la.id = $leaveId";
        $leaveDetailsResult = $con->query($leaveDetailsSql);

        if ($leaveDetailsResult->num_rows > 0) {
            $row = $leaveDetailsResult->fetch_assoc();
            $userId = $row['user_id'];
            $userEmail = $row['email'];
            $userFullName = $row['fullname'];
            $startDate = $row['start_date'];
            $endDate = $row['end_date'];

            $leaveDaysSql = "SELECT DATEDIFF('$endDate', '$startDate') AS days";
            $leaveDaysResult = $con->query($leaveDaysSql);
            $leaveDays = $leaveDaysResult->fetch_assoc()['days'];

            if ($action == 'approve') {
                $status = 'Approved';
                if (!adjustLeaveDays($userId, $leaveDays, 'deduct')) return;
            } elseif ($action == 'disapprove') {
                $status = 'Disapproved';
                if (!adjustLeaveDays($userId, $leaveDays, 'add')) return;
            }

            $sql = "UPDATE leave_applications SET status = '$status' WHERE id = $leaveId";
            if ($con->query($sql) === TRUE) {
                $subject = "Leave Application Status Update";
                $message = "Dear $userFullName,<br><br>Your leave application has been " . strtolower($status) . ".";
                sendEmail($userEmail, $subject, $message);

                $adminEmail = 'admin@timefliesza.co.za';
                $adminMessage = "The leave application for user ID $userId has been " . strtolower($status) . ".";
                sendEmail($adminEmail, $subject, $adminMessage);

                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                echo "Error updating leave application: " . $con->error;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_leave'])) {
    $userId = $_POST['user_id'];
    $leaveType = $_POST['leave_type'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO leave_applications (user_id, leave_type, start_date, end_date, reason, status) 
            VALUES ('$userId', '$leaveType', '$startDate', '$endDate', '$reason', 'Pending')";

    if ($con->query($sql) === TRUE) {
        $altaafsEmail = 'altaafs@wtt.co.za';
        $subject = "New Leave Application Submitted";
        $message = "A new leave application has been submitted.<br><br>
                    User ID: $userId<br>Leave Type: $leaveType<br>
                    Start Date: $startDate<br>End Date: $endDate<br>Reason: $reason";
        sendEmail($altaafsEmail, $subject, $message);

        echo "Leave application submitted successfully.";
    } else {
        echo "Error: " . $con->error;
    }
}

// Assuming the alias 'la' for leave_applications is correct

$limit = 15;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filterConditions = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter'])) {
    if (!empty($_POST['user_id'])) {
        $filterConditions[] = "la.user_id = " . intval($_POST['user_id']);
    }
    if (!empty($_POST['leave_type'])) {
        $filterConditions[] = "la.leave_type LIKE '%" . $con->real_escape_string($_POST['leave_type']) . "%'";
    }
    if (!empty($_POST['status'])) {
        // Ensure you reference `la.status` correctly
        $filterConditions[] = "la.status = '" . $con->real_escape_string($_POST['status']) . "'";
    }
}

// Build the filter query
$filterQuery = implode(' AND ', $filterConditions);

// Main SQL query for leave applications
$sql = "SELECT la.*, u.fullname FROM leave_applications la
        INNER JOIN users_tbl u ON la.user_id = u.id" . 
        (!empty($filterQuery) ? " WHERE $filterQuery" : "") . "
        LIMIT $limit OFFSET $offset";

// Debugging: Output the final SQL query (remove this line in production)
echo "<pre>" . $sql . "</pre>"; // Show the final query for debugging

// Execute the query
$result = $con->query($sql);

// Count the total rows for pagination
$countSql = "SELECT COUNT(*) AS total FROM leave_applications la" . 
            (!empty($filterQuery) ? " WHERE $filterQuery" : "");
$countResult = $con->query($countSql);
$rowCount = $countResult->fetch_assoc()['total'];
$totalPages = ceil($rowCount / $limit);

?>

<div class="container-fluid mt-2 table-responsive bg-white">
    <h4 class="text-center mb-3">Employee Leave Applications List</h4>
    <hr>

    <!-- Filter Form -->
    <form method="post" class="mb-3">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label">User ID:</label>
                <input type="text" class="form-control" name="user_id" 
                       value="<?= isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : '' ?>" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Leave Type:</label>
                <input type="text" class="form-control" name="leave_type" 
                       value="<?= isset($_POST['leave_type']) ? htmlspecialchars($_POST['leave_type']) : '' ?>" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Status:</label>
                <select class="form-control" name="status">
                    <option value="">Select Status</option>
                    <option value="Approved" <?= isset($_POST['status']) && $_POST['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Disapproved" <?= isset($_POST['status']) && $_POST['status'] === 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
                    <option value="Pending" <?= isset($_POST['status']) && $_POST['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-3 align-self-end mb-2">
                <button type="submit" name="filter" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </div>
    </form>

    <!-- Leave Applications Table -->
    <table class="table table-hover table-sm bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Full Name</th>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row["id"] ?></td>
                        <td><?= $row["user_id"] ?></td>
                        <td><?= htmlspecialchars($row["fullname"]) ?></td>
                        <td><?= htmlspecialchars($row["leave_type"]) ?></td>
                        <td><?= htmlspecialchars($row["start_date"]) ?></td>
                        <td><?= htmlspecialchars($row["end_date"]) ?></td>
                        <td><?= htmlspecialchars($row["reason"]) ?></td>
                        <td><?= htmlspecialchars($row["status"]) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="leave_id" value="<?= $row["id"] ?>">
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="submit" class="btn btn-light btn-sm text-success" name="action" value="approve">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <button type="submit" class="btn btn-dark btn-sm text-white" name="action" value="disapprove">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                    <button type="submit" class="btn btn-danger btn-sm text-white" name="action" value="delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center">No results found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- FontAwesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<?php include '../footer.php'; ?>
