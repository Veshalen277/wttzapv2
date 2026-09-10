<?php
include '../../header.php';

/**
 * Access Control
 */
$allowed_roles = [1,7,  2, 0];
if (in_array($role, $allowed_roles, true)) {
    header("location:../dashboard/404.php");
    exit;
}

/**
 * Session user extraction
 */
if (!isset($_SESSION['u_data'])) {
    header("Location: ../../index.php");
    exit;
}
$user = $_SESSION['u_data'];
$id = isset($user[5]) ? (int)$user[5] : 0;
if ($id <= 0) {
    header("Location: ../../index.php");
    exit;
}
// Change these three lines:
require_once __DIR__ . '/../../inc/dash_metrics.php';
require_once __DIR__ . '/../../inc/dash_widgets.php';
require_once __DIR__ . '/../../inc/schema_introspection.php';

/**
 * Admin header data
 */
$stmt = mysqli_prepare($con, "SELECT fullname, user_des, profile_photo, leave_balances FROM users_tbl WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result) ?: [];

$fullName = $userData['fullname'] ?? 'Admin';
$userDesignation = $userData['user_des'] ?? 'Management';
$db_photo = $userData['profile_photo'] ?? '';
$profilePhoto = (!empty($db_photo)) ? "../" . $db_photo : "../../employee/uploads/placeholder.jpg";

/**
 * Time window helpers
 */
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

/**
 * Metrics (these are “modules” — add more without bloating the page)
 */
$user_kpis = get_user_kpis($con, $id, $month_start, $month_end);
                 // income/expense/net if reports exists
$activity = get_user_activity_feed($con, 25); // LOGIN + WORK
                               // logins feed
$orders = get_recent_orders($con, 8);                                         // orders feed (if orders exists)
$schema = get_schema_summary($con);                                           // tables, row estimates, last update (best effort)
$fk_health = get_fk_orphan_summary($con, 12);                                 // top orphan relationships
$data_quality = get_data_quality_summary($con, 10);                           // null density, duplicate candidates, etc.
?>

<style>
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

    .kpi-box {
        padding: 14px;
        border-radius: 10px;
        color: white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        text-align: center;
    }
    .kpi-label {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        opacity: 0.85;
        letter-spacing: 0.4px;
    }
    .kpi-value {
        font-size: 1.15rem;
        font-weight: 900;
        display: block;
        margin-top: 4px;
    }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <!-- LEFT -->
        <div class="col-12 col-md-3">
            <h3 class="h6 fw-bold text-uppercase">Navigation</h3>
            <div class="bg-white border p-2 mb-3 shadow-sm">
               <?php include '../../inc/sidebar.php'; ?>
            </div>

            <div class="bg-white border p-3 shadow-sm rounded">
                <h5 class="text-danger small fw-bold text-uppercase mb-3">
                    <i class="bi bi-activity"></i> Recent Activity
                </h5>

                <div style="max-height: 450px; overflow-y: auto; overflow-x: hidden;">
                    <?php
                    $last_date = "";
                    foreach ($activity as $row):
                        $this_date = date('D, M d', strtotime($row['login_time']));
                        if ($last_date !== $this_date):
                            $last_date = $this_date; ?>
                            <span class="login-date-pill"><?= htmlspecialchars($last_date) ?></span>
                        <?php endif; ?>

                        <div class="login-item d-flex justify-content-between align-items-center border-bottom">
                            <div>
                                <strong class="small d-block text-dark">
                                    <?= ucwords(htmlspecialchars($row['fullname'] ?? 'Unknown')) ?>
                                </strong>
                                <span class="text-muted" style="font-size: 0.6rem;">
                                    Role: <?= htmlspecialchars((string)($row['user_role'] ?? '')) ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-success border" style="font-size: 0.65rem;">
                                    <?= date('H:i', strtotime($row['login_time'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="/dashboard/admin/activity.php" class="btn btn-sm btn-outline-dark w-100 mt-3" style="font-size:0.75rem;">
                    Open Activity Audit
                </a>
            </div>
        </div>

        <!-- CENTER -->
        <div class="col-12 col-md-5">
            <h3 class="h6 fw-bold text-uppercase">Admin Portal</h3>

            <div class="bg-white border p-3 mb-3 shadow-sm" style="border-left: 5px solid #dc3545 !important; border-radius: 8px;">
                <div class="d-flex align-items-start">
                    <img src="<?= htmlspecialchars($profilePhoto) ?>" width="95" height="95"
                         class="rounded-circle border border-3 border-light shadow-sm me-3" style="object-fit: cover;">
                    <div>
                        <span class="d-block h5 mb-0"><strong><?= ucwords(htmlspecialchars($fullName)); ?></strong></span>
                        <span class="d-block text-muted small"><?= ucwords(htmlspecialchars($userDesignation)); ?></span>
                        <span class="badge bg-danger mt-2">Administrator</span>
                        <span class="d-block mt-3 text-primary small fw-bold">
                            <i class="bi bi-calendar3"></i> <?= date("D, jS M Y"); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- KPI ROW -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="kpi-box bg-success">
            <span class="kpi-label">My Logins (MTD)</span>
            <span class="kpi-value"><?= (int)($user_kpis['my_logins_mtd'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-box bg-danger">
            <span class="kpi-label">My Work (MTD)</span>
            <span class="kpi-value"><?= (int)($user_kpis['my_work_mtd'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-box bg-dark">
            <span class="kpi-label">My Work (7D)</span>
            <span class="kpi-value"><?= (int)($user_kpis['my_work_7d'] ?? 0) ?></span>
        </div>
    </div>
</div>


            <!-- DATA HEALTH -->
            <div class="bg-white border shadow-sm rounded mb-3">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-0 fw-bold small text-uppercase">
                        <i class="bi bi-shield-check text-danger"></i> Data Health Overview
                    </h6>
                </div>
                <div class="p-3">
                    <?php render_data_quality_cards($data_quality); ?>
                </div>
            </div>

            <!-- FK ORPHANS -->
            <div class="bg-white border shadow-sm rounded mb-3">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold small text-uppercase">
                        <i class="bi bi-diagram-3 text-danger"></i> FK Integrity (Top Issues)
                    </h6>
                    <a href="/dashboard/admin/integrity.php" class="small fw-bold text-decoration-none">Open</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Relationship</th>
                                <th>Child Table</th>
                                <th class="text-end pe-3">Orphans</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($fk_health as $fk): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= htmlspecialchars($fk['constraint'] ?? '') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($fk['child_table'] ?? '') ?></td>
                                <td class="text-end pe-3 fw-bold text-danger"><?= number_format((int)($fk['orphans'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SCHEMA SUMMARY -->
            <div class="bg-white border shadow-sm rounded">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-0 fw-bold small text-uppercase">
                        <i class="bi bi-database text-danger"></i> Schema Summary
                    </h6>
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:0.75rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Table</th>
                                    <th>Rows (est)</th>
                                    <th class="text-end pe-3">Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($schema as $t): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= htmlspecialchars($t['table_name']) ?></td>
                                    <td><?= number_format((int)$t['rows_est']) ?></td>
                                    <td class="text-end pe-3 text-muted"><?= htmlspecialchars($t['update_time'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-3">
                    <a href="/dashboard/admin/schema.php" class="btn btn-sm btn-outline-dark w-100" style="font-size:0.75rem;">
                        Open Full Schema Explorer
                    </a>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="col-12 col-md-4">
            <h3 class="h6 fw-bold text-uppercase">Work Status</h3>

            <div class="bg-danger text-white p-3 mb-3 shadow-sm rounded">
                <strong><i class="bi bi-briefcase"></i> Leave Management</strong>
                <div class="mt-2 bg-white text-dark p-2 rounded small">
                    <p class="mb-0">Your Balance: <strong><?= (int)($userData['leave_balances'] ?? 0) ?> Days</strong></p>
                    <hr class="my-1">
                    <a href="/dashboard/users/leave-neg.php" class="text-danger fw-bold text-decoration-none small">
                        Manage All Requests &rarr;
                    </a>
                </div>
            </div>

            <div class="bg-white border p-3 shadow-sm rounded">
                <h6 class="fw-bold small text-uppercase mb-2 text-dark">
                    <i class="bi bi-cart3"></i> Recent Orders
                </h6>

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Qty</th><th>User</th><th>Time</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($ord['product'] ?? '') ?></td>
                                <td><?= (int)($ord['quantity'] ?? 0) ?></td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 80px;">
                                    <?= htmlspecialchars($ord['fullname'] ?: '---') ?>
                                </span></td>
                                <td><?= !empty($ord['submitted_at']) ? date('H:i', strtotime($ord['submitted_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <a href="/dashboard/orders/orderBoard.php" class="btn btn-sm btn-outline-dark w-100 mt-2" style="font-size: 0.7rem;">
                    Open Order Board
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../footer.php'; ?>
