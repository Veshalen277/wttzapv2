<?php
include '../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

$userSession = $_SESSION['u_data'];
$id = (int)($userSession[5] ?? 0);

$sql = "SELECT * FROM users_tbl WHERE id = $id LIMIT 1";
$userQuery = mysqli_query($con, $sql);

if (!$userQuery) {
    die("User query failed: " . mysqli_error($con));
}

$userRow = mysqli_fetch_assoc($userQuery);

if (!$userRow) {
    die("User not found");
}

// ✅ Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {

    $fullname        = mysqli_real_escape_string($con, trim($_POST['user_name'] ?? ''));
    $contact_number  = mysqli_real_escape_string($con, trim($_POST['contact_number'] ?? ''));
    $address         = mysqli_real_escape_string($con, trim($_POST['address'] ?? ''));
    $next_of_kin     = mysqli_real_escape_string($con, trim($_POST['next_of_kin'] ?? ''));
    $next_kin_number = mysqli_real_escape_string($con, trim($_POST['next_of_kin_number'] ?? ''));

    $updSql = "
        UPDATE users_tbl
        SET fullname='$fullname',
            contact_number='$contact_number',
            address='$address',
            next_of_kin='$next_of_kin',
            next_of_kin_number='$next_kin_number'
        WHERE id=$id
    ";

    if (mysqli_query($con, $updSql)) {
        $_SESSION['msg'] = 'Profile updated successfully';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'Update failed: ' . mysqli_error($con);
        $_SESSION['msg_type'] = 'error';
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$leave_data = json_decode($userRow['leave_balances'] ?? '', true);
$accrued_leave = $leave_data['accrued_leave'] ?? 0;

$taken_sql = "SELECT SUM(DATEDIFF(end_date, start_date) + 1) AS leave_taken
              FROM leave_applications
              WHERE user_id = $id
              AND YEAR(start_date) = YEAR(CURDATE())
              AND status = 'Approved'";

$taken_query = mysqli_query($con, $taken_sql);
$taken_row = $taken_query ? mysqli_fetch_assoc($taken_query) : [];
$leave_taken = $taken_row['leave_taken'] ?? 0;
$remaining_leave = $accrued_leave - $leave_taken;

$profilePhoto = !empty($userRow['profile_photo'])
    ? $userRow['profile_photo']
    : '/dashboard/employee/uploads/placeholder.jpg';
?>


<style>
    :root { --profile-primary: #dc3545; }
    .profile-header { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; padding: 30px; margin-bottom: 30px; border: 1px solid #dee2e6; }
    .profile-img-container { position: relative; width: 160px; margin: 0 auto; }
    .profile-img-container img { width: 160px; height: 160px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .upload-overlay { position: absolute; bottom: 5px; right: 5px; }
    .nav-pills .nav-link.active { background-color: var(--profile-primary); }
    .nav-pills .nav-link { color: #495057; font-weight: 500; }
    .info-card { border: none; border-radius: 12px; transition: 0.3s; height: 100%; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .leave-stat { font-size: 2rem; font-weight: 700; color: var(--profile-primary); }
    .task-list { max-height: 400px; overflow-y: auto; }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="row">
        <div class="col-lg-2">
            <div class="sticky-top" style="top: 20px;">
                <?php include '../inc/sidebar.php'; ?>
            </div>
        </div>

        <div class="col-lg-10">
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] == 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                    <?= $_SESSION['msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>

            <div class="profile-header text-center text-md-start">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="profile-img-container mb-3 mb-md-0">
                            <img src="<?= $profilePhoto ?>" class="rounded-circle" alt="Profile">
                            <div class="upload-overlay">
                                <button class="btn btn-sm btn-light shadow-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#photoModal">
                                    <i class="bi bi-camera-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h2 class="fw-bold mb-1"><?= ucwords($userRow['fullname']) ?></h2>
                        <p class="text-muted mb-3"><i class="bi bi-briefcase me-2"></i><?= $userRow['user_scale'] ?> | <i class="bi bi-person-badge me-2"></i>ID: <?= $userRow['id_number'] ?></p>
                        <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                            <span class="badge bg-white text-dark border p-2 px-3">Remaining Leave: <?= $remaining_leave ?> Days</span>
                            <span class="badge bg-white text-dark border p-2 px-3">Status: Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills mb-4 gap-2" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#p-info">Personal Details</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#p-leave">Leave Balance</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#p-tasks">Responsibilities</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#p-security">Security</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="p-info">
                    <div class="card info-card">
                        <div class="card-body p-4">
                            <form method="POST">
                                <h5 class="mb-4 fw-bold border-bottom pb-2">Edit Contact Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Full Name</label>
                                        <input type="text" class="form-control" name="user_name" value="<?= $userRow['fullname'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Number</label>
                                        <input type="text" class="form-control" name="contact_number" value="<?= $userRow['contact_number'] ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Home Address</label>
                                        <input type="text" class="form-control" name="address" value="<?= $userRow['address'] ?>">
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <label class="form-label small fw-bold text-danger">Next of Kin Name</label>
                                        <input type="text" class="form-control border-danger-subtle" name="next_of_kin" value="<?= $userRow['next_of_kin'] ?>">
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <label class="form-label small fw-bold text-danger">Next of Kin Contact</label>
                                        <input type="text" class="form-control border-danger-subtle" name="next_of_kin_number" value="<?= $userRow['next_of_kin_number'] ?>">
                                    </div>
                                </div>
                                <button type="submit" name="update_info" class="btn btn-danger mt-4">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="p-leave">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card info-card text-center p-4">
                                <div class="text-muted small mb-2">Accrued This Year</div>
                                <div class="leave-stat"><?= $accrued_leave ?></div>
                                <div class="small">Total Entitlement</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card info-card text-center p-4 border-start border-warning border-4">
                                <div class="text-muted small mb-2">Leave Taken</div>
                                <div class="leave-stat text-warning"><?= $leave_taken ?></div>
                                <div class="small">Days Used</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card info-card text-center p-4 border-start border-success border-4">
                                <div class="text-muted small mb-2">Remaining</div>
                                <div class="leave-stat text-success"><?= $remaining_leave ?></div>
                                <div class="small">Available Days</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="p-tasks">
                    <div class="card info-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Your Key Responsibilities</h5>
                            <div class="task-list">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    //  $tasks = preg_split('/(?<=\.)\s*/', $user[3]);
                                    $taskText = $userRow['user_res'] ?? ($userSession[3] ?? '');
                                    $tasks = preg_split('/(?<=\.)\s*/', $taskText);
                                    foreach ($tasks as $task): if(trim($task)):
                                    ?>
                                        <li class="list-group-item border-0 ps-0">
                                            <i class="bi bi-check2-circle text-danger me-2"></i>
                                            <?= ucwords(trim($task)) ?>
                                        </li>
                                    <?php endif; endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="p-security">
                    <div class="card info-card">
                        <div class="card-body p-4">
                            <form method="POST">
                                <h5 class="mb-4 fw-bold">Update Password</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-outline-danger mt-4">Update Security Credentials</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h6 class="mb-3">Update Profile Photo</h6>
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="profileImage" class="form-control form-control-sm mb-3">
                    <button type="submit" name="submit" class="btn btn-danger w-100">Upload Now</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>