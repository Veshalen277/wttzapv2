<?php
include 'config.php';
include 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class DailyReport {
    private $con;
    private $currentDate;
    
    public function __construct($dbConnection) {
        $this->con = $dbConnection;
        $this->currentDate = date('Y-m-d');
    }
    
    public function generateReport() {
        $report = "DAILY FINANCIAL REPORT - " . $this->currentDate . "\n\n";
        $report .= str_repeat("=", 50) . "\n\n";
        
        $query = "SELECT r.*, u.fullname, u.user_scale
                  FROM reports r
                  LEFT JOIN users_tbl u ON r.user_id = u.id
                  WHERE r.report_date = ?";
        
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "s", $this->currentDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result)) {
            $grandTotals = [
                'income' => 0,
                'expenses' => 0,
                'net' => 0
            ];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $report .= "USER: " . htmlspecialchars($row['fullname']) . "\n";
                $report .= "DEPARTMENT: " . htmlspecialchars($row['user_dept']) . "\n";
                $report .= str_repeat("-", 50) . "\n";
                
                // Income section
                $report .= "INCOME\n";
                $report .= sprintf("  Cash: R %10.2f\n", $row['income_cash']);
                $report .= sprintf("  Card: R %10.2f\n", $row['income_card']);
                $report .= sprintf("  Other: R %10.2f\n", $row['income_other']);
                $report .= sprintf("  TOTAL INCOME: R %10.2f\n", $row['total_income']);
                $report .= "\n";
                
                // Expenses section
                $report .= "EXPENSES\n";
                $report .= sprintf("  Cash: R %10.2f\n", $row['expense_cash']);
                $report .= sprintf("  Card: R %10.2f\n", $row['expense_card']);
                $report .= sprintf("  Airtime: R %10.2f\n", $row['airtime']);
                $report .= sprintf("  TOTAL EXPENSES: R %10.2f\n", $row['total_expenses']);
                $report .= "\n";
                
                // Net total
                $report .= "NET TOTAL: R " . sprintf("%10.2f", $row['net_total']) . "\n";
                $report .= "\n";
                
                // Notes
                $report .= "NOTES:\n" . wordwrap(htmlspecialchars($row['notes']), 50, "\n") . "\n";
                $report .= str_repeat("=", 50) . "\n\n";
                
                // Add to grand totals
                $grandTotals['income'] += $row['total_income'];
                $grandTotals['expenses'] += $row['total_expenses'];
                $grandTotals['net'] += $row['net_total'];
            }
            
            // Add grand totals
            $report .= "GRAND TOTALS\n";
            $report .= sprintf("  TOTAL INCOME: R %10.2f\n", $grandTotals['income']);
            $report .= sprintf("  TOTAL EXPENSES: R %10.2f\n", $grandTotals['expenses']);
            $report .= sprintf("  NET TOTAL: R %10.2f\n", $grandTotals['net']);
            $report .= str_repeat("=", 50) . "\n";
        } else {
            $report .= "No reports found for today.\n";
        }
        
        return $report;
    }
    
    public function sendEmail($reportContent) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = 'mail.wttzap.co.za';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'info@wttzap.co.za';
            $mail->Password   = '!Mv130369$';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';
            
            // Recipients
            $mail->setFrom('info@wttzap.co.za', 'WTTZAP Financial System');
            
            $recipients = [
                'admin@timefliesza.co.za' => 'Administrator',
                'altaafs@wtt.co.za' => 'Altaaf S',
                'accounts@wtt.co.za' => 'Accounts Department'
            ];
            
            foreach ($recipients as $email => $name) {
                $mail->addAddress($email, $name);
            }
            


     


            // Content
            $mail->isHTML(false);
            $mail->Subject = 'Daily Financial Report - ' . $this->currentDate;
            
            // Simple email body
            $mail->Body = "Please find attached the daily financial report for " . $this->currentDate . ".\n\n";
            $mail->Body .= "This is an automated message.";
            
            // Create a temporary file for the report
            $filename = 'financial_report_' . $this->currentDate . '.txt';
            file_put_contents($filename, $reportContent);
            
            // Attach the report file
            $mail->addAttachment($filename);
            
            $mail->send();
            
            // Delete the temporary file
            unlink($filename);
            
            error_log("Daily report email sent successfully on " . $this->currentDate);
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize and run the report
try {
    $dailyReport = new DailyReport($con);
    $reportContent = $dailyReport->generateReport();
    
    // Log the report content for debugging
    error_log("Generated report content:\n" . $reportContent);
    
    // Send the email
    if ($dailyReport->sendEmail($reportContent)) {
        error_log("Daily report processed successfully");
    } else {
        error_log("Failed to send daily report email");
    }
    
} catch (Exception $e) {
    error_log("Error generating report: " . $e->getMessage());
} finally {
    if (isset($con)) {
        mysqli_close($con);
    }
}
?>