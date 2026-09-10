<?php
require_once '../config.php';

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user = $_SESSION['u_data'] ?? null;
if (!$user) {
    header("Location: ../dashboard/404.php");
    exit;
}

$user_role = (int)($user[4] ?? 0);
if ($user_role !== 7) {
    header("Location: ../dashboard/404.php");
    exit;
}

// config.php gives: $con and/or $db
$dbConn = null;
if (isset($db) && $db instanceof mysqli) {
    $dbConn = $db;
} elseif (isset($con) && $con instanceof mysqli) {
    $dbConn = $con;
} else {
    // in PHP 8+, mysqli_connect returns mysqli object so above should catch it
    header("Location: special_daily_report.php?error=" . urlencode("No mysqli connection found in config.php"));
    exit;
}

try {
    $dbConn->set_charset('utf8mb4');
    $dbConn->begin_transaction();

    $userId     = (int)($_POST['user_id'] ?? 0);
    $reportDate = (string)($_POST['report_date'] ?? date('Y-m-d'));
    $userDept   = (string)($_POST['user_dept'] ?? '');

    $incomeCash  = (float)($_POST['income_cash'] ?? 0);
    $incomeCard  = (float)($_POST['income_card'] ?? 0);
    $incomeOther = (float)($_POST['income_other'] ?? 0);

    $expenseCash = (float)($_POST['expense_cash'] ?? 0);
    $airtime     = (float)($_POST['airtime'] ?? 0);

    $staffAdv    = (float)($_POST['staff_advances'] ?? 0);
    $staffPur    = (float)($_POST['staff_purchases'] ?? 0);

    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($userId <= 0) throw new RuntimeException('Invalid user_id');
    if ($notes === '') throw new RuntimeException('Notes are required');

    $totalIncome   = $incomeCash + $incomeCard + $incomeOther;
    $totalExpenses = $expenseCash + $airtime + $staffAdv + $staffPur;
    $netTotal      = $totalIncome - $totalExpenses;

    // IMPORTANT: bind_param needs variables (by reference), not expressions
    $p_userId     = $userId;
    $p_userDept   = $userDept;
    $p_reportDate = $reportDate;

    $p_incomeCash  = number_format($incomeCash, 2, '.', '');
    $p_incomeCard  = number_format($incomeCard, 2, '.', '');
    $p_incomeOther = number_format($incomeOther, 2, '.', '');

    $p_expenseCash = number_format($expenseCash, 2, '.', '');
    $p_airtime     = number_format($airtime, 2, '.', '');

    $p_staffAdv    = number_format($staffAdv, 2, '.', '');
    $p_staffPur    = number_format($staffPur, 2, '.', '');

    $p_totalIncome   = number_format($totalIncome, 2, '.', '');
    $p_totalExpenses = number_format($totalExpenses, 2, '.', '');
    $p_netTotal      = number_format($netTotal, 2, '.', '');

    $p_notes = $notes;

    $sql = "
        INSERT INTO special_reports (
            user_id, user_dept, report_date,
            income_cash, income_card, income_other,
            expense_cash, airtime,
            staff_advances, staff_purchases,
            total_income, total_expenses, net_total,
            notes
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ";

    $stmt = $dbConn->prepare($sql);

    // types: i=integer, s=string (we pass money as string)
    $stmt->bind_param(
        "isssssssssssss",
        $p_userId,
        $p_userDept,
        $p_reportDate,
        $p_incomeCash,
        $p_incomeCard,
        $p_incomeOther,
        $p_expenseCash,
        $p_airtime,
        $p_staffAdv,
        $p_staffPur,
        $p_totalIncome,
        $p_totalExpenses,
        $p_netTotal,
        $p_notes
    );

    $stmt->execute();
    $stmt->close();

    $reportId = (int)$dbConn->insert_id;
    if ($reportId <= 0) throw new RuntimeException('Insert failed: no report id returned');

    // sectors
    $sectorNames = (!empty($_POST['sector_name']) && is_array($_POST['sector_name'])) ? $_POST['sector_name'] : [];
    $sectorCash  = (isset($_POST['sector_cash']) && is_array($_POST['sector_cash'])) ? $_POST['sector_cash'] : [];
    $sectorCard  = (isset($_POST['sector_card']) && is_array($_POST['sector_card'])) ? $_POST['sector_card'] : [];

    if (count($sectorNames) > 0) {
        $sectorSql = "
            INSERT INTO special_report_sectors
            (report_id, sector_name, cash_amount, card_amount)
            VALUES (?,?,?,?)
        ";
        $sectorStmt = $dbConn->prepare($sectorSql);

        for ($i = 0; $i < count($sectorNames); $i++) {
            $name = trim((string)$sectorNames[$i]);
            if ($name === '') continue;

            $cash = isset($sectorCash[$i]) ? (float)$sectorCash[$i] : 0.0;
            $card = isset($sectorCard[$i]) ? (float)$sectorCard[$i] : 0.0;

            $p_reportId = $reportId;
            $p_sectorName = $name;
            $p_cash = number_format($cash, 2, '.', '');
            $p_card = number_format($card, 2, '.', '');

            $sectorStmt->bind_param("isss", $p_reportId, $p_sectorName, $p_cash, $p_card);
            $sectorStmt->execute();
        }

        $sectorStmt->close();
    }

    $dbConn->commit();








// Include central mailer
require_once __DIR__ . '/../inc/mailer.php';

// Build subject
$subject = "Special Daily Report - {$reportDate}";

// Build clean plain-text body
$emailBody = 
    "Special Daily Report Submitted\n\n" .
    "Date: {$reportDate}\n" .
    "Department: {$userDept}\n" .
    "User ID: {$userId}\n\n" .
    "Income\n" .
    "Cash: R {$p_incomeCash}\n" .
    "Card: R {$p_incomeCard}\n" .
    "Other: R {$p_incomeOther}\n\n" .
    "Expenses\n" .
    "Expenses: R {$p_expenseCash}\n" .
    "Airtime: R {$p_airtime}\n" .
    "Staff Advances: R {$p_staffAdv}\n" .
    "Staff Purchases: R {$p_staffPur}\n\n" .
    "Total Income: R {$p_totalIncome}\n" .
    "Total Expenses: R {$p_totalExpenses}\n" .
    "Net Total: R {$p_netTotal}\n\n" .
    "Notes:\n{$p_notes}\n";

// Optional: attach PDF if you generated one
$attachments = [];
if (isset($pdfPath) && is_file($pdfPath)) {
    $attachments = ['paths' => [$pdfPath]];
}

// Send email
$result = sendEmail(
    [
        'admin@timefliesza.co.za',
        'altaafs@wtt.co.za'
    ],
    $subject,
    $emailBody,
    $attachments
);

// Handle failure silently or log it
if ($result !== true) {
    error_log("Special report email failed: " . $result);
}















    header("Location: special_daily_report.php?message=" . urlencode("Report submitted successfully (ID: $reportId)"));
    exit;

} catch (Throwable $e) {
    try { $dbConn->rollback(); } catch (Throwable $ignore) {}
    header("Location: special_daily_report.php?error=" . urlencode("Save failed: " . $e->getMessage()));
    exit;
}