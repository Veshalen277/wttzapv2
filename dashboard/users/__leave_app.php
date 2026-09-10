<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../header.php';
require '../vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email
function sendEmail($to = [], $subject, $message, $cc = [], $bcc = []) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za';
        $mail->Password   = '!Mv130369$';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Sender
        $mail->setFrom('info@wttzap.co.za', 'WTTZAP Leave Application');

        // Add To recipients
        foreach ($to as $email) {
            $mail->addAddress($email);
        }

        // Add CC recipients
        foreach ($cc as $email) {
            $mail->addCC($email);
        }

        // Add BCC recipients
        foreach ($bcc as $email) {
            $mail->addBCC($email);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
      //  echo ' Message has been sent';


   echo '   <div class="container"><div class="alert alert-success mt-3" role="alert">
        <strong><a href="https://wttzap.co.za/dashboard/employee/emp_profile.php">Go back!</a></strong>
      </div>';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $user_id = $_SESSION['u_data']['0'] ?? null;
    $leave_type = $_POST['leave_type'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $reason = $_POST['reason'] ?? null;

    // Check if user_id is not null
    if ($user_id !== null) {
        // Insert leave application into database
        $stmt = $con->prepare("INSERT INTO leave_applications (user_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $leave_type, $start_date, $end_date, $reason);

            if ($stmt->execute()) {
              //  echo "Leave application submitted successfully"; replaced with the bottom 
              echo '<div class="container"><div class="alert alert-success mt-3" role="alert">
        <strong>Success!</strong> Leave application submitted successfully, and notification email sent.
      </div></div>';


                // Email details
                $to = ['altaafs@wtt.co.za', 'hr@wtt.co.za'];
                $cc = ['accounts@wtt.co.za', 'shuiabk@wtt.co.za', 'studio@wtt.co.za']; // Add real CCs here
                $bcc = ['admin@timefliesza.co.za']; // Add real BCCs here

                $subject = "New Leave Application Submitted";
                $message = "A new leave application has been submitted by User ID: $user_id.<br>
                            Leave Type: $leave_type<br>
                            Start Date: $start_date<br>
                            End Date: $end_date<br>
                            Reason: $reason";

                sendEmail($to, $subject, $message, $cc, $bcc);
            } else {
                echo "Error executing query: " . $stmt->error;
            }
        } else {
            echo "Error preparing query: " . $con->error;
        }
    } else {
        echo "Error: User ID is not set.";
    }
}
?>

<div class="container mt-3 p-2 bg-white border ">
    <div class="row">
        <div class="col-md-3 col-sm-12">
            <?php include '../inc/sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-sm-12">
            <!-- <h2>Leave Application Form</h2>
            <form action="</?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="form-group">
                    <label for="user_id">Employee ID:</label>
                    <input type="text" class="form-control" id="user_id" name="user_id"
                        value="</?php $user = $_SESSION['u_data']; echo $user['5']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" class="form-control" id="fullname" name="fullname"
                        value="</?php $user = $_SESSION['u_data']; echo $user['0']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="leave_type">Leave Type:</label>
                    <select class="form-control" id="leave_type" name="leave_type">
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Vacation">Vacation</option>
                        <option value="Personal Leave">Personal Leave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                </div>
                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <textarea class="form-control" id="reason" name="reason"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form> -->


            <h2 class="mb-4">Leave Application Form</h2>
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="p-4 shadow-sm bg-light rounded">

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="user_id" class="form-label">Employee ID</label>
            <input type="text" class="form-control" id="user_id" name="user_id"
                   value="<?php $user = $_SESSION['u_data']; echo $user['5']; ?>" readonly>
        </div>
        <div class="col-md-6">
            <label for="fullname" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullname" name="fullname"
                   value="<?php $user = $_SESSION['u_data']; echo $user['0']; ?>" readonly>
        </div>
    </div>

    <div class="mb-3">
        <label for="leave_type" class="form-label">Leave Type</label>
        <select class="form-select" id="leave_type" name="leave_type" required>
            <option value="" selected disabled>Select Leave Type</option>
            <option value="Sick Leave">Sick Leave</option>
            <option value="Vacation">Vacation</option>
            <option value="Personal Leave">Personal Leave</option>
        </select>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" required>
        </div>
        <div class="col-md-6">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="reason" class="form-label">Reason</label>
        <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Provide details..." required></textarea>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary px-4">Submit Application</button>
    </div>
</form>

        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
