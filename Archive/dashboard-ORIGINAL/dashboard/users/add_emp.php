<?php
// Include required files
include "../header.php";
include "../functions.php";
require "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only allow role 5 (King Dingaling)
if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: /dashboard/404.php");
    die;
}

// Get departments or scales
$scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";
$scale_query = mysqli_query($con, $scale_sql);
$scales = mysqli_fetch_all($scale_query, MYSQLI_ASSOC);

$error_message = '';
$success_message = '';

// If form is submitted
if (isset($_POST["add_emp"])) {
    $data = array(
        "user_name" => $_POST["user_name"],
        "user_des" => $_POST["user_des"],
        "user_res" => $_POST["user_res"],
        "user_scale" => $_POST["user_scale"],
        "user_id" => $_POST["user_id"],
        "user_pass" => $_POST["user_pass"],
        "user_role" => $_POST["user_role"],
        "date_started" => $_POST["date_started"],
        "id_number" => $_POST["id_number"],
        "address" => $_POST["address"],
        "contact_number" => $_POST["contact_number"],
        "email" => $_POST["email"],
        "next_of_kin" => $_POST["next_of_kin"],
        "next_of_kin_number" => $_POST["next_of_kin_number"],
        "job_functions" => $_POST["job_functions"]
    );

    $result = addEmployee($con, $data);

    if (is_bool($result) && $result) {
        $_SESSION["success"] = "Employee added successfully.";

        // Send confirmation email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = "mail.wttzap.co.za";
            $mail->SMTPAuth = true;
            $mail->Username = "info@wttzap.co.za";
            $mail->Password = "!Mv130369$";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom("info@wttzap.co.za", "Mailer");
            $mail->setFrom("admin@wttzap.co.za", "Admin");
            $mail->addAddress($data["email"], $data["user_name"]);

            $mail->isHTML(true);
            $mail->Subject = "Employee Registration Successful";
            $mail->Body = "Dear " . $data["user_name"] . ",<br><br>Your registration as an employee has been successfully completed.<br>Thank you!";
            $mail->AltBody = "Dear " . $data["user_name"] . ",\n\nYour registration as an employee has been successfully completed.\nThank you!";

            $mail->send();
            header("location:add_emp.php");
            die;
        } catch (Exception $e) {
            $_SESSION["error"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            header("location:add_emp.php");
            die;
        }
    } else {
        $_SESSION["error"] = $result;
        header("location:add_emp.php");
        die;
    }
}
?>

<!-- HTML Form for Employee Registration -->
<div class="container-fluid mt-2">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="border border-secondary m-2 p-3 register_form row">
            <h5 class="text-center">Employee Registration Form</h5>
            <div class="col-md-5 m-auto">
                <!-- Left side form fields -->
                <div class="mb-2"><label>Fullname</label>
                    <input class="form-control" name="user_name" maxlength="30" required minlength="3" placeholder="Fullname">
                </div>
                <div class="mb-2"><label>Designation</label>
                    <input class="form-control" name="user_des" maxlength="30" required minlength="3" placeholder="Designation">
                </div>
                <div class="mb-2"><label>Responsibilities</label>
                    <textarea class="form-control" name="user_res" rows="6" maxlength="300" minlength="10"></textarea>
                </div>
                <div class="mb-2"><label>Job Functions (Bullet Points)</label>
                    <textarea class="form-control" name="job_functions" rows="4" placeholder="Enter each function on a new line"></textarea>
                </div>
                <div class="mb-2"><label>Date Started</label>
                    <input class="form-control" name="date_started" required type="date">
                </div>
                <div class="mb-2"><label>ID Number (13 digits)</label>
                    <input class="form-control" name="id_number" maxlength="13" required minlength="13" type="number">
                </div>
                <div class="mb-2"><label>Email</label>
                    <input class="form-control" name="email" maxlength="100" required type="email">
                </div>
                <div class="mb-2"><label>Address</label>
                    <input class="form-control" name="address" maxlength="100" required>
                </div>
                <div class="mb-2"><label>Contact Number (10 digits)</label>
                    <input class="form-control" name="contact_number" maxlength="10" required minlength="10" type="number">
                </div>
            </div>
            <div class="col-md-5">
                <!-- Right side form fields -->
                <div class="mb-2"><label>Department</label>
                    <select class="form-control" name="user_scale" required>
                        <option value="">Select Dept</option>
                        <?php foreach ($scales as $scale) { ?>
                            <option value="<?php echo $scale["user_scale"]; ?>"><?php echo $scale["user_scale"]; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb-2"><label>User ID</label>
                    <input class="form-control" name="user_id" maxlength="30" required minlength="4">
                </div>
                <div class="mb-2"><label>Password</label>
                    <input class="form-control" name="user_pass" maxlength="100" required minlength="4" type="password">
                </div>
                <div class="mb-2"><label>User Role</label>
                    <select class="form-control" name="user_role" required>
                        <option value="">Select Role</option>
                        <option value="1">Normal User</option>
                        <option value="0">Admin</option>
                        <option value="2">Super User</option>
                        <option value="3">SC Admin</option>
                        <option value="4">SC Super Admin</option>
                        <option value="5">King Dingaling</option>
                    </select>
                </div>
                <div class="mb-2"><label>Next of Kin</label>
                    <input class="form-control" name="next_of_kin" maxlength="50">
                </div>
                <div class="mb-2"><label>Next of Kin Number</label>
                    <input class="form-control" name="next_of_kin_number" maxlength="10" minlength="10">
                </div>
                <div class="mb-2">
                    <button class="btn btn-primary" name="add_emp">Register</button>
                    <a class="btn btn-secondary" href="users_list.php">Back</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
// Show success message
if (isset($_SESSION["success"])) {
    echo '<div class="alert mt-3 alert-success" role="alert">' . $_SESSION["success"] . '</div>';
    unset($_SESSION["success"]);
}

// Show error message
if (isset($_SESSION["error"])) {
    echo '<div class="alert mt-3 alert-danger" role="alert">' . $_SESSION["error"] . '</div>';
    unset($_SESSION["error"]);
}

include "../footer.php";
?>
