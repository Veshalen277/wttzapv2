<?php
include '../header.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function sendEmail(array $to, string $subject, string $message, array $cc = [], array $bcc = []): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
        $mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)(getenv('SMTP_PORT') ?: 465);

        $from = getenv('SMTP_FROM') ?: $mail->Username;
        $mail->setFrom($from, 'WTTZAP Leave Application');

        foreach ($to as $email)  { if (!empty($email)) $mail->addAddress($email); }
        foreach ($cc as $email)  { if (!empty($email)) $mail->addCC($email); }
        foreach ($bcc as $email) { if (!empty($email)) $mail->addBCC($email); }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // In production log this rather than echo
        return false;
    }
}

/* -----------------------------
   Session guard + user context
------------------------------*/
$current_user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;
$current_fullname = isset($_SESSION['u_data'][0]) ? $_SESSION['u_data'][0] : '';

if ($current_user_id <= 0) {
    header("Location: ../index.php");
    exit;
}

/* -----------------------------
   Handle form submission
------------------------------*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Always trust session for identity, not POST fields
    $leave_type  = trim($_POST['leave_type'] ?? '');
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '');
    $reason      = trim($_POST['reason'] ?? '');

    // Basic validation
    if ($leave_type === '' || $start_date === '' || $end_date === '' || $reason === '') {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: " . basename(__FILE__));
        exit;
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        $_SESSION['error'] = "End date cannot be before start date.";
        header("Location: " . basename(__FILE__));
        exit;
    }

    $stmt = $con->prepare(
        "INSERT INTO leave_applications (user_id, leave_type, start_date, end_date, reason)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        $_SESSION['error'] = "System error preparing request. Please contact admin.";
        header("Location: " . basename(__FILE__));
        exit;
    }

    $stmt->bind_param("issss", $current_user_id, $leave_type, $start_date, $end_date, $reason);

    if ($stmt->execute()) {

        // Email distribution
        $to  = ['altaafs@wtt.co.za', 'hr@wtt.co.za'];
        $cc  = ['accounts@wtt.co.za', 'shuiabk@wtt.co.za', 'studio@wtt.co.za'];
        $bcc = ['admin@timefliesza.co.za'];

        $subject = "New Leave Application Submitted";
        $message = "
            A new leave application has been submitted.<br><br>
            <strong>Employee:</strong> " . e($current_fullname) . " (ID: " . (int)$current_user_id . ")<br>
            <strong>Leave Type:</strong> " . e($leave_type) . "<br>
            <strong>Start Date:</strong> " . e($start_date) . "<br>
            <strong>End Date:</strong> " . e($end_date) . "<br>
            <strong>Reason:</strong><br>" . nl2br(e($reason)) . "
        ";

        $emailSent = sendEmail($to, $subject, $message, $cc, $bcc);

        $_SESSION['success'] = $emailSent
            ? "Leave application submitted successfully and notification email sent."
            : "Leave application submitted successfully, but email could not be sent (please notify HR).";

    } else {
        $_SESSION['error'] = "Error submitting application. Please try again.";
    }

    $stmt->close();

    header("Location: " . basename(__FILE__));
    exit;
}
?>

<style>
    .page-title { font-size: .95rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .card-title-sm { font-size: .8rem; font-weight: 800; text-transform: uppercase; }
    .hint { font-size: .75rem; color: #6c757d; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- Sidebar -->
        <div class="col-12 col-md-3">
            <div class="bg-white border p-2 shadow-sm">
                <div class="page-title mb-2">Navigation</div>
                <?php include '../inc/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main -->
        <div class="col-12 col-md-9">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="page-title">Leave Management</div>
                <div class="hint">Submit a leave application for approval</div>
            </div>

            <!-- Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title-sm text-danger mb-1">Leave application form</div>
                            <div class="hint">All fields are required. Please ensure dates are correct.</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Leave</span>
                        </div>
                    </div>

                    <hr>

                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="needs-validation" novalidate>

                        <div class="row g-3 mb-1">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Employee ID</label>
                                <input type="text" class="form-control" value="<?= (int)$current_user_id; ?>" readonly>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Full name</label>
                                <input type="text" class="form-control" value="<?= e($current_fullname); ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase">Leave type</label>
                                <select class="form-select" name="leave_type" required>
                                    <option value="" selected disabled>Select leave type</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Vacation">Vacation</option>
                                    <option value="Personal Leave">Personal Leave</option>
                                </select>
                                <div class="invalid-feedback">Please select a leave type.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Start date</label>
                                <input type="date" class="form-control" name="start_date" required>
                                <div class="invalid-feedback">Please select a start date.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">End date</label>
                                <input type="date" class="form-control" name="end_date" required>
                                <div class="invalid-feedback">Please select an end date.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase">Reason</label>
                                <textarea class="form-control" name="reason" rows="5" required placeholder="Provide details..."></textarea>
                                <div class="invalid-feedback">Please provide a reason.</div>
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    Submit application
                                </button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../footer.php'; ?>
