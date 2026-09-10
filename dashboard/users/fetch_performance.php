<?php
include '../header.php'; // Include necessary initialization
include '../functions.php'; // Include any required functions

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Prepare the base query
$query = "
    SELECT u.id, u.fullname, 
           COUNT(w.id) AS report_count,
           COUNT(DISTINCT w.task) AS tasks_completed,
           MAX(w.work_date) AS last_report_date
    FROM users_tbl u
    LEFT JOIN work_tbl w ON u.id = w.employee_id
";

// Initialize conditions array
$conditions = [];
if ($start_date) {
    $conditions[] = "w.work_date >= '" . mysqli_real_escape_string($con, $start_date) . "'";
}
if ($end_date) {
    $conditions[] = "w.work_date <= '" . mysqli_real_escape_string($con, $end_date) . "'";
}

// Append conditions if any
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Finalize the query
$query .= " GROUP BY u.id ORDER BY report_count DESC";

// Execute the query
$result = mysqli_query($con, $query);
if (!$result) {
    die(json_encode(['error' => "Database query failed: " . mysqli_error($con)]));
}

// Prepare data for JSON response
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'fullname' => htmlspecialchars($row['fullname']),
        'report_count' => (int)$row['report_count'],
        'tasks_completed' => (int)$row['tasks_completed'],
        'last_report_date' => !empty($row['last_report_date']) ? date('Y-m-d', strtotime($row['last_report_date'])) : 'N/A'
    ];
}

// Return JSON response
echo json_encode($data);
?>
