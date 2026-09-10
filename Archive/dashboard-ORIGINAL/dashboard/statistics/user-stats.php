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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: all-stats.php");
    exit();
}

[$from, $to, $range] = stats_date_range($_GET);

$userRes = mysqli_query($con, "SELECT * FROM `users_tbl` WHERE `id` = {$id} LIMIT 1");
if (!$userRes || mysqli_num_rows($userRes) === 0) {
    header("Location: all-stats.php");
    exit();
}
$user = mysqli_fetch_assoc($userRes);
$stats = stats_build_user_stats($con, $user, $from, $to);

$rankedDomains = stats_build_ranking($con, $from, $to);
$userRank = stats_find_user_rank($rankedDomains, $id);

$img = $stats['image_path'];
$imgFs = $img ? $_SERVER['DOCUMENT_ROOT'] . $img : '';
$hasImg = $img && file_exists($imgFs);

function recent_rows(mysqli $con, string $table, string $where, string $orderCol, int $limit = 10): array {
    if (!stats_table_exists($con, $table) || !stats_has_column($con, $table, $orderCol)) {
        return [];
    }
    $res = mysqli_query($con, "SELECT * FROM `{$table}` WHERE {$where} ORDER BY `{$orderCol}` DESC LIMIT {$limit}");
    return $res ? stats_fetch_all($res) : [];
}

$recentLogins = stats_has_column($con, 'user_logins', 'login_time')
    ? recent_rows($con, 'user_logins', "`user_id` = {$id}", 'login_time')
    : (stats_has_column($con, 'user_logins', 'created_at') ? recent_rows($con, 'user_logins', "`user_id` = {$id}", 'created_at') : []);

$recentReports = stats_has_column($con, 'reports', 'report_date')
    ? recent_rows($con, 'reports', "`user_id` = {$id}", 'report_date')
    : [];

$recentWork = stats_has_column($con, 'work_tbl', 'work_date')
    ? recent_rows($con, 'work_tbl', "`employee_id` = {$id}", 'work_date')
    : (stats_has_column($con, 'work_tbl', 'created_at') ? recent_rows($con, 'work_tbl', "`employee_id` = {$id}", 'created_at') : []);

$recentLeave = stats_has_column($con, 'leave_applications', 'start_date')
    ? recent_rows($con, 'leave_applications', "`user_id` = {$id}", 'start_date')
    : [];

$queryStringBase = http_build_query([
    'range' => $range,
    'from'  => $from,
    'to'    => $to,
]);
?>
<style>
.stats-page .box,.stats-page .panel{border:1px solid #dee2e6;border-radius:14px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.stats-page .panel{padding:18px}
.stats-page .box{padding:14px;height:100%}
.stats-page .label{font-size:12px;color:#6c757d;text-transform:uppercase;letter-spacing:.05em}
.stats-page .value{font-size:24px;font-weight:700;margin-top:6px}
.stats-page .mini-section{border:1px solid #edf0f2;border-radius:10px;background:#fafbfc;padding:12px;height:100%}
.stats-page .mini-section h6{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#495057;margin-bottom:10px}
.stats-page .metric{display:flex;justify-content:space-between;gap:10px;font-size:14px;margin-bottom:6px}
.stats-page .avatar{width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid #ddd;background:#f8f9fa}
</style>

<div class="container-fluid stats-page">
    <div class="row">
        <div class="col-12">
            <div class="container-fluid mt-2 py-3 panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($hasImg): ?>
                            <img src="<?= stats_h($img) ?>" alt="User Photo" class="avatar">
                        <?php else: ?>
                            <img src="/dashboard/employee/uploads/placeholder.jpg" alt="Default Photo" class="avatar">
                        <?php endif; ?>

                        <div>
                            <h4 class="mb-1">#<?= stats_h($user['id']) ?> — <?= stats_h($user['fullname'] ?? '') ?></h4>
                            <div class="text-muted small">
                                <?= stats_h($user['email'] ?? '') ?>
                                <?php if (!empty($user['user_scale'])): ?> • <?= stats_h($user['user_scale']) ?><?php endif; ?>
                                <?php if (!empty($user['user_des'])): ?> • <?= stats_h($user['user_des']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="all-stats.php?<?= $queryStringBase ?>" class="btn btn-outline-secondary btn-sm">Back to All Stats</a>
                        <a href="rankings.php?<?= $queryStringBase ?>" class="btn btn-dark btn-sm">Rankings</a>
                    </div>
                </div>

                <form method="get" class="row">
                    <input type="hidden" name="id" value="<?= (int)$id ?>">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Range</label>
                        <select class="form-select" name="range">
                            <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>Week</option>
                            <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>Month</option>
                            <option value="year" <?= $range === 'year' ? 'selected' : '' ?>>Year</option>
                            <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="from" value="<?= stats_h($from) ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="to" value="<?= stats_h($to) ?>">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>

            <div class="container-fluid mt-3 py-3 panel">
                <div class="row g-3">
                    <div class="col-md-3"><div class="box"><div class="label">Overall Score</div><div class="value"><?= number_format($stats['overall_score']) ?></div></div></div>
                    <div class="col-md-3"><div class="box"><div class="label">Domain</div><div class="value"><?= stats_h($userRank['domain']) ?></div></div></div>
                    <div class="col-md-3"><div class="box"><div class="label">Rank in Domain</div><div class="value"><?= $userRank['rank_in_domain'] ? '#' . number_format($userRank['rank_in_domain']) : '-' ?></div></div></div>
                    <div class="col-md-3"><div class="box"><div class="label">Range</div><div class="value" style="font-size:18px"><?= stats_h($from) ?> → <?= stats_h($to) ?></div></div></div>
                </div>
            </div>

            <div class="container-fluid mt-3 py-3 panel">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Login Stats</h6>
                            <div class="metric"><span>Total</span><strong><?= number_format($stats['logins_count']) ?></strong></div>
                            <div class="metric"><span>First</span><strong><?= stats_h($stats['first_login']) ?></strong></div>
                            <div class="metric"><span>Last</span><strong><?= stats_h($stats['last_login']) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Work</h6>
                            <div class="metric"><span>Entries</span><strong><?= number_format($stats['work_count']) ?></strong></div>
                            <div class="metric"><span>Tasks</span><strong><?= number_format($stats['adv_tasks']) ?></strong></div>
                            <div class="metric"><span>Checklist</span><strong><?= number_format($stats['checklist_done']) ?>/<?= number_format($stats['checklist_items']) ?></strong></div>
                            <div class="metric"><span>Manager Reports</span><strong><?= number_format($stats['manager_reports']) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Cash-Ups</h6>
                            <div class="metric"><span>Reports</span><strong><?= number_format($stats['reports_count']) ?></strong></div>
                            <div class="metric"><span>Mauritius</span><strong><?= number_format($stats['mauritius_reports_count']) ?></strong></div>
                            <div class="metric"><span>Income</span><strong>R <?= number_format($stats['combined_income'], 2) ?></strong></div>
                            <div class="metric"><span>Expenses</span><strong>R <?= number_format($stats['combined_expenses'], 2) ?></strong></div>
                            <div class="metric"><span>Net</span><strong>R <?= number_format($stats['combined_net'], 2) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Consistency</h6>
                            <div class="metric"><span>Days</span><strong><?= number_format($stats['distinct_days']) ?></strong></div>
                            <div class="metric"><span>Longest</span><strong><?= number_format($stats['longest_streak']) ?></strong></div>
                            <div class="metric"><span>Current</span><strong><?= number_format($stats['current_streak']) ?></strong></div>
                            <div class="metric"><span>Last</span><strong><?= stats_h($stats['last_report_date']) ?></strong></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Leave</h6>
                            <div class="metric"><span>Total</span><strong><?= number_format($stats['leave_count']) ?></strong></div>
                            <div class="metric"><span>Approved</span><strong><?= number_format($stats['approved_leave']) ?></strong></div>
                            <div class="metric"><span>Pending</span><strong><?= number_format($stats['pending_leave']) ?></strong></div>
                            <div class="metric"><span>Disapproved</span><strong><?= number_format($stats['disapproved_leave']) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Communication</h6>
                            <div class="metric"><span>Forum</span><strong><?= number_format($stats['messages_posted']) ?></strong></div>
                            <div class="metric"><span>Replies</span><strong><?= number_format($stats['replies_posted']) ?></strong></div>
                            <div class="metric"><span>Posts</span><strong><?= number_format($stats['posts_created']) ?></strong></div>
                            <div class="metric"><span>PM Sent</span><strong><?= number_format($stats['pm_sent']) ?></strong></div>
                            <div class="metric"><span>PM Received</span><strong><?= number_format($stats['pm_received']) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Projects</h6>
                            <div class="metric"><span>Assigned</span><strong><?= number_format($stats['projects_assigned']) ?></strong></div>
                            <div class="metric"><span>Created</span><strong><?= number_format($stats['projects_created']) ?></strong></div>
                            <div class="metric"><span>Assigned By</span><strong><?= number_format($stats['projects_assigned_by']) ?></strong></div>
                            <div class="metric"><span>Groups</span><strong><?= number_format($stats['group_memberships']) ?></strong></div>
                            <div class="metric"><span>Group Msgs</span><strong><?= number_format($stats['group_messages']) ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="mini-section">
                            <h6>Reads & Notifications</h6>
                            <div class="metric"><span>Post React</span><strong><?= number_format($stats['post_reactions']) ?></strong></div>
                            <div class="metric"><span>Item React</span><strong><?= number_format($stats['item_reactions']) ?></strong></div>
                            <div class="metric"><span>Acks</span><strong><?= number_format($stats['ack_count']) ?></strong></div>
                            <div class="metric"><span>Reads</span><strong><?= number_format($stats['bulletin_reads']) ?></strong></div>
                            <div class="metric"><span>Unread</span><strong><?= number_format($stats['unread_notifications']) ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mt-3 py-3 panel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Recent Logins</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>ID</th><th>Time</th></tr></thead>
                                <tbody>
                                <?php if ($recentLogins): foreach ($recentLogins as $row): ?>
                                    <tr>
                                        <td><?= stats_h($row['id'] ?? '') ?></td>
                                        <td><?= stats_h($row['login_time'] ?? ($row['created_at'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="2" class="text-muted">No login records</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6>Recent Leave</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>ID</th><th>Type</th><th>Start</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php if ($recentLeave): foreach ($recentLeave as $row): ?>
                                    <tr>
                                        <td><?= stats_h($row['id'] ?? '') ?></td>
                                        <td><?= stats_h($row['leave_type'] ?? '') ?></td>
                                        <td><?= stats_h($row['start_date'] ?? '') ?></td>
                                        <td><?= stats_h($row['status'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-muted">No leave records</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6>Recent Reports</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>ID</th><th>Date</th><th>Income</th><th>Net</th></tr></thead>
                                <tbody>
                                <?php if ($recentReports): foreach ($recentReports as $row): ?>
                                    <tr>
                                        <td><?= stats_h($row['id'] ?? '') ?></td>
                                        <td><?= stats_h($row['report_date'] ?? '') ?></td>
                                        <td>R <?= number_format((float)($row['total_income'] ?? 0), 2) ?></td>
                                        <td>R <?= number_format((float)($row['net_total'] ?? 0), 2) ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-muted">No report records</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6>Recent Work</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>ID</th><th>Date</th><th>Task</th></tr></thead>
                                <tbody>
                                <?php if ($recentWork): foreach ($recentWork as $row): ?>
                                    <tr>
                                        <td><?= stats_h($row['id'] ?? '') ?></td>
                                        <td><?= stats_h($row['work_date'] ?? ($row['created_at'] ?? '')) ?></td>
                                        <td><?= stats_h($row['task'] ?? ($row['work_desc'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="3" class="text-muted">No work records</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>