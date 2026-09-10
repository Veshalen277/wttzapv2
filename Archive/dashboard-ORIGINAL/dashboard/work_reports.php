<?php
include 'header.php';
require 'vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za'; // SMTP username
        $mail->Password   = '!Mv130369$'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption
        $mail->Port       = 465; // TCP port to connect to
        
        //Recipients
        $mail->setFrom('info@wttzap.co.za', 'Mailer');
        $mail->addAddress($to); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        logError("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to log errors
function logError($message) {
    $logfile = 'error_log.txt'; // File to store error logs
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logfile, $logMessage, FILE_APPEND);
}

// Handle form submission for work description
if (isset($_POST['work_btn'])) {
    // Retrieve user data from session
    $id = $user[5]; // Assuming $user[5] contains the employee_id
    $fullname = $user[0];
    $department = $user[2];
    $userTask = $user[3];

    // Sanitize and prepare work description
    $work_desc = mysqli_real_escape_string($con, $_POST['work_desc']);

    // Get the current timestamp
    $current_timestamp = time();
    $date = date("Y-m-d H:i:s", $current_timestamp);

    // Check if work for today already exists
    $check_query = "SELECT * FROM work_tbl WHERE employee_id='$id' AND work_date='$date'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['msg'] = "You have already submitted work for today.";
        $_SESSION['msg_type'] = "error";
    } else {
        // Insert new work entry
        $insert_query = "INSERT INTO work_tbl (employee_id, work_desc, work_date, assigned_to, department, task) 
                         VALUES ('$id', '$work_desc', '$date', '$fullname', '$department', '$userTask')";
        $insert_result = mysqli_query($con, $insert_query);

        if ($insert_result) {
            // Send email notification to admin
            $adminEmail = 'admin@timefliesza.co.za';
            $subject = "New Work Report Submitted";
            $message = "A new work report has been submitted by $fullname.<br>
                        Date: $date<br>
                        Department: $department<br>
                        Task: $userTask<br>
                        Description: $work_desc";
            $emailResult = sendEmail($adminEmail, $subject, $message);

            if ($emailResult) {
                $_SESSION['msg'] = "Work submitted successfully. Email notification sent to admin.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Work submitted successfully, but email notification failed.";
                $_SESSION['msg_type'] = "warning";
            }
        } else {
            $error = mysqli_error($con);
            logError("Database error: $error");
            $_SESSION['msg'] = "Error submitting work. Please try again.";
            $_SESSION['msg_type'] = "error";
        }
    }

    // Redirect back to the same page
    echo "<script>window.location.href='emp_profile.php';</script>";
    exit(); // Make sure to exit after redirection
}
?>

<div class="container-fluid mt-2 mb-2">
    <div class="row">
        <div class="col-md-2">
            <!-- Leave balance code can go here if needed -->
        </div>
        <div class="col-md-10 col-sm-12 emp_profile p-4 border border-secondary">
            <p class="text-center bg-white p-3">
                <span class="emp_name"><?php echo ucwords($user[0]); ?></span><br>
                <span>(<?php echo ucwords($user[1]); ?>)</span>
                <span>Role: <?php echo ucwords($user[4]); ?>)</span>
            </p>
            <div class="bg-white p-3">
                <strong class="text-danger">
                    <?php
                    echo date("l") . " | ";
                    $work_dateTime = date("jS \of F Y h:i:s A");
                    echo $work_dateTime;
                    ?>
                </strong>
                <br>
                <strong>Tasks:</strong>
                <ul>
                    <li><small><?php echo ucwords($user[3]); ?></small></li>
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
                    <label><strong>Daily Work</strong></label>
                    <textarea required="required" class="form-control" rows="5" name="work_desc" maxlength="200" minlength="10"></textarea>
                    <button type="submit" name="work_btn" value="<?php echo $user[5]; ?>" class="btn btn-primary mt-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
