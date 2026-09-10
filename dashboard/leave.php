<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';
require 'vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email
function sendEmail($email, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za'; // SMTP username
        $mail->Password   = '!Mv130369$'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 465; // TCP port to connect to

        //Recipients
        $mail->setFrom('info@wttzap.co.za', 'Mailer');
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}



// Handle form submission for approving/disapproving leave
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $leaveId = $_POST['leave_id'];

    // Perform update query based on action
    if ($action == 'approve') {
        $status = 'Approved';
    } elseif ($action == 'disapprove') {
        $status = 'Disapproved';
    }

    // Update status in the database
    $sql = "UPDATE leave_applications SET status = '$status' WHERE id = $leaveId";
    if ($con->query($sql) === TRUE) {
        // Fetch user's email and full name
        $userDetailsSql = "SELECT la.user_id, u.email, u.fullname FROM leave_applications la
                           INNER JOIN users_tbl u ON la.user_id = u.id
                           WHERE la.id = $leaveId";
        $userDetailsResult = $con->query($userDetailsSql);
        if ($userDetailsResult->num_rows > 0) {
            $userDetailsRow = $userDetailsResult->fetch_assoc();
            $userId = $userDetailsRow['user_id'];
            $userEmail = $userDetailsRow['email'];
            $userFullName = $userDetailsRow['fullname'];

            // Send email to user
            $subject = "Leave Application Status Update";
            $message = "Dear $userFullName,<br><br>Your leave application has been " . strtolower($status) . ".";
            sendEmail($userEmail, $subject, $message);

            // Send email to admin
            $adminEmail = 'altaafs@wtt.co.za';
            $adminSubject = "Leave Application Status Update for User ID: $userId";
            $adminMessage = "The leave application for user ID $userId has been " . strtolower($status) . ".";
            sendEmail($adminEmail, $adminSubject, $adminMessage);
        }
        echo "Leave application status updated successfully.";
    } else {
        echo "Error updating leave application status: " . $con->error;
    }
}

// Handle leave application submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_leave'])) {
    // Assuming form fields are named accordingly
    $userId = $_POST['user_id']; // Replace with actual user ID handling logic
    $leaveType = $_POST['leave_type']; // Replace with actual leave type handling logic
    $startDate = $_POST['start_date']; // Replace with actual start date handling logic
    $endDate = $_POST['end_date']; // Replace with actual end date handling logic
    $reason = $_POST['reason']; // Replace with actual reason handling logic

    // Insert leave application into database
    $sql = "INSERT INTO leave_applications (user_id, leave_type, start_date, end_date, reason, status) 
            VALUES ('$userId', '$leaveType', '$startDate', '$endDate', '$reason', 'Pending')";

    if ($con->query($sql) === TRUE) {
        // Fetch user's email and full name
        $userDetailsSql = "SELECT email, fullname FROM users_tbl WHERE id = $userId";
        $userDetailsResult = $con->query($userDetailsSql);
        if ($userDetailsResult->num_rows > 0) {
            $userDetailsRow = $userDetailsResult->fetch_assoc();
            $userEmail = $userDetailsRow['email'];
            $userFullName = $userDetailsRow['fullname'];

            // Send email notification to altaafs@wtt.co.za
            $altaafsEmail = 'altaafs@wtt.co.za';
            $altaafsSubject = "New Leave Application Submitted";
            $altaafsMessage = "Dear Altaaf,<br><br>A new leave application has been submitted by $userFullName.<br><br>
                               User ID: $userId<br>Leave Type: $leaveType<br>Start Date: $startDate<br>End Date: $endDate<br>Reason: $reason";
           
            sendEmail($altaafsEmail, $altaafsSubject, $altaafsMessage);

            echo "Leave application submitted successfully.";
        } else {
            echo "Error retrieving user details: " . $con->error;
        }
    } else {
        echo "Error submitting leave application: " . $con->error;
    }
}

// Pagination variables
$limit = 10; // Number of items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page, default is 1
$offset = ($page - 1) * $limit; // Offset calculation

// Fetch leave applications with user's full name
$sql = "SELECT la.*, u.fullname FROM leave_applications la
        INNER JOIN users_tbl u ON la.user_id = u.id
        LIMIT $limit OFFSET $offset";
$result = $con->query($sql);

// Count total rows
$countSql = "SELECT COUNT(*) AS total FROM leave_applications";
$countResult = $con->query($countSql);
$rowCount = $countResult->fetch_assoc()['total'];

// Calculate total pages
$totalPages = ceil($rowCount / $limit);
?>

<div class="row m-2 p-3 register_form border border-secondary">
    <h4 class="text-center mb-3">Employee Leave Applications List</h4>
    <hr>
    <table class="table table-hover bg-white table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>User Full Name</th>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["id"] . "</td>";
                    echo "<td>" . $row["user_id"] . "</td>";
                    echo "<td>" . $row["fullname"] . "</td>";
                    echo "<td>" . $row["leave_type"] . "</td>";
                    echo "<td>" . $row["start_date"] . "</td>";
                    echo "<td>" . $row["end_date"] . "</td>";
                    echo "<td>" . $row["reason"] . "</td>";
                    echo "<td>" . $row["status"] . "</td>";
                    echo '<td>';
                    echo '<form method="POST" action="">';
                    echo '<input type="hidden" name="leave_id" value="' . $row["id"] . '">';
                    echo '<button type="submit" class="btn btn-success btn-sm" name="action" value="approve">Approve</button> ';
                    echo '<button type="submit" class="btn btn-danger btn-sm" name="action" value="disapprove">Disapprove</button>';
                    echo '</form>';
                    echo '</td>';
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>0 results</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Pagination links -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = ($page == $i) ? 'active' : '';
                echo "<li class='page-item $activeClass'><a class='page-link' href='?page=$i'>$i</a></li>";
            }
            ?>
        </ul>
    </nav>
</div>

<?php include 'footer.php'; ?>
