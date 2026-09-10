<?php
include 'header.php';

require 'vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Define the function to send email
function sendEmail($toAddresses, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za'; // SMTP username
        $mail->Password   = '!Mv130369$'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable SMTPS encryption
        $mail->Port       = 465; // TCP port to connect to

        // Sender
        $mail->setFrom('info@wttzap.co.za', 'WTTZAP Work Report Mailer');

        // Recipients
        foreach ($toAddresses as $address) {
            $mail->addAddress($address); // Add each recipient
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<div class="container-fluid mt-2 mb-2">
    <div class="row">
        <div class="col-md-3 col-sm-12">
            <!-- Navigation Block -->
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="/dashboard/message_board.php" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-chat-dots"></i> Messages
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/dashboard/suggestion.php" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-balloon-heart"></i> Suggestions
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/dashboard/leave_app.php" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-rocket-takeoff-fill"></i> Apply for leave
                    </a>
                </li>
            </ul>
        </div>
        <div class="col-md-9 col-sm-12 emp_profile border border-secondary">
            <p class="text-center bg-white p-3">
                <span class="emp_name"><?php echo ucwords($user[0]) ?></span><br>
                <span>(<?php echo ucwords($user[1]) ?>)</span>
                <span>Role: (<?php echo ucwords($user[4]) ?>)</span>
            </p>
            <div class="bg-white p-3">
                <strong class="text-danger">
                    <?php
                    // Prints the day
                    echo date("l") . " | ";
                    // Prints the day, date, month, year, time, AM or PM
                    $work_dateTime = date("jS \of F Y h:i:s A");
                    echo $work_dateTime;
                    ?>
                </strong>
                <br>
                <strong>Tasks:</strong>
                <ul>
                    <li><small><?php echo ucwords($user[3]) ?></small></li>
                </ul>
            </div>
            <br>
            <div class="bg-white p-3">
                <form method="POST">
                    <?php
                    // Display messages if they exist
                    if (isset($_SESSION['msg'])) {
                        $msg_type = $_SESSION['msg_type'] == "error" ? "text-danger" : "text-success";
                        echo "<p class='$msg_type'>{$_SESSION['msg']}</p>";
                        unset($_SESSION['msg']);
                        unset($_SESSION['msg_type']);
                    }
                    ?>
                    <label><strong>I did :</strong></label>
                    <textarea required="required" class="form-control" rows="5" name="work_desc" maxlength="200"
                        minlength="10"></textarea>
                    <button type="submit" name="work_btn" value="<?php echo $user[5]; ?>"
                        class="btn btn-primary mt-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Modal for success/error messages -->
<div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php
                // Display the message from the session if it exists
                if (isset($_SESSION['msg'])) {
                    echo htmlspecialchars($_SESSION['msg']);
                    unset($_SESSION['msg']); // Clear the message after displaying
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.href='emp_profile.php'">OK</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Show the modal if there's a message
    $(document).ready(function() {
        <?php if (isset($_SESSION['msg'])): ?>
            $('#messageModal').modal('show');
        <?php endif; ?>
    });
</script>

<?php include 'footer.php'; ?>

<?php
// Handle form submission for work description
if (isset($_POST['work_btn'])) {
    // Retrieve user data from session
    $id = $user[5]; // Assuming $user[5] contains the employee_id
    $fullname = $user[0];
    $department = $user[2];
    $userTask = $user[3];

    // Sanitize and prepare work description
    $work_desc = mysqli_real_escape_string($con, $_POST['work_desc']);

    // Convert newlines to HTML line breaks
    $work_desc_html = nl2br($work_desc);

    // Get the current timestamp
    $current_timestamp = time();

    // Format the date and time
    $date = date("Y-m-d H:i:s", $current_timestamp);

    // Check if work for today already exists
    $check_query = "SELECT * FROM work_tbl WHERE employee_id='$id' AND work_date='$date'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['msg'] = "You have already submitted work for today.";
        $_SESSION['msg_type'] = "error";
    } else {
        // Insert new work entry
        $insert_query = "INSERT INTO work_tbl (employee_id, work_desc, work_date, assigned_to, department, task) VALUES ('$id', '$work_desc', '$date', '$fullname', '$department', '$userTask')";
        $insert_result = mysqli_query($con, $insert_query);

        if ($insert_result) {
            // Send email notification to admin and secondary email address
            $emailAddresses = ['admin@timefliesza.co.za', 'altaafs@wtt.co.za']; // Array of email addresses
            $subject = "New Work Report Submitted";
            $message = "A new work report has been submitted by $fullname.<br>
                        Date: $date<br>
                        Department: $department<br>
                        Task: $userTask<br>
                        Description: $work_desc_html";
            $emailResult = sendEmail($emailAddresses, $subject, $message);

            if ($emailResult === true) {
                $_SESSION['msg'] = "Work submitted successfully. Email notification sent.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Work submitted successfully, but email notification failed: $emailResult";
                $_SESSION['msg_type'] = "warning";
            }
        } else {
            $_SESSION['msg'] = "Error submitting work. Please try again.";
            $_SESSION['msg_type'] = "error";
        }
    }

    // Redirect back to the same page
    header("Location: emp_profile.php");
    exit(); // Make sure to exit after redirection
}
?>
