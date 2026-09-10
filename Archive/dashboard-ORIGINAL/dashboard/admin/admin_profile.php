<?php 
include '../header.php';

// 1. Access Control
$allowed_roles = [7, 5, 2, 0];
if (!in_array($role, $allowed_roles)) {
    header("location:../dashboard/404.php");
    exit;
}

// 2. Extract User ID from Session
if (isset($_SESSION['u_data'])) {
    $user = $_SESSION['u_data'];
    $id = isset($user[5]) ? (int)$user[5] : 0; 
} else {
    header("Location: ../index.php");
    exit;
}

// 3. Fetch Admin Data
$stmt = mysqli_prepare($con, "SELECT fullname, user_des, profile_photo, leave_balances FROM users_tbl WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);

$fullName = $userData['fullname'] ?? 'Admin'; 
$userDesignation = $userData['user_des'] ?? 'Management';
$db_photo = $userData['profile_photo'] ?? '';
$profilePhoto = (!empty($db_photo)) ? "../" . $db_photo : "../employee/uploads/placeholder.jpg";

// 4. MTD Financial Totals (Month to Date) - Only current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$mtd_sql = "SELECT SUM(total_income) as mtd_inc, SUM(total_expenses) as mtd_exp, SUM(net_total) as mtd_net 
            FROM reports 
            WHERE report_date BETWEEN '$month_start' AND '$month_end'";
$mtd_res = mysqli_fetch_assoc(mysqli_query($con, $mtd_sql));

// 5. Improved Logins (Ordered by date and time)
$login_sql = "SELECT u.fullname, u.user_role, l.login_time 
              FROM user_logins l
              INNER JOIN users_tbl u ON l.user_id = u.id 
              ORDER BY l.login_time DESC LIMIT 15";
$login_query = mysqli_query($con, $login_sql);

// 6. Recent Orders
$order_query = mysqli_query($con, "SELECT fullname, product, quantity, department, submitted_at FROM orders WHERE product != '' ORDER BY submitted_at DESC LIMIT 5");

// 7. Financial History (Detailed Cash-up table)
$cashup_history = mysqli_query($con, "SELECT report_date, total_income, total_expenses, net_total, notes FROM reports ORDER BY report_date DESC LIMIT 12");
?>

<style>
    /* Styling for Logins */
    .login-date-pill {
        background: #f8f9fa;
        color: #dc3545;
        font-size: 0.65rem;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 50px;
        border: 1px solid #eee;
        display: inline-block;
        margin: 10px 0 5px 0;
        text-transform: uppercase;
    }
    .login-item {
        padding: 8px 10px;
        border-radius: 6px;
        transition: background 0.2s;
    }
    .login-item:hover { background: #fffcfc; }
    
    /* MTD Cards */
    .mtd-box { padding: 15px; border-radius: 8px; text-align: center; color: white; shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .mtd-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; opacity: 0.8; letter-spacing: 0.5px; }
    .mtd-value { font-size: 1.1rem; font-weight: 800; display: block; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <div class="col-12 col-md-3">
            <h3 class="h6 fw-bold text-uppercase">Navigation</h3>
            <div class="bg-white border p-2 mb-3 shadow-sm">
                <?php include '../inc/sidebar.php';?>
            </div>

            <div class="bg-white border p-3 shadow-sm rounded">
                <h5 class="text-danger small fw-bold text-uppercase mb-3"><i class="bi bi-shield-lock"></i> Recent Activity</h5>
                <div style="max-height: 450px; overflow-y: auto; overflow-x: hidden;">
                    <?php 
                    $last_date = "";
                    while($row = mysqli_fetch_assoc($login_query)): 
                        $this_date = date('D, M d', strtotime($row['login_time']));
                        if($last_date != $this_date): $last_date = $this_date; ?>
                            <span class="login-date-pill"><?= $last_date ?></span>
                        <?php endif; ?>
                        <div class="login-item d-flex justify-content-between align-items-center border-bottom-faded">
                            <div>
                                <strong class="small d-block text-dark"><?= ucwords(htmlspecialchars($row['fullname'])) ?></strong> 
                                <span class="text-muted" style="font-size: 0.6rem;">Role: <?= htmlspecialchars($row['user_role']) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-success border" style="font-size: 0.65rem;"><?= date('H:i', strtotime($row['login_time'])) ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-5">
            <h3 class="h6 fw-bold text-uppercase">Admin Portal</h3>
            
            <div class="bg-white border p-3 mb-3 shadow-sm" style="border-left: 5px solid #dc3545 !important; border-radius: 8px;">
                <div class="d-flex align-items-start">
                    <img src="<?= htmlspecialchars($profilePhoto) ?>" width="95" height="95" class="rounded-circle border border-3 border-light shadow-sm me-3" style="object-fit: cover;">
                    <div>
                        <span class="d-block h5 mb-0"><strong><?= ucwords(htmlspecialchars($fullName)); ?></strong></span>
                        <span class="d-block text-muted small"><?= ucwords(htmlspecialchars($userDesignation)); ?></span>
                        <span class="badge bg-danger mt-2">Administrator</span>
                        <span class="d-block mt-3 text-primary small fw-bold"><i class="bi bi-calendar3"></i> <?= date("D, jS M Y"); ?></span>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-4">
                    <div class="mtd-box bg-success shadow-sm">
                        <span class="mtd-label">MTD INCOME</span>
                        <span class="mtd-value">R<?= number_format($mtd_res['mtd_inc'] ?? 0, 0) ?></span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="mtd-box bg-danger shadow-sm">
                        <span class="mtd-label">MTD EXPENSE</span>
                        <span class="mtd-value">R<?= number_format($mtd_res['mtd_exp'] ?? 0, 0) ?></span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="mtd-box bg-dark shadow-sm">
                        <span class="mtd-label">MTD NET</span>
                        <span class="mtd-value">R<?= number_format($mtd_res['mtd_net'] ?? 0, 0) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white border shadow-sm rounded mb-3">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-0 fw-bold small text-uppercase"><i class="bi bi-journal-text text-danger"></i> Detailed Financial Log</h6>
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.75rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Notes / Reference</th>
                                    <th class="text-end pe-3">Net Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ch = mysqli_fetch_assoc($cashup_history)): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= date('d M', strtotime($ch['report_date'])) ?></td>
                                        <td class="text-muted italic"><?= htmlspecialchars($ch['notes']) ?></td>
                                        <td class="text-end pe-3 fw-bold <?= $ch['net_total'] >=0 ? 'text-success' : 'text-danger' ?>">
                                            R<?= number_format($ch['net_total'], 0) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <h3 class="h6 fw-bold text-uppercase">Work Status</h3>
            <div class="bg-danger text-white p-3 mb-3 shadow-sm rounded">
                <strong><i class="bi bi-briefcase"></i> Leave Management</strong>
                <div class="mt-2 bg-white text-dark p-2 rounded small">
                    <p class="mb-0">Your Balance: <strong><?= $userData['leave_balances'] ?? 0 ?> Days</strong></p>
                    <hr class="my-1">
                    <a href="/dashboard/users/leave-neg.php" class="text-danger fw-bold text-decoration-none small">Manage All Requests &rarr;</a>
                </div>
            </div>

            <div class="bg-white border p-3 shadow-sm rounded">
                <h6 class="fw-bold small text-uppercase mb-2 text-dark"><i class="bi bi-cart3"></i> Live Shop Orders</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Qty</th><th>Dept</th><th>Time</th></tr>
                        </thead>
                        <tbody>
                            <?php while($ord = mysqli_fetch_assoc($order_query)): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($ord['product']) ?></td>
                                    <td><?= $ord['quantity'] ?></td>
                                    <td><span class="text-truncate d-inline-block" style="max-width: 60px;"><?= htmlspecialchars($ord['fullname'] ?: '---') ?></span></td>
                                    <td><?= date('H:i', strtotime($ord['submitted_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <a href="/dashboard/orders/orderBoard.php" class="btn btn-sm btn-outline-dark w-100 mt-2" style="font-size: 0.7rem;">Open Order Board</a>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php';?>