<?php
// Include necessary files
include '../header.php';
include '../functions.php';

if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: /dashboard/404.php");
    exit();
}

// Check if ID is provided via GET
if (!isset($_GET['id'])) {
    header("Location: users_list.php");
    exit;
}

// Fetch user details based on ID
$id = $_GET['id'];
$result = get_user_by_id($con, $id);

// Fetch distinct user scales
$scales = get_distinct_user_scales($con);

// Handle form submission
if (isset($_POST['update_emp'])) {
    $post_data = $_POST;
    $post_data['id'] = $id;

    // Hash password if provided, otherwise remove from update data
    if (!empty(trim($post_data['user_pass']))) {
        $post_data['user_pass'] = sha1($post_data['user_pass']); // SHA-1 hashing to match existing format
    } else {
        unset($post_data['user_pass']);
    }

    // Send user_role as selected
    $query = update_user_details($con, $post_data);

    if ($query) {
        $_SESSION['update'] = "Record updated!";
        header("Location: users_list.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating record: " . mysqli_error($con);
        header("Location: edit_emp.php?id=$id");
        exit;
    }
}
?>

<!-- HTML Form for editing employee details -->
<div class="container mt-2">
    <form action="" method="POST">
        <div class="row m-2 p-3 register_form border border-secondary">
            <h5 class="text-center">Edit Employee</h5>
            <div class="col-md-5 m-auto">
                <div class="mb-2">
                    <label>Fullname</label>
                    <input type="text" name="user_name" placeholder="Fullname" class="form-control" required value="<?= isset($result['fullname']) ? htmlspecialchars($result['fullname']) : '' ?>" maxlength="30" minlength="3">
                </div>

                <div class="mb-2">
                    <label>Designation</label>
                    <input type="text" name="user_des" placeholder="Designation" class="form-control" required value="<?= isset($result['user_des']) ? htmlspecialchars($result['user_des']) : '' ?>" maxlength="30" minlength="3">
                </div>

                <div class="mb-2 border border-danger border-3 p-2">
                    <label><?= isset($result['fullname']) ? htmlspecialchars($result['fullname']) : '' ?> has been assigned these tasks:</label>
                    <textarea class="form-control" rows="6" name="user_res" maxlength="23000" minlength="10"><?= isset($result['user_res']) ? htmlspecialchars($result['user_res']) : '' ?></textarea>
                </div>

                <div class="mb-2">
                    <label>Job Functions</label>
                    <textarea class="form-control" rows="4" name="job_functions" placeholder="Enter job functions (one per line)"><?= isset($result['job_functions']) ? htmlspecialchars($result['job_functions']) : '' ?></textarea>
                </div>

                <div class="mb-2">
                    <label>Date Started</label>
                    <input type="date" name="date_started" class="form-control" required value="<?= isset($result['date_started']) ? htmlspecialchars($result['date_started']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>ID Number</label>
                    <input type="text" name="id_number" class="form-control" maxlength="13" minlength="13" required value="<?= isset($result['id_number']) ? htmlspecialchars($result['id_number']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" maxlength="100" minlength="5" required value="<?= isset($result['email']) ? htmlspecialchars($result['email']) : '' ?>">
                </div>
            </div>

            <div class="col-md-5">
                <div class="mb-2">
                    <label>Department</label>
                    <select class="form-control" name="user_scale" required>
                        <?php foreach ($scales as $scale): ?>
                            <option value="<?= htmlspecialchars($scale['user_scale']) ?>" <?= (isset($result['user_scale']) && $result['user_scale'] == $scale['user_scale']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($scale['user_scale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label>User ID</label>
                    <input type="text" name="user_id" class="form-control" maxlength="30" minlength="4" value="<?= isset($result['user_id']) ? htmlspecialchars($result['user_id']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>Password (leave blank to keep unchanged)</label>
                    <input type="password" name="user_pass" class="form-control" minlength="5" placeholder="Enter new password">
                </div>

                <div class="mb-2">
                    <label>User Role</label>
                    <select name="user_role" class="form-control" required>
                        <option value="0" <?= ($result['user_role'] == 0) ? 'selected' : '' ?>>Admin</option>
                        <option value="1" <?= ($result['user_role'] == 1) ? 'selected' : '' ?>>Normal User</option>
                        <option value="2" <?= ($result['user_role'] == 2) ? 'selected' : '' ?>>Super Admin</option>
                        <option value="3" <?= ($result['user_role'] == 3) ? 'selected' : '' ?>>SC Admin</option>
                        <option value="4" <?= ($result['user_role'] == 4) ? 'selected' : '' ?>>SC Super Admin</option>
                        <option value="5" <?= ($result['user_role'] == 5) ? 'selected' : '' ?>>K D</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" maxlength="100" required value="<?= isset($result['address']) ? htmlspecialchars($result['address']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" maxlength="10" minlength="10" required value="<?= isset($result['contact_number']) ? htmlspecialchars($result['contact_number']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>Next of Kin</label>
                    <input type="text" name="next_of_kin" class="form-control" maxlength="50" value="<?= isset($result['next_of_kin']) ? htmlspecialchars($result['next_of_kin']) : '' ?>">
                </div>

                <div class="mb-2">
                    <label>Next of Kin Number</label>
                    <input type="text" name="next_of_kin_number" class="form-control" maxlength="10" minlength="10" value="<?= isset($result['next_of_kin_number']) ? htmlspecialchars($result['next_of_kin_number']) : '' ?>">
                </div>

                <div class="row">
                    <div class="col"><button class="btn btn-danger btn-sm text-white" name="update_emp">Update</button></div>
                    <div class="col"><a href="users_list.php" class="btn btn-primary btn-sm text-white">Back</a></div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>