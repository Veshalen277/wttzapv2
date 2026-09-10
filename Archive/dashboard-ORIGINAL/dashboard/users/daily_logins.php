<?php
session_start();
include "../config.php";

// Get date from URL parameter
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    $date = date('Y-m-d');
}

// Get logins for specific date
$sql = "
    SELECT 
        u.id as user_id,
        u.fullname,
        u.user_des as designation,
        u.email,
        l.login_time
    FROM user_logins l
    JOIN users_tbl u ON l.user_id = u.id
    WHERE DATE(l.login_time) = ?
    ORDER BY l.login_time DESC
";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include '../header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-users me-2"></i>Users Who Logged In on <?= date('F j, Y', strtotime($date)) ?></h2>
            <a href="logins.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Login Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th>User</th>
                            <th>Designation</th>
                            <th>Login Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php 
                            $counter = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                                $loginTime = strtotime($row["login_time"]);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $counter++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($row["fullname"]) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($row["email"]) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row["designation"]) ?></td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="top" 
                                              title="<?= date("F j, Y, g:i a", $loginTime) ?>">
                                            <?= date("h:i A", $loginTime) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-user-times fa-2x text-muted mb-3"></i>
                                    <h5 class="text-muted">No logins recorded on this date</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../footer.php';?>