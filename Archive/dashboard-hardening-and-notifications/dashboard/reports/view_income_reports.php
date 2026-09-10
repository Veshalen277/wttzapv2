<?php
require_once __DIR__.'/../app/bootstrap.php';
\Portal\Auth::requireRoles([2,5,6,7]);
include __DIR__.'/../header.php'; 

// Fetch user data from session
$user = $_SESSION['u_data']; 

// Check if user data is available
if (!isset($user)) {
    header("Location: ../dashboard/404.php");
    exit;
}

// Extract user role and scale from the session data
$user_role = $user[4]; // Assuming user role is stored in $user[4]
$user_scale = $user[2]; // Assuming user scale is stored in $user[2]

// Function to fetch distinct user scales
function fetchUserScales($con) {
    $scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";
    $scale_query = mysqli_query($con, $scale_sql);
    return mysqli_fetch_all($scale_query, MYSQLI_ASSOC);
}

// Function to handle delete logic
function handleDelete($con) {
    if (!isset($_POST['delete_btn'])) return;
    \Portal\Auth::requireRoles([7]);
    if (!\Portal\Csrf::valid($_POST['_csrf']??null)) {http_response_code(403);exit('Refresh the report list before changing a record.');}
    $id=(int)($_POST['delete_id']??0);$reason=$_POST['archive_reason']??null;
    if($id<1||!is_string($reason)||trim($reason)===''||strlen($reason)>255){http_response_code(400);exit('Enter a reason for archiving.');}
    $actor=(int)$_SESSION['u_data'][5];$con->begin_transaction();
    try {
        $s=$con->prepare('SELECT status FROM reports WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();
        if(!$row)throw new RuntimeException('Report not found');
        if($row['status']!=='archived') {
            $old=$row['status'];$s=$con->prepare('INSERT INTO portal_report_archives(report_id,actor_id,previous_status,reason) VALUES(?,?,?,?)');$s->bind_param('iiss',$id,$actor,$old,$reason);$s->execute();$s->close();
            $s=$con->prepare("UPDATE reports SET status='archived' WHERE id=?");$s->bind_param('i',$id);$s->execute();$s->close();
        }
        $con->commit();echo '<div class="alert alert-success">Report archived. The record has been retained.</div>';
    }catch(Throwable $error){$con->rollback();error_log('Report archive: '.$error->getMessage());echo '<div class="alert alert-danger">The report could not be archived. Ask your administrator to check the log.</div>';}
}

// Function to filter and fetch reports
function fetchReports($con) {
    $sql = "SELECT r.*, u.fullname, u.user_scale
            FROM reports r
            LEFT JOIN users_tbl u ON r.user_id = u.id
            WHERE (r.status IS NULL OR r.status != 'archived')"; // Fetch only non-archived reports

    $filter_conditions = [];
    $params = [];
    $types = '';

    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $filter_conditions[] = "r.report_date BETWEEN ? AND ?";
        $params[] = $_POST['start_date'];
        $params[] = $_POST['end_date'];
        $types .= 'ss';
    }

    if (!empty($_POST['user_scale'])) {
        $filter_conditions[] = "u.user_scale = ?";
        $params[] = $_POST['user_scale'];
        $types .= 's';
    }

    if (!empty($filter_conditions)) {
        $sql .= " AND " . implode(" AND ", $filter_conditions);
    }

    $stmt = $con->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    // Calculate totals
    $totals = [
        'income_cash' => 0,
        'income_card' => 0,
        'total_income' => 0,
        'expense_cash' => 0,
        'airtime' => 0,
        'total_expenses' => 0,
        'net_total' => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        $totals['income_cash'] += $row['income_cash'];
        $totals['income_card'] += $row['income_card'];
        $totals['total_income'] += $row['total_income'];
        $totals['expense_cash'] += $row['expense_cash'];
        $totals['airtime'] += $row['airtime'];
        $totals['total_expenses'] += $row['total_expenses'];
        $totals['net_total'] += $row['net_total'];
    }
    
    // Reset the result pointer
    $result->data_seek(0);

    return ['result' => $result, 'totals' => $totals];
}

// Handle delete logic
handleDelete($con);

// Fetch distinct user scales
$scales = fetchUserScales($con);

// Fetch and filter reports
$data = fetchReports($con);
$result = $data['result'];
$totals = $data['totals'];

// Set the date to the last full day for the date inputs
$lastFullDay = date('Y-m-d', strtotime('-1 day'));
?>

<link rel="stylesheet" href="<?= \Portal\Html::escape(\Portal\Html::asset('reports/assets/cashup.css')) ?>">
<div class="cashup-page">
<header class="cashup-heading"><div><p class="cashup-eyebrow">YOUR WORKSPACE / CASHUPS</p><h1>Cashup history</h1><p>Find a submitted cashup, review the figures, or print a report.</p></div></header>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="px-2 px-sm-4 py-2 bg-white">
                <h4 class="text-center mb-3">Income Reports</h4>

                <div class="container-fluid">
                    <!-- Print All Button -->
                    <div class="mb-3 text-center">
                        <button onclick="printTable()" class="btn btn-secondary">Print All Reports</button>
                    </div>
                    <form method="post">
                        <div class="row mb-3">
                            <!-- Filter by date range -->
                            <div class="col-12 col-md-3 mb-2 mb-md-0">
                                <label for="start_date" class="form-label">Start Date:</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? $lastFullDay) ?>">
                            </div>

                            <div class="col-12 col-md-3 mb-2 mb-md-0">
                                <label for="end_date" class="form-label">End Date:</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? $lastFullDay) ?>">
                            </div>

                            <!-- Filter by user scale -->
                            <div class="col-12 col-md-3 mb-2 mb-md-0">
                                <label for="user_scale" class="form-label">Departments:</label>
                                <select class="form-control" id="user_scale" name="user_scale">
                                    <option value="">All Dept</option>
                                    <?php foreach ($scales as $scale): ?>
                                        <option value="<?= htmlspecialchars($scale['user_scale']) ?>" <?= isset($_POST['user_scale']) && $_POST['user_scale'] == $scale['user_scale'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($scale['user_scale']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-3 d-flex align-items-end mb-2 mb-md-0">
                                <button type="submit" name="filter" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php 
                if ($result->num_rows > 0) {
                    echo "<div class='table-responsive'>
                            <table id='reportsTable' class='table table-hover table-bordered bg-white table-sm'>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Report Date</th>
                                        <th>User ID</th>
                                        <th>User Name</th>
                                        <th>User Department</th>
                                        <th>Income Cash</th>
                                        <th>Income Card</th>
                                        <th>Total Income</th>
                                        <th>Expense Cash</th>
                                        <th>Airtime</th>
                                        <th>Notes</th>
                                        <th>Total Expenses</th>
                                        <th>Net Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>";

                    while ($row = $result->fetch_assoc()) {
                        $archiveControl='';
                        if((int)$user_role===7){
                            $archiveControl='<details><summary>More options</summary><form method="post">'.\Portal\Csrf::field().'<input type="hidden" name="delete_id" value="'.(int)$row['id'].'"><label>Archive reason<input name="archive_reason" maxlength="255" required></label><button name="delete_btn" class="btn btn-outline-secondary btn-sm">Archive report</button></form></details>';
                        }
                        foreach($row as $key=>$value)$row[$key]=htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['report_date']}</td>
                                <td>{$row['user_id']}</td>
                                <td class='bg-info text-white'><h6>{$row['fullname']}</h6></td>
                                <td>{$row['user_scale']}</td>
                                <td>{$row['income_cash']}</td>
                                <td>{$row['income_card']}</td>
                                <td class='bg-success text-white'>{$row['total_income']}</td>
                                <td>{$row['expense_cash']}</td>
                                <td>{$row['airtime']}</td>
                                <td>{$row['notes']}</td>
                                <td class='bg-danger text-white'>{$row['total_expenses']}</td>
                                <td class='bg-dark text-white'>{$row['net_total']}</td>
                                <td>
                                    {$archiveControl}
                                </td>
                            </tr>";
                    }

                    echo "</tbody>
                        </table>
                        </div>";
                    echo "<div class='mt-3'>
                            <h5 class='mb-3'>Totals:</h5>
                            <div class='d-flex flex-wrap'>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Income Cash:</strong> <span class='fs-10'>{$totals['income_cash']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Income Card:</strong> <span class='fs-10'>{$totals['income_card']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Total Income:</strong> <span class='fs-10'>{$totals['total_income']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Expense Cash:</strong> <span class='fs-10'>{$totals['expense_cash']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Airtime:</strong> <span class='fs-10'>{$totals['airtime']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Total Expenses:</strong> <span class='fs-10'>{$totals['total_expenses']}</span>
                                </div>
                                <div class='p-2 flex-fill'>
                                    <strong class='fs-10'>Net Total:</strong> <span class='fs-10'>{$totals['net_total']}</span>
                                </div>
                            </div>
                        </div>";
                } else {
                    echo "<p>No reports found.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function printTable() {
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Reports</title>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(document.getElementById('reportsTable').outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>


</div>
<?php include '../footer.php'; ?>
