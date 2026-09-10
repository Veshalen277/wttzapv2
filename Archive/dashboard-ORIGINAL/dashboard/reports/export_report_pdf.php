<?php
require_once '../tcpdf/tcpdf.php'; // adjust path as needed
include '../db.php';

// Fetch filters
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

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your System');
$pdf->SetTitle('Income Report');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Income & Expense Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Date range: ' . ($start_date ?: '...') . ' to ' . ($end_date ?: '...'), 0, 1, 'C');
$pdf->Ln(5);

// Table header
$html = '<table border="1" cellpadding="4">
<tr style="background-color:#eee;">
    <th>Date</th>
    <th>Department</th>
    <th>Cash Income</th>
    <th>Card Income</th>
    <th>Total Income</th>
    <th>Cash Expense</th>
    <th>Airtime</th>
    <th>Total Expenses</th>
    <th>Net Total</th>
</tr>';

// Table rows
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['report_date']) . '</td>
        <td>' . htmlspecialchars($row['user_scale'] ?: 'Unknown') . '</td>
        <td>' . number_format($row['income_cash'], 2) . '</td>
        <td>' . number_format($row['income_card'], 2) . '</td>
        <td>' . number_format($row['total_income'], 2) . '</td>
        <td>' . number_format($row['expense_cash'], 2) . '</td>
        <td>' . number_format($row['airtime'], 2) . '</td>
        <td>' . number_format($row['total_expenses'], 2) . '</td>
        <td>' . number_format($row['net_total'], 2) . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output
$pdf->Output('report_export_' . date('Ymd_His') . '.pdf', 'D');
exit;
