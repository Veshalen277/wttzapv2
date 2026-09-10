<?php
include '../config/db.php';

$start = $_POST['start_date'] ?? date('Y-m-01');
$end = $_POST['end_date'] ?? date('Y-m-d');
$scale = $_POST['user_scale'] ?? '';

// Base query
$sql = "SELECT r.*, u.user_scale FROM reports r 
        JOIN users_tbl u ON r.user_id = u.id 
        WHERE r.report_date BETWEEN ? AND ?";
$params = [$start, $end];
$types = 'ss';

if ($scale) {
    $sql .= " AND u.user_scale = ?";
    $params[] = $scale;
    $types .= 's';
}

$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total_income = $total_expenses = $net_total = 0;
$count = 0;
$trend = [];
$dept = [];

while ($row = $result->fetch_assoc()) {
    $income = $row['income_cash'] + $row['income_card'];
    $expenses = $row['expense_cash'] + $row['airtime'];
    $net = $income - $expenses;

    $date = $row['report_date'];
    $dept_name = $row['user_scale'];

    $total_income += $income;
    $total_expenses += $expenses;
    $net_total += $net;
    $count++;

    $trend[$date] = ($trend[$date] ?? 0) + $income;
    $dept[$dept_name] = ($dept[$dept_name] ?? 0) + $income;
}

// Format for JS
$response = [
    'total_income' => number_format($total_income, 2),
    'total_expenses' => number_format($total_expenses, 2),
    'net_total' => number_format($net_total, 2),
    'count' => $count,
    'trend' => [
        'labels' => array_keys($trend),
        'totals' => array_values($trend)
    ],
    'dept' => [
        'labels' => array_keys($dept),
        'totals' => array_values($dept)
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
