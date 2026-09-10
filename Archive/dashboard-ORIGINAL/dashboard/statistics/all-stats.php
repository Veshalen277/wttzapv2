<?php
include '../header.php';
require_once __DIR__ . '/includes/stats-helpers.php';

if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: ../404.php");
    exit();
}
if ($role == 1) {
    header("Location: ../emp_profile.php");
    exit();
}

[$from, $to, $range] = stats_date_range($_GET);

$filters = [
    'search_user' => trim($_GET['search_user'] ?? ''),
    'department'  => trim($_GET['department'] ?? ''),
    'email'       => trim($_GET['email'] ?? ''),
];

$records_per_page = isset($_GET['show_all']) ? 100000 : 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$total_users = stats_count_users($con);
$total_filtered_users = stats_count_users($con, $filters);
$total_pages = max(1, (int)ceil($total_filtered_users / $records_per_page));

$users = stats_get_users($con, $filters, $records_per_page, $offset);

$userStats = [];
foreach ($users as $user) {
    $userStats[] = stats_build_user_stats($con, $user, $from, $to);
}

$total_logins = stats_table_exists($con, 'user_logins') ? (int)stats_qv($con, "SELECT COUNT(*) FROM `user_logins`", 0) : 0;
$total_reports = stats_table_exists($con, 'reports') ? (int)stats_qv($con, "SELECT COUNT(*) FROM `reports`", 0) : 0;
$total_mauritius_reports = stats_table_exists($con, 'mauritius_reports') ? (int)stats_qv($con, "SELECT COUNT(*) FROM `mauritius_reports`", 0) : 0;
$total_work_entries = stats_table_exists($con, 'work_tbl') ? (int)stats_qv($con, "SELECT COUNT(*) FROM `work_tbl`", 0) : 0;
$total_leave = stats_table_exists($con, 'leave_applications') ? (int)stats_qv($con, "SELECT COUNT(*) FROM `leave_applications`", 0) : 0;

$total_income_reports = stats_has_column($con, 'reports', 'total_income') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`total_income`),0) FROM `reports`", 0) : 0;
$total_income_mru = stats_has_column($con, 'mauritius_reports', 'total_income') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`total_income`),0) FROM `mauritius_reports`", 0) : 0;
$total_exp_reports = stats_has_column($con, 'reports', 'total_expenses') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`total_expenses`),0) FROM `reports`", 0) : 0;
$total_exp_mru = stats_has_column($con, 'mauritius_reports', 'total_expenses') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`total_expenses`),0) FROM `mauritius_reports`", 0) : 0;
$total_net_reports = stats_has_column($con, 'reports', 'net_total') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`net_total`),0) FROM `reports`", 0) : 0;
$total_net_mru = stats_has_column($con, 'mauritius_reports', 'net_total') ? (float)stats_qv($con, "SELECT COALESCE(SUM(`net_total`),0) FROM `mauritius_reports`", 0) : 0;

$queryStringBase = http_build_query([
    'search_user' => $filters['search_user'],
    'department'  => $filters['department'],
    'email'       => $filters['email'],
    'range'       => $range,
    'from'        => $from,
    'to'          => $to,
]);
?>
<style>
.stats-page .summary-card,.stats-page .user-stat-card,.stats-page .filter-panel{border:1px solid #dee2e6;border-radius:14px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.stats-page .summary-card{padding:16px;height:100%}
.stats-page .summary-label{font-size:12px;color:#6c757d;text-transform:uppercase;letter-spacing:.05em}
.stats-page .summary-value{font-size:24px;font-weight:700;margin-top:6px;line-height:1.2}
.stats-page .user-stat-card{padding:18px}
.stats-page .user-top{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.stats-page .user-meta{display:flex;gap:14px;align-items:center}
.stats-page .user-avatar{width:58px;height:58px;border-radius:10px;object-fit:cover;border:1px solid #ddd;background:#f8f9fa}
.stats-page .user-name{font-size:18px;font-weight:700;margin:0}
.stats-page .user-sub{font-size:13px;color:#6c757d}
.stats-page .mini-section{border:1px solid #edf0f2;border-radius:10px;background:#fafbfc;padding:12px;height:100%}
.stats-page .mini-section h6{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#495057;margin-bottom:10px}
.stats-page .metric{display:flex;justify-content:space-between;gap:10px;font-size:14px;margin-bottom:6px}
.stats-page .metric:last-child{margin-bottom:0}
.stats-page .metric strong{white-space:nowrap}
</style>

<div class="container-fluid stats-page">
    <div class="row">
        <div class="col-12">
            <div class="container-fluid mt-2 py-3 filter-panel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0">Employee Statistics Dashboard</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="all-stats.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <a href="rankings.php?<?= $queryStringBase ?>" class="btn btn-dark btn-sm">Rankings</a>
                    </div>
                </div>

                <form method="get">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Search by User</label>
                            <input type="text" class="form-control border border-danger" name="search_user" value="<?= stats_h($filters['search_user']) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Department / Domain</label>
                            <input type="text" class="form-control" name="department" value="<?= stats_h($filters['department']) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" name="email" value="<?= stats_h($filters['email']) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Range</label>
                            <select class="form-select" name="range">
                                <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>Week</option>
                                <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>Month</option>
                                <option value="year" <?= $range === 'year' ? 'selected' : '' ?>>Year</option>
                                <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" name="from" value="<?= stats_h($from) ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" name="to" value="<?= stats_h($to) ?>">
                        </div>
                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>

                <hr>

                <div class="row g-3">
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Total Users</div><div class="summary-value"><?= number_format($total_users) ?></div></div></div>
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Filtered Users</div><div class="summary-value"><?= number_format($total_filtered_users) ?></div></div></div>
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Total Logins</div><div class="summary-value"><?= number_format($total_logins) ?></div></div></div>
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Cash-Ups</div><div class="summary-value"><?= number_format($total_reports + $total_mauritius_reports) ?></div></div></div>
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Work Entries</div><div class="summary-value"><?= number_format($total_work_entries) ?></div></div></div>
                    <div class="col-md-2 col-sm-6"><div class="summary-card"><div class="summary-label">Leave Apps</div><div class="summary-value"><?= number_format($total_leave) ?></div></div></div>

                    <div class="col-md-4"><div class="summary-card"><div class="summary-label">Combined Income</div><div class="summary-value">R <?= number_format($total_income_reports + $total_income_mru, 2) ?></div></div></div>
                    <div class="col-md-4"><div class="summary-card"><div class="summary-label">Combined Expenses</div><div class="summary-value">R <?= number_format($total_exp_reports + $total_exp_mru, 2) ?></div></div></div>
                    <div class="col-md-4"><div class="summary-card"><div class="summary-label">Combined Net</div><div class="summary-value">R <?= number_format($total_net_reports + $total_net_mru, 2) ?></div></div></div>
                </div>
            </div>

            <div class="container-fluid mt-3 py-3 bg-white filter-panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">All Users</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="text-muted small align-self-center">Range: <?= stats_h($from) ?> to <?= stats_h($to) ?></div>
                        <a href="all-stats.php?<?= $queryStringBase ?>&show_all=1" class="btn btn-outline-dark btn-sm">Show All</a>
                    </div>
                </div>

                <?php if (!empty($userStats)): ?>
                    <div class="row g-3">
                        <?php foreach ($userStats as $s): ?>
                            <?php
                            $u = $s['user'];
                            $uid = (int)$u['id'];
                            $img = $s['image_path'];
                            $imgFs = $img ? $_SERVER['DOCUMENT_ROOT'] . $img : '';
                            $hasImg = $img && file_exists($imgFs);
                            ?>
                            <div class="col-12">
                                <div class="user-stat-card">
                                    <div class="user-top">
                                        <div class="user-meta">
                                            <?php if ($hasImg): ?>
                                                <img src="<?= stats_h($img) ?>" alt="User Photo" class="user-avatar">
                                            <?php else: ?>
                                                <img src="/dashboard/employee/uploads/placeholder.jpg" alt="Default Photo" class="user-avatar">
                                            <?php endif; ?>

                                            <div>
                                                <h5 class="user-name">#<?= stats_h($u['id']) ?> — <?= stats_h($u['fullname'] ?? '') ?></h5>
                                                <div class="user-sub">
                                                    <?= stats_h($u['email'] ?? '') ?>
                                                    <?php if (!empty($u['user_scale'])): ?> • <?= stats_h($u['user_scale']) ?><?php endif; ?>
                                                    <?php if (!empty($u['user_des'])): ?> • <?= stats_h($u['user_des']) ?><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="user-stats.php?id=<?= $uid ?>&<?= $queryStringBase ?>" class="btn btn-dark btn-sm">View Full Stats</a>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-3 col-md-6">
                                            <div class="mini-section">
                                                <h6>Login Stats</h6>
                                                <div class="metric"><span>Total</span><strong><?= number_format($s['logins_count']) ?></strong></div>
                                                <div class="metric"><span>First</span><strong><?= stats_h($s['first_login']) ?></strong></div>
                                                <div class="metric"><span>Last</span><strong><?= stats_h($s['last_login']) ?></strong></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="mini-section">
                                                <h6>Work</h6>
                                                <div class="metric"><span>Entries</span><strong><?= number_format($s['work_count']) ?></strong></div>
                                                <div class="metric"><span>Tasks</span><strong><?= number_format($s['adv_tasks']) ?></strong></div>
                                                <div class="metric"><span>Checklist</span><strong><?= number_format($s['checklist_done']) ?>/<?= number_format($s['checklist_items']) ?></strong></div>
                                                <div class="metric"><span>Manager Reports</span><strong><?= number_format($s['manager_reports']) ?></strong></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="mini-section">
                                                <h6>Cash-Ups</h6>
                                                <div class="metric"><span>Total</span><strong><?= number_format($s['combined_cashups']) ?></strong></div>
                                                <div class="metric"><span>Income</span><strong>R <?= number_format($s['combined_income'], 2) ?></strong></div>
                                                <div class="metric"><span>Expenses</span><strong>R <?= number_format($s['combined_expenses'], 2) ?></strong></div>
                                                <div class="metric"><span>Net</span><strong>R <?= number_format($s['combined_net'], 2) ?></strong></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="mini-section">
                                                <h6>Consistency</h6>
                                                <div class="metric"><span>Days</span><strong><?= number_format($s['distinct_days']) ?></strong></div>
                                                <div class="metric"><span>Longest</span><strong><?= number_format($s['longest_streak']) ?></strong></div>
                                                <div class="metric"><span>Current</span><strong><?= number_format($s['current_streak']) ?></strong></div>
                                                <div class="metric"><span>Score</span><strong><?= number_format($s['overall_score']) ?></strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No records found</div>
                <?php endif; ?>

                <?php if (empty($_GET['show_all']) && $total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center flex-wrap">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= $queryStringBase ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>