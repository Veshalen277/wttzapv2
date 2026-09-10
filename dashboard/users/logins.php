<?php
session_start();
include "../config.php";

$sql = "
    SELECT 
        u.id as user_id,
        u.fullname,
        u.user_des as designation,
        u.email,
        l.login_time,
        l.id as login_id
    FROM user_logins l
    JOIN users_tbl u ON l.user_id = u.id
    WHERE DATE(l.login_time) = CURDATE()
    ORDER BY l.login_time DESC
    LIMIT 50
";
$result = mysqli_query($con, $sql);

include "../header.php";
?>

<div class="container-fluid">
    <div class="mb-4 row">
        <div class="col-md-12">
            <h2><i class="fas me-2 fa-sign-in-alt"></i>Today's Login Activity</h2>
            <p class="text-muted">Showing logins for <?php echo date("F j, Y"); ?></p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th>User</th>
                            <th>Designation</th>
                            <th>Login Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            $counter = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $loginTime = strtotime($row["login_time"]);
                                $currentTime = time();
                                $timeDiff = $currentTime - $loginTime;
                                $isRecent = $timeDiff < 3600; // less than an hour
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $counter++; ?></td>
                            <td>
                                <div class="align-items-center d-flex">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row["fullname"]); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row["email"]); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row["designation"]); ?></td>
                            <td>
                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo date("F j, Y, g:i a", $loginTime); ?>">
                                    <?php echo date("h:i A", $loginTime); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isRecent) { ?>
                                    <span class="badge bg-success">Active now</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Logged in today</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td class="text-center py-4" colspan="5">
                                <i class="fas fa-2x fa-database mb-3 text-muted"></i>
                                <h5 class="text-muted">No logins recorded today</h5>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historical Login Table -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas me-2 fa-history"></i>Historical Login Data</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Logins</th>
                            <th>First Login</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $historySql = "
                            SELECT 
                                DATE(login_time) AS login_date,
                                COUNT(*) AS total_logins,
                                MIN(login_time) AS first_login,
                                MAX(login_time) AS last_login
                            FROM user_logins
                            GROUP BY DATE(login_time)
                            ORDER BY login_date DESC
                            LIMIT 7
                        ";
                        $historyResult = mysqli_query($con, $historySql);

                        while ($history = mysqli_fetch_assoc($historyResult)) {
                            $dateParam = urlencode($history["login_date"]);
                        ?>
                        <tr>
                            <td><?php echo date("M j, Y", strtotime($history["login_date"])); ?></td>
                            <td><?php echo $history["total_logins"]; ?></td>
                            <td><?php echo date("h:i A", strtotime($history["first_login"])); ?></td>
                            <td><?php echo date("h:i A", strtotime($history["last_login"])); ?></td>
                            <td>
                                <a class="btn btn-outline-primary btn-sm" href="daily_logins.php?date=<?php echo $dateParam; ?>">
                                    <i class="fas fa-users me-1"></i> View Users
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (t) {
            return new bootstrap.Tooltip(t);
        });
    });
</script>

<?php include "../footer.php"; ?>
