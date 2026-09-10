<?php
include '../header.php';
require '../vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure session user data is available
if (isset($_SESSION['u_data'])) {
    $user = $_SESSION['u_data'];
} else {
    header("Location: ../index.php");
    exit;
}

// Logged in user
$logged_in_user_id = isset($user[5]) ? (int)$user[5] : 0;

// Load the reusable feed component
require '../employee_wall.php';

/**
 * Send email with PHPMailer
 */
function sendEmail($toAddresses, $subject, $message, $attachments = []) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.wttzap.co.za';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@wttzap.co.za';
        $mail->Password   = '!Mv130369$';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('info@wttzap.co.za', 'WTTZAP Work Report Mailer');

        foreach ($toAddresses as $address) {
            $mail->addAddress($address);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $htmlMessage    = '<p>' . str_replace("\n", '</p><p>', $escapedMessage) . '</p>';

        $mail->Body = "<html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; }
                                .email-container { padding: 20px; }
                                .email-header { font-size: 18px; font-weight: bold; }
                                .email-content { margin-top: 10px; }
                                .email-footer { margin-top: 20px; font-size: 12px; color: #888; }
                                p { margin: 0; padding: 0; }
                            </style>
                        </head>
                        <body>
                            <div class='email-container'>
                                <div class='email-header'>{$subject}</div>
                                <div class='email-content'>{$htmlMessage}</div>
                                <div class='email-footer'>
                                    <p>Best regards,</p>
                                    <p>WTTZAP Work Report Mailer</p>
                                </div>
                            </div>
                        </body>
                      </html>";

        if (!empty($attachments['report'])) {
            $mail->addAttachment($attachments['report']);
        }
        if (!empty($attachments['image'])) {
            $mail->addAttachment($attachments['image']);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// IP helper
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    else return $_SERVER['REMOTE_ADDR'];
}

$visitorIP      = getUserIP();
$visitorCountry = $_SESSION['user_country'] ?? 'Unknown';

// Fetch user details from DB for profile
$id    = $user[5];
$sql   = "SELECT * FROM users_tbl WHERE id = $id";
$query = mysqli_query($con, $sql);
$result = mysqli_fetch_assoc($query);

// role for permissions (work report visibility etc in wall)
$current_user_role = isset($result['user_role']) ? (int)$result['user_role'] : 0;

// Profile photo
$profilePhoto = $result['profile_photo'] ?? '';
if (empty($profilePhoto)) {
    $profilePhoto = '/dashboard/employee/uploads/placeholder.jpg';
}

// Tasks text
$userTasksRaw = $user[3] ?? '';
$userDes      = $result['user_des'] ?? '';
?>

<div class="container-fluid">
    <!-- Location strip -->
    <div class="mb-3 p-2 bg-white border">
        <div style="padding: 10px; background-color: #f0f8ff; border-left: 4px solid #0073e6; font-size: 14px; color: #333;">
            🌍 Location : <strong><?= htmlspecialchars($visitorCountry) ?></strong><br>
            🧭 IP Address: <strong><?= htmlspecialchars($visitorIP) ?></strong>
        </div>
    </div>
    <div class="row g-3">
        
        <!-- LEFT COLUMN: Sidebar + Bulletins -->
        <div class="col-12 col-md-3">
        <h3>Menu</h3>
        <div class="bg-white border p-2 mb-3">
                <?php include '../inc/sidebar.php'; ?>
            </div>
            <div class="bg-white border p-2">
                <?php include '../bulletins/widget.php'; ?>
            </div>
        </div>

        <!-- CENTER COLUMN: Profile + FEED -->
        <div class="col-12 col-md-5 border border-3 border-danger">
        <h3>News</h3>    
        <!-- Profile card -->
            <div class="bg-white border p-3 mb-3">
                <div class="d-flex align-items-start">
                    <img src="<?php echo htmlspecialchars($profilePhoto); ?>"
                         alt="Profile Photo" width="120" height="120"
                         class="rounded-circle border border-3 border-white shadow-sm me-3">
                    <div>
                        <span class="emp_name d-block">
                            <strong><?php echo ucwords($user[0]); ?></strong>
                        </span>
                        <span class="d-block">
                            <strong><?php echo ucwords($user[1]); ?></strong>
                        </span>
                        <span class="d-block">
                            Access Level : <?php echo ucwords($user[4]); ?>
                        </span>
                        <span class="d-block mt-2 text-danger">
                            <?php
                                echo date("l") . " | ";
                                $work_dateTime = date("jS \\of F Y h:i:s A");
                                echo $work_dateTime;
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- FEED (Recent updates / posts) -->
            <div class="bg-white border p-3 mb-3">
                <?php
                    // Global feed (posts + work reports for role >=2)
                    render_employee_wall($con, $logged_in_user_id, $current_user_role);
                ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Tasks + Work report form + Accordion -->
        <div class="col-12 col-md-4 p-2">
        <h3>Work</h3>    
        <!-- Tasks at top of right column -->
            <div class="bg-danger  text-white font-weight-bold p-3 mb-3">
                
            <strong>Tasks:</strong>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 15px;">
                    <ul class="mb-0">
                        <?php
                        $tasks = preg_split('/(?<=\.)\s*/', $userTasksRaw);
                        foreach ($tasks as $task) {
                            $task = trim($task);
                            if ($task === '') continue;
                            echo "<li><small>" . ucwords($task) . "</small></li>";
                        }
                        ?>
                    </ul>
                </div>

                <?php if (!empty($userDes)): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            <?= htmlspecialchars(ucwords($userDes)) ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Work report form -->
            <div class="bg-white border p-3 mb-3">
                <form method="POST" enctype="multipart/form-data">
                    <label><strong>I did :</strong></label>
                    <textarea required class="form-control" rows="5" name="work_desc"
                              minlength="10"
                              placeholder="You can paste what you copied from the checklist, or type out your work report"></textarea>

                    <label class="mt-2"><strong>Attach file (optional):</strong></label>
                    <input type="file" class="form-control btn-sm" name="attachment"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">

                    <button type="submit" name="work_btn" value="<?php echo (int)$user[5]; ?>"
                            class="btn btn-primary mt-2">
                        Submit
                    </button>
                    <small class="text-muted d-block mt-1">
                        <strong>Please ensure your file conforms to the permitted types before submission.</strong>
                    </small>
                </form>
            </div>

            <!-- File type accordion -->
            <div class="bg-white border p-3">
                <div class="accordion" id="accordionExample">
                    <!-- Non-permitted file types -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <small>Non-permitted file types</small>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show"
                             aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <small>
                                    .exe, .bat, .js, .vbs, .scr, .cmd, .dll, .pl, .py, .sh, .cgi
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Permitted file types -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <small>Permitted file types</small>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse"
                             aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <small>
                                    .jpg, .png, .gif, .pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt, .csv, .zip
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /RIGHT COLUMN -->
    </div><!-- /row -->
</div><!-- /container-fluid -->

<!-- Modal for displaying messages -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="messageModalLabel">Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Message content will be dynamically inserted here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
// Handle form submission for work description
if (isset($_POST['work_btn'])) {
    $id         = $user[5];
    $fullname   = $user[0];
    $department = $user[2];
    $userTask   = $user[3];

    $work_desc = $_POST['work_desc'] ?? '';
    $work_desc = htmlspecialchars($work_desc, ENT_QUOTES, 'UTF-8');

    $work_desc_html = '<ul>';
    $lines = explode("\n", $work_desc);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $work_desc_html .= '<li>' . $line . '</li>';
        }
    }
    $work_desc_html .= '</ul>';

    $html_report = "<html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .report-container { padding: 20px; }
                            .report-header { font-size: 18px; font-weight: bold; }
                            .report-content { margin-top: 10px; }
                            .report-footer { margin-top: 20px; font-size: 12px; color: #888; }
                            ul { list-style-type: disc; margin-left: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='report-container'>
                            <div class='report-header'>Work Report</div>
                            <div class='report-content'>{$work_desc_html}</div>
                            <div class='report-footer'>
                                <p>Best regards,</p>
                                <p>WTTZAP Work Report Mailer</p>
                            </div>
                        </div>
                    </body>
                    </html>";

    $report_file = 'work_report_' . time() . '.html';
    file_put_contents($report_file, $html_report);

    $attachments = ['report' => $report_file];

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir  = '../uploads/';
        $upload_file = $upload_dir . basename($_FILES['attachment']['name']);

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_file)) {
            $attachments['image'] = $upload_file;
        } else {
            $_SESSION['msg']      = "Failed to move uploaded file.";
            $_SESSION['msg_type'] = "error";
            if (file_exists($report_file)) unlink($report_file);
            header("Location: /dashboard/employee/emp_profile.php");
            exit;
        }
    }

    $current_timestamp = time();
    $date = date("Y-m-d H:i:s", $current_timestamp);

    // NOTE: this checks exact datetime; consider changing to DATE(work_date) if you truly mean "per day"
    $check_query  = "SELECT * FROM work_tbl WHERE employee_id='$id' AND work_date='$date'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['msg']      = "You have already submitted work for today.";
        $_SESSION['msg_type'] = "error";
    } else {
        $attachmentPath = $attachments['image'] ?? '';
        $insert_query = "INSERT INTO work_tbl (employee_id, work_desc, work_date, assigned_to, department, task, attachment_path)
                         VALUES ('$id', '$work_desc', '$date', '$fullname', '$department', '$userTask', '$attachmentPath')";
        $insert_result = mysqli_query($con, $insert_query);

        if ($insert_result) {
            $emailAddresses = ['altaafs@wtt.co.za', 'accounts@wtt.co.za','zulekhas@wtt.co.za','shuaibk@wtt.co.za','amata@wtt.co.za'];
            $subject = "New Work Report Submitted";
            $message = "A new work report has been submitted by $fullname.
                        Date: $date
                        Department: $department
                        Task: $userTask";

            $emailResult = sendEmail($emailAddresses, $subject, $message, $attachments);

            if ($emailResult === true) {
                $_SESSION['msg']      = "Work report submitted successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg']      = $emailResult;
                $_SESSION['msg_type'] = "error";
            }
        } else {
            $_SESSION['msg']      = "Failed to submit work report.";
            $_SESSION['msg_type'] = "error";
        }
    }

    if (!empty($attachments['image']) && file_exists($attachments['image'])) {
        unlink($attachments['image']);
    }
    if (file_exists($report_file)) {
        unlink($report_file);
    }

    header("Location: /dashboard/employee/emp_profile.php");
    exit;
}

include '../footer.php';
