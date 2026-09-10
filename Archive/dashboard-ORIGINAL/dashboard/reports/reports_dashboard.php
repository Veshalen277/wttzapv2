<?php
include '../header.php';

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$selected_dept = $_POST['department'] ?? '';

$dept_result = $con->query("SELECT DISTINCT user_scale FROM users_tbl ORDER BY user_scale");

$sql = "SELECT r.*, u.user_scale FROM reports r 
        LEFT JOIN users_tbl u ON r.user_id = u.id 
        WHERE r.status != 'archived'";
$params = [];
$types = '';

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND r.report_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= 'ss';
}
if (!empty($selected_dept)) {
    $sql .= " AND u.user_scale = ?";
    $params[] = $selected_dept;
    $types .= 's';
}

$sql .= " ORDER BY r.report_date ASC";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$totals = [
    'income_cash' => 0,
    'income_card' => 0,
    'total_income' => 0,
    'expense_cash' => 0,
    'airtime' => 0,
    'total_expenses' => 0,
    'net_total' => 0
];

$chart_data = [];
$expense_data = [];
$departments = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['report_date'];
    $dept = $row['user_scale'] ?: 'Unknown';

    $totals['income_cash'] += $row['income_cash'];
    $totals['income_card'] += $row['income_card'];
    $totals['total_income'] += $row['total_income'];
    $totals['expense_cash'] += $row['expense_cash'];
    $totals['airtime'] += $row['airtime'];
    $totals['total_expenses'] += $row['total_expenses'];
    $totals['net_total'] += $row['net_total'];

    $chart_data[$date][$dept] = ($chart_data[$date][$dept] ?? 0) + $row['total_income'];
    $expense_data[$date][$dept] = ($expense_data[$date][$dept] ?? 0) + $row['total_expenses'];
    $departments[$dept] = true;
}

$stmt->close();
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Reports Dashboard</h3>
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
                <option value="">All Departments</option>
                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($dept['user_scale']) ?>" <?= $selected_dept == $dept['user_scale'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['user_scale']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row text-center mb-4">
        <?php foreach ($totals as $key => $value): ?>
            <div class="col-md-3 mb-2">
                <div class="bg-light p-3 rounded shadow-sm">
                    <strong><?= ucwords(str_replace('_', ' ', $key)) ?>:</strong><br>
                    <?= number_format($value, 2) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h5 class="mt-5">Income Over Time by Department</h5>
    <canvas id="incomeChart" height="100"></canvas>

    <h5 class="mt-5">Expenses Over Time by Department</h5>
    <canvas id="expenseChart" height="100"></canvas>

    <div class="mt-4">
        <a href="export_report_csv.php" class="btn btn-success">Export CSV</a>
        <!-- <a href="export_report_pdf.php" class="btn btn-danger">Export PDF</a> -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode(array_keys($chart_data)) ?>;
const incomeRaw = <?= json_encode($chart_data) ?>;
const expenseRaw = <?= json_encode($expense_data) ?>;
const departments = <?= json_encode(array_keys($departments)) ?>;

function createStackedDatasets(dataMap) {
    return departments.map((dept, i) => {
        const color = `hsl(${i * 50 % 360}, 70%, 50%)`;
        return {
            label: dept,
            data: labels.map(date => dataMap[date]?.[dept] ?? 0),
            backgroundColor: color,
            stack: 'stack1',
            borderWidth: 1
        };
    });
}

new Chart(document.getElementById('incomeChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: createStackedDatasets(incomeRaw)
    },
    options: {
        plugins: {
            title: { display: true, text: 'Stacked Income by Department' },
            legend: { position: 'top' }
        },
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('expenseChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: createStackedDatasets(expenseRaw)
    },
    options: {
        plugins: {
            title: { display: true, text: 'Stacked Expenses by Department' },
            legend: { position: 'top' }
        },
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});
</script>

<?php include '../footer.php'; ?>
