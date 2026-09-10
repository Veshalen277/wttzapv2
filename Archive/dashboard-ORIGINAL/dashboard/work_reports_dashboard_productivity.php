<?php
include '../header.php';

/**
 * Utility: build expected dates in a range (optionally exclude weekends)
 */
function build_date_range($startYmd, $endYmd, $excludeWeekends = false) {
    if (empty($startYmd) || empty($endYmd)) return [];

    $start = new DateTime($startYmd);
    $end = new DateTime($endYmd);
    if ($end < $start) return [];

    $end->setTime(0,0,0);
    $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));

    $days = [];
    foreach ($period as $dt) {
        $dow = (int)$dt->format('N'); // 1=Mon ... 7=Sun
        if ($excludeWeekends && ($dow === 6 || $dow === 7)) continue;
        $days[] = $dt->format('Y-m-d');
    }
    return $days;
}

/**
 * Filters
 */
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$department_filter = $_POST['department'] ?? '';
$exclude_weekends = isset($_POST['exclude_weekends']) ? 1 : 0;
$cutoff_time = $_POST['cutoff_time'] ?? '17:00'; // productivity "on time" cutoff

// Normalize date inputs: if one date is set, mirror it
if (!empty($start_date) && empty($end_date)) $end_date = $start_date;
if (!empty($end_date) && empty($start_date)) $start_date = $end_date;

// Expected days array (for missed-days metric)
$expected_days = build_date_range($start_date, $end_date, (bool)$exclude_weekends);
$expected_days_count = count($expected_days);

// Department dropdown: do NOT derive from filtered results
$dept_list = [];
$dept_stmt = $con->prepare("
    SELECT DISTINCT COALESCE(NULLIF(department,''), 'Unknown') AS dept
    FROM work_tbl
    ORDER BY dept ASC
");
$dept_stmt->execute();
$dept_res = $dept_stmt->get_result();
while ($d = $dept_res->fetch_assoc()) $dept_list[] = $d['dept'];
$dept_stmt->close();

/**
 * WHERE clause for filtered data
 */
$where = "WHERE 1=1";
$params = [];
$types = "";

// Date filter
if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND work_date BETWEEN ? AND ?";
    $params[] = $start_date . " 00:00:00";
    $params[] = $end_date . " 23:59:59";
    $types .= "ss";
}

// Department filter
if (!empty($department_filter)) {
    $where .= " AND COALESCE(NULLIF(department,''),'Unknown') = ?";
    $params[] = $department_filter;
    $types .= "s";
}

function prep_bind($con, $sql, $types, $params) {
    $stmt = $con->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    return $stmt;
}

/**
 * Core aggregates (existing dashboard + fixes)
 */
$total_tasks = 0;
$tasks_per_day = [];
$tasks_by_department = [];
$tasks_by_assigned = [];

// total tasks
$stmt_total = prep_bind($con, "SELECT COUNT(*) AS c FROM work_tbl $where", $types, $params);
$stmt_total->execute();
$total_tasks = (int)($stmt_total->get_result()->fetch_assoc()['c'] ?? 0);
$stmt_total->close();

// by day
$stmt_day = prep_bind($con, "
    SELECT DATE(work_date) AS d, COUNT(*) AS c
    FROM work_tbl
    $where
    GROUP BY DATE(work_date)
    ORDER BY d ASC
", $types, $params);
$stmt_day->execute();
$res_day = $stmt_day->get_result();
while ($r = $res_day->fetch_assoc()) $tasks_per_day[$r['d']] = (int)$r['c'];
$stmt_day->close();

// by department
$stmt_dept = prep_bind($con, "
    SELECT COALESCE(NULLIF(department,''),'Unknown') AS dept, COUNT(*) AS c
    FROM work_tbl
    $where
    GROUP BY COALESCE(NULLIF(department,''),'Unknown')
    ORDER BY c DESC, dept ASC
", $types, $params);
$stmt_dept->execute();
$res_dept = $stmt_dept->get_result();
while ($r = $res_dept->fetch_assoc()) $tasks_by_department[$r['dept']] = (int)$r['c'];
$stmt_dept->close();

// by assigned (string field)
$stmt_asg = prep_bind($con, "
    SELECT COALESCE(NULLIF(assigned_to,''),'Unassigned') AS person, COUNT(*) AS c
    FROM work_tbl
    $where
    GROUP BY COALESCE(NULLIF(assigned_to,''),'Unassigned')
    ORDER BY c DESC, person ASC
", $types, $params);
$stmt_asg->execute();
$res_asg = $stmt_asg->get_result();
while ($r = $res_asg->fetch_assoc()) $tasks_by_assigned[$r['person']] = (int)$r['c'];
$stmt_asg->close();

// by hour
$tasks_by_hour = array_fill(0, 24, 0);
$stmt_hour = prep_bind($con, "
    SELECT HOUR(work_date) AS h, COUNT(*) AS c
    FROM work_tbl
    $where
    GROUP BY HOUR(work_date)
    ORDER BY h ASC
", $types, $params);
$stmt_hour->execute();
$res_hour = $stmt_hour->get_result();
while ($r = $res_hour->fetch_assoc()) {
    $h = (int)$r['h'];
    if ($h >= 0 && $h <= 23) $tasks_by_hour[$h] = (int)$r['c'];
}
$stmt_hour->close();

// recent tasks (respect filters)
$recent_tasks = [];
$stmt_recent = prep_bind($con, "
    SELECT work_date, work_desc, assigned_to, department, employee_id
    FROM work_tbl
    $where
    ORDER BY work_date DESC
    LIMIT 10
", $types, $params);
$stmt_recent->execute();
$res_recent = $stmt_recent->get_result();
while ($r = $res_recent->fetch_assoc()) $recent_tasks[] = $r;
$stmt_recent->close();

/**
 * Top 5 busiest days (FIXED: true busiest days)
 */
$top_days = $tasks_per_day;
arsort($top_days);
$top_days = array_slice($top_days, 0, 5, true);

/**
 * Staff Productivity (new)
 *
 * Assumption: users_tbl exists in same DB. If it doesn't, this still works using work_tbl.assigned_to as fallback name.
 */
$cutoff_hour = (int)substr($cutoff_time, 0, 2);
$cutoff_min = (int)substr($cutoff_time, 3, 2);
$cutoff_sql = sprintf("%02d:%02d:00", $cutoff_hour, $cutoff_min);

$staff_rows = [];
$stmt_staff = prep_bind($con, "
    SELECT
        w.employee_id,
        COALESCE(u.fullname, NULLIF(w.assigned_to,''), 'Unknown') AS staff_name,
        COALESCE(NULLIF(w.department,''),'Unknown') AS dept,
        COUNT(*) AS task_count,
        COUNT(DISTINCT DATE(w.work_date)) AS active_days,
        MAX(w.work_date) AS last_activity,
        SUM(CASE WHEN TIME(w.work_date) <= ? THEN 1 ELSE 0 END) AS on_time_count
    FROM work_tbl w
    LEFT JOIN users_tbl u ON u.id = w.employee_id
    $where
    GROUP BY w.employee_id, staff_name, dept
    ORDER BY task_count DESC, staff_name ASC
", "s" . $types, array_merge([$cutoff_sql], $params));
$stmt_staff->execute();
$res_staff = $stmt_staff->get_result();

$unique_staff = 0;
$top_performer_name = '';
$top_performer_tasks = 0;

while ($r = $res_staff->fetch_assoc()) {
    $unique_staff++;

    $task_count = (int)$r['task_count'];
    $active_days = max(1, (int)$r['active_days']); // avoid div/0
    $avg_per_active_day = $task_count / $active_days;

    $on_time = (int)$r['on_time_count'];
    $on_time_rate = $task_count > 0 ? round(($on_time / $task_count) * 100, 1) : 0;

    $missed_days = null;
    if ($expected_days_count > 0) {
        // missed days relative to expected calendar days
        $missed_days = max(0, $expected_days_count - (int)$r['active_days']);
    }

    if ($task_count > $top_performer_tasks) {
        $top_performer_tasks = $task_count;
        $top_performer_name = $r['staff_name'];
    }

    $staff_rows[] = [
        'employee_id' => $r['employee_id'],
        'staff_name' => $r['staff_name'],
        'dept' => $r['dept'],
        'task_count' => $task_count,
        'active_days' => (int)$r['active_days'],
        'avg_per_active_day' => round($avg_per_active_day, 2),
        'last_activity' => $r['last_activity'],
        'on_time_rate' => $on_time_rate,
        'missed_days' => $missed_days
    ];
}
$stmt_staff->close();

$avg_tasks_per_staff = $unique_staff > 0 ? round($total_tasks / $unique_staff, 2) : 0;
$avg_tasks_per_day = $expected_days_count > 0 ? round($total_tasks / $expected_days_count, 2) : (count($tasks_per_day) > 0 ? round($total_tasks / count($tasks_per_day), 2) : 0);

// Staff chart inputs
$staff_chart_labels = array_map(fn($x) => $x['staff_name'], $staff_rows);
$staff_chart_data = array_map(fn($x) => $x['task_count'], $staff_rows);

// Export link with filters
$export_qs = http_build_query([
    'start_date' => $start_date,
    'end_date' => $end_date,
    'department' => $department_filter,
    'exclude_weekends' => $exclude_weekends,
    'cutoff_time' => $cutoff_time
]);
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Work Reports Dashboard</h3>

    <!-- Filters -->
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-3">
            <label for="department" class="form-label">Department</label>
            <select name="department" id="department" class="form-select">
                <option value="">All</option>
                <?php foreach ($dept_list as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>" <?= ($dept === $department_filter) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="cutoff_time" class="form-label">On-time cutoff</label>
            <input type="time" name="cutoff_time" id="cutoff_time" class="form-control" value="<?= htmlspecialchars($cutoff_time) ?>">
        </div>

        <div class="col-md-6 d-flex align-items-center">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="exclude_weekends" name="exclude_weekends" <?= $exclude_weekends ? 'checked' : '' ?>>
                <label class="form-check-label" for="exclude_weekends">
                    Exclude weekends (for missed-days / per-day metrics)
                </label>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Productivity KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="bg-light p-3 rounded shadow-sm text-center">
                <div class="small text-muted">Total Tasks</div>
                <div class="fs-4 fw-bold"><?= $total_tasks ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-light p-3 rounded shadow-sm text-center">
                <div class="small text-muted">Active Staff</div>
                <div class="fs-4 fw-bold"><?= $unique_staff ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-light p-3 rounded shadow-sm text-center">
                <div class="small text-muted">Avg Tasks per Staff</div>
                <div class="fs-4 fw-bold"><?= $avg_tasks_per_staff ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-light p-3 rounded shadow-sm text-center">
                <div class="small text-muted">Avg Tasks per Day</div>
                <div class="fs-4 fw-bold"><?= $avg_tasks_per_day ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="border rounded p-3">
                <div class="small text-muted">Top performer (by tasks)</div>
                <div class="fs-5 fw-bold"><?= htmlspecialchars($top_performer_name ?: 'N/A') ?></div>
                <div class="text-muted">Tasks: <?= (int)$top_performer_tasks ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3">
                <div class="small text-muted">Productivity definition used</div>
                <div>Output volume: task entries per staff</div>
                <div>Consistency: active days and missed days (if date range selected)</div>
                <div>Timeliness: on-time % using cutoff <?= htmlspecialchars($cutoff_time) ?></div>
            </div>
        </div>
    </div>

    <!-- Existing charts (improved types) -->
    <div class="mt-4">
        <h5 class="mb-3">Work Report Visual Breakdown</h5>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3"><canvas id="chartDept"></canvas></div>
            <div class="col-md-6 col-lg-3"><canvas id="chartAssigned"></canvas></div>
            <div class="col-md-12 col-lg-6"><canvas id="chartDate"></canvas></div>
            <div class="col-md-12"><canvas id="chartHour"></canvas></div>
        </div>
    </div>

    <!-- New: Staff Productivity -->
    <div class="mt-5">
        <h5 class="mb-3">Staff Productivity</h5>

        <div class="row g-4">
            <div class="col-md-12">
                <canvas id="chartStaff"></canvas>
            </div>

            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Department</th>
                                <th class="text-end">Tasks</th>
                                <th class="text-end">Active Days</th>
                                <th class="text-end">Avg / Active Day</th>
                                <th class="text-end">On-time %</th>
                                <th>Last Activity</th>
                                <th class="text-end">Missed Days</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($staff_rows)): ?>
                            <tr><td colspan="8" class="text-muted">No staff activity found for the selected filters</td></tr>
                        <?php else: ?>
                            <?php foreach ($staff_rows as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['staff_name']) ?></td>
                                    <td><?= htmlspecialchars($s['dept']) ?></td>
                                    <td class="text-end"><?= (int)$s['task_count'] ?></td>
                                    <td class="text-end"><?= (int)$s['active_days'] ?></td>
                                    <td class="text-end"><?= htmlspecialchars($s['avg_per_active_day']) ?></td>
                                    <td class="text-end"><?= htmlspecialchars($s['on_time_rate']) ?>%</td>
                                    <td><?= htmlspecialchars(substr((string)$s['last_activity'], 0, 16)) ?></td>
                                    <td class="text-end">
                                        <?= ($s['missed_days'] === null) ? '<span class="text-muted">N/A</span>' : (int)$s['missed_days'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="small text-muted">
                    Missed Days only calculates when you select a Start Date and End Date.
                </div>
            </div>
        </div>
    </div>

    <!-- Tables (existing + fixed busiest days) -->
    <div class="row mt-5 g-4">
        <div class="col-md-6">
            <h6>Top 5 Busiest Days</h6>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Date</th><th>Tasks</th></tr></thead>
                <tbody>
                <?php if (empty($top_days)): ?>
                    <tr><td colspan="2" class="text-muted">No data</td></tr>
                <?php else: ?>
                    <?php foreach ($top_days as $day => $count): ?>
                        <tr><td><?= htmlspecialchars($day) ?></td><td><?= (int)$count ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="col-md-6">
            <h6>Tasks by Assignee (text field)</h6>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Assigned To</th><th>Tasks</th></tr></thead>
                <tbody>
                <?php if (empty($tasks_by_assigned)): ?>
                    <tr><td colspan="2" class="text-muted">No data</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks_by_assigned as $person => $count): ?>
                        <tr><td><?= htmlspecialchars($person) ?></td><td><?= (int)$count ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="col-md-12">
            <h6>Recent 10 Tasks (Filtered)</h6>
            <table class="table table-sm table-striped">
                <thead><tr><th>Date</th><th>Description</th><th>Assigned</th><th>Department</th></tr></thead>
                <tbody>
                <?php if (empty($recent_tasks)): ?>
                    <tr><td colspan="4" class="text-muted">No tasks found</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_tasks as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr((string)$r['work_date'], 0, 16)) ?></td>
                            <td><?= htmlspecialchars((string)$r['work_desc']) ?></td>
                            <td><?= htmlspecialchars((string)($r['assigned_to'] ?: 'Unassigned')) ?></td>
                            <td><?= htmlspecialchars((string)($r['department'] ?: 'Unknown')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export -->
    <div class="mt-4">
        <a href="export_work_csv.php?<?= $export_qs ?>" class="btn btn-outline-secondary">Export to CSV (Filtered)</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const deptLabels = <?= json_encode(array_keys($tasks_by_department)) ?>;
const deptData   = <?= json_encode(array_values($tasks_by_department)) ?>;

const asgLabels  = <?= json_encode(array_keys($tasks_by_assigned)) ?>;
const asgData    = <?= json_encode(array_values($tasks_by_assigned)) ?>;

const dayLabels  = <?= json_encode(array_keys($tasks_per_day)) ?>;
const dayData    = <?= json_encode(array_values($tasks_per_day)) ?>;

const hourLabels = <?= json_encode(range(0,23)) ?>;
const hourData   = <?= json_encode(array_values($tasks_by_hour)) ?>;

const staffLabels = <?= json_encode($staff_chart_labels) ?>;
const staffData   = <?= json_encode($staff_chart_data) ?>;

function doughnut(ctxId, labels, data, title) {
  new Chart(document.getElementById(ctxId), {
    type: 'doughnut',
    data: { labels, datasets: [{ data }] },
    options: {
      responsive: true,
      plugins: { title: { display: true, text: title }, legend: { position: 'bottom' } }
    }
  });
}
function line(ctxId, labels, data, title) {
  new Chart(document.getElementById(ctxId), {
    type: 'line',
    data: { labels, datasets: [{ label: title, data, tension: 0.25 }] },
    options: {
      responsive: true,
      plugins: { title: { display: true, text: title }, legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });
}
function bar(ctxId, labels, data, title) {
  new Chart(document.getElementById(ctxId), {
    type: 'bar',
    data: { labels, datasets: [{ label: title, data }] },
    options: {
      responsive: true,
      plugins: { title: { display: true, text: title }, legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });
}

doughnut('chartDept', deptLabels, deptData, 'Tasks by Department');
doughnut('chartAssigned', asgLabels, asgData, 'Tasks by Assignee');
line('chartDate', dayLabels, dayData, 'Tasks Over Time (Daily)');
bar('chartHour', hourLabels, hourData, 'Tasks by Hour');
bar('chartStaff', staffLabels, staffData, 'Tasks per Staff (Productivity)');
</script>

<?php include '../footer.php'; ?>
