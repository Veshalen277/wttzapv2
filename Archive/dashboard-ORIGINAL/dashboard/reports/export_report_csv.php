<?php
include '../db.php'; // Adjust path if needed

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=report_export_' . date('Ymd_His') . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ['Date', 'Department', 'Cash Income', 'Card Income', 'Total Income', 'Cash Expense', 'Airtime', 'Total Expenses', 'Net Total']);

// Optional filtering
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$department = $_GET['department'] ?? '';

// Build SQL
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

if (!empty($department)) {
    $sql .= " AND u.user_scale = ?";
    $params[] = $department;
    $types .= 's';
}

$sql .= " ORDER BY r.report_date ASC";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['report_date'],
        $row['user_scale'] ?: 'Unknown',
        $row['income_cash'],
        $row['income_card'],
        $row['total_income'],
        $row['expense_cash'],
        $row['airtime'],
        $row['total_expenses'],
        $row['net_total']
    ]);
}

fclose($output);
exit;
