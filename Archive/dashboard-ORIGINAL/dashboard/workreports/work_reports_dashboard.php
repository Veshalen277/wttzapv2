<?php
include '../header.php';


// echo 'Session contents: ';
// print_r($_SESSION);
$allowed_ids = [34, 60]; // Allowed numeric user IDs
$user_id = $_SESSION['u_data'][5] ?? null; // numeric ID

if (!in_array((int)$user_id, $allowed_ids)) {
    echo "<h1>Access Denied</h1><p>You are not authorized to view this page.</p>";
    echo "<p>Redirecting in <span id='countdown'>3</span> seconds...</p>";
    echo "<script>
        let seconds = 3;
        const countdown = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            countdown.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/dashboard/employee/emp_profile.php';
            }
        }, 1000);
    </script>";
    exit();
}



function array_sort($array) {
    arsort($array);
    return $array;
}

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$department_filter = $_POST['department'] ?? '';

// Base SQL
$sql = "SELECT * FROM work_tbl WHERE 1=1";
$params = [];
$types = '';

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND work_date BETWEEN ? AND ?";
    $params[] = $start_date . ' 00:00:00';
    $params[] = $end_date . ' 23:59:59';
    $types .= 'ss';
}

if (!empty($department_filter)) {
    $sql .= " AND department = ?";
    $params[] = $department_filter;
    $types .= 's';
}

$sql .= " ORDER BY work_date ASC";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$total_tasks = 0;
$tasks_per_day = [];
$tasks_by_department = [];
$tasks_by_assigned = [];

$all_departments = [];

while ($row = $result->fetch_assoc()) {
    $date = substr($row['work_date'], 0, 10);
    $dept = $row['department'] ?: 'Unknown';
    $assigned = $row['assigned_to'] ?: 'Unassigned';

    $total_tasks++;
    $tasks_per_day[$date] = ($tasks_per_day[$date] ?? 0) + 1;
    $tasks_by_department[$dept] = ($tasks_by_department[$dept] ?? 0) + 1;
    $tasks_by_assigned[$assigned] = ($tasks_by_assigned[$assigned] ?? 0) + 1;

    $all_departments[$dept] = true;
}

$stmt->close();
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
                <?php foreach (array_keys($all_departments) as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>" <?= $dept === $department_filter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="bg-light p-3 rounded shadow-sm">
                <strong>Total Tasks:</strong><br>
                <?= $total_tasks ?>
            </div>
        </div>
    </div>

    <!-- Task Distribution by Department -->
    <h5 class="mt-4">Tasks by Department</h5>
    <div class="row">
        <?php foreach ($tasks_by_department as $dept => $count): ?>
            <div class="col-md-3 mb-2">
                <div class="border p-3 rounded">
                    <strong><?= htmlspecialchars($dept) ?>:</strong><br>
                    <?= $count ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
<!-- Enhanced Charts and Tables -->
<div class="mt-5">
    <h5 class="mb-3">Work Report Visual Breakdown</h5>

    <div class="row g-4">
        <!-- Tasks by Department -->
        <div class="col-md-6 col-lg-3">
            <canvas id="chartDept"></canvas>
        </div>

        <!-- Tasks by Assignee -->
        <div class="col-md-6 col-lg-3">
            <canvas id="chartAssigned"></canvas>
        </div>

        <!-- Tasks by Date -->
        <div class="col-md-6 col-lg-3">
            <canvas id="chartDate"></canvas>
        </div>

        <!-- Tasks by Hour (Optional Insight) -->
        <div class="col-md-6 col-lg-3">
            <canvas id="chartHour"></canvas>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="row mt-5">
    <div class="col-md-6">
        <h6>Top 5 Busiest Days</h6>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Date</th><th>Tasks</th></tr></thead>
            <tbody>
            <?php
            $top_days = array_slice(array_reverse(array_sort($tasks_per_day)), 0, 5, true);
            foreach ($top_days as $day => $count): ?>
                <tr><td><?= $day ?></td><td><?= $count ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <h6>Tasks by Assignee</h6>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Assigned To</th><th>Tasks</th></tr></thead>
            <tbody>
            <?php foreach ($tasks_by_assigned as $person => $count): ?>
                <tr><td><?= htmlspecialchars($person) ?></td><td><?= $count ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-12 mt-4">
        <h6>Recent 10 Tasks</h6>
        <table class="table table-sm table-striped">
            <thead><tr><th>Date</th><th>Description</th><th>Assigned</th><th>Department</th></tr></thead>
            <tbody>
            <?php
            $stmt_recent = $con->prepare("SELECT * FROM work_tbl ORDER BY work_date DESC LIMIT 10");
            $stmt_recent->execute();
            $recent = $stmt_recent->get_result();
            while ($r = $recent->fetch_assoc()):
            ?>
                <tr>
                    <td><?= substr($r['work_date'], 0, 16) ?></td>
                    <td><?= htmlspecialchars($r['work_desc']) ?></td>
                    <td><?= htmlspecialchars($r['assigned_to']) ?></td>
                    <td><?= htmlspecialchars($r['department']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


    <!-- Export Button -->
    <div class="mt-4">
        <a href="export_work_csv.php" class="btn btn-outline-secondary">Export to CSV</a>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function renderPieChart(ctxId, labelSet, dataSet, title) {
    new Chart(document.getElementById(ctxId), {
        type: 'pie',
        data: {
            labels: labelSet,
            datasets: [{
                data: dataSet,
                backgroundColor: [
                    'rgba(255,99,132,0.5)', 'rgba(54,162,235,0.5)',
                    'rgba(255,206,86,0.5)', 'rgba(75,192,192,0.5)',
                    'rgba(153,102,255,0.5)', 'rgba(255,159,64,0.5)',
                    'rgba(0,200,100,0.5)', 'rgba(200,0,200,0.5)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: title },
                legend: { display: true, position: 'bottom' }
            }
        }
    });
}

// Tasks by Department
renderPieChart('chartDept', <?= json_encode(array_keys($tasks_by_department)) ?>, <?= json_encode(array_values($tasks_by_department)) ?>, 'By Department');

// Tasks by Assignee
renderPieChart('chartAssigned', <?= json_encode(array_keys($tasks_by_assigned)) ?>, <?= json_encode(array_values($tasks_by_assigned)) ?>, 'By Assignee');

// Tasks by Date
renderPieChart('chartDate', <?= json_encode(array_keys($tasks_per_day)) ?>, <?= json_encode(array_values($tasks_per_day)) ?>, 'By Date');

// Optional: Tasks by Hour
<?php
$tasks_by_hour = array_fill(0, 24, 0);
$stmt_hours = $con->prepare($sql);
if (!empty($params)) $stmt_hours->bind_param($types, ...$params);
$stmt_hours->execute();
$res_hours = $stmt_hours->get_result();
while ($r = $res_hours->fetch_assoc()) {
    $h = (int)date('H', strtotime($r['work_date']));
    $tasks_by_hour[$h]++;
}
$stmt_hours->close();
?>
renderPieChart('chartHour', <?= json_encode(range(0,23)) ?>, <?= json_encode($tasks_by_hour) ?>, 'By Hour (0–23)');
</script>


<?php include '../footer.php'; ?>
