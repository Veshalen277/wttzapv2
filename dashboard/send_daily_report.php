<?php
include 'config.php';
include 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class DailyWorkReport {
    private $con;
    private $reportDate;
    private $reportFilePath;

    public function __construct($dbConnection) {
        $this->con = $dbConnection;
        $this->reportDate = date('Y-m-d'); // Use today's date
        $this->reportFilePath = 'reports/daily_work_report_' . $this->reportDate . '.txt';
    }

    public function generateReport() {
        // Create reports directory if it doesn't exist
        if (!file_exists('reports')) {
            mkdir('reports', 0755, true);
        }

        $report = "📋 DAILY WORK REPORT - " . $this->reportDate . "\n\n";
        $report .= str_repeat("=", 60) . "\n\n";

        $query = "SELECT w.*, u.fullname, u.user_scale
                  FROM work_tbl w
                  LEFT JOIN users_tbl u ON w.employee_id = u.id
                  WHERE w.work_date BETWEEN ? AND ?";

        $stmt = mysqli_prepare($this->con, $query);
        $dayStart = $this->reportDate . ' 00:00:00';
        $dayEnd = $this->reportDate . ' 23:59:59';
        mysqli_stmt_bind_param($stmt, "ss", $dayStart, $dayEnd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result)) {
            $employeeCount = 0;
            $taskCount = 0;

            while ($row = mysqli_fetch_assoc($result)) {
                $employeeCount++;
                $report .= "👤 EMPLOYEE #{$employeeCount}\n";
                $report .= "   ID: " . htmlspecialchars($row['employee_id']) . "\n";
                $report .= "   Name: " . htmlspecialchars($row['fullname']) . "\n";
                $report .= "   Department: " . htmlspecialchars($row['department']) . "\n";
                $report .= str_repeat("-", 60) . "\n";

                $report .= "📌 TASKS:\n";
                $report .= "   " . wordwrap(htmlspecialchars($row['task']), 58, "\n   ") . "\n";
                $report .= str_repeat("-", 60) . "\n";

                $report .= "📝 WORK DESCRIPTION:\n";
                $report .= "   " . wordwrap(htmlspecialchars($row['work_desc']), 58, "\n   ") . "\n";
                $report .= "\n";
                $report .= "🕒 WORK DATE: " . htmlspecialchars($row['work_date']) . "\n";
                $report .= "👥 ASSIGNED TO: " . htmlspecialchars($row['assigned_to']) . "\n";
                $report .= str_repeat("=", 60) . "\n\n";

                $taskCount++;
            }

            $report .= "\n📊 SUMMARY:\n";
            $report .= "   Total Employees: {$employeeCount}\n";
            $report .= "   Total Tasks: {$taskCount}\n";
            $report .= str_repeat("=", 60) . "\n";
        } else {
            $report .= "⚠️ No work records found for " . $this->reportDate . "\n";
        }

        // Save to file
        file_put_contents($this->reportFilePath, $report);
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
            $mail->Password   = '!Mv130369$'; // ⚠️ Consider moving this to a secure config or env variable
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom('info@wttzap.co.za', 'WTTZAP Work Report System');

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
            $mail->Subject = 'Daily Work Report - ' . $this->reportDate;
          //  $mail->Body    = $reportContent;
          $mail->Body    = "Please find attached the daily work report for {$this->reportDate}.";

            // Optional HTML version
            $htmlContent = $this->generateHtmlReport($reportContent);
            $mail->AltBody = $reportContent;

            if (file_exists($this->reportFilePath)) {
                $mail->addAttachment($this->reportFilePath);
            } else {
                error_log("Report file does not exist: {$this->reportFilePath}");
            }

            $mail->send();
            error_log("Work report email sent successfully for " . $this->reportDate);
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function generateHtmlReport($textContent) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .report { max-width: 800px; margin: 0 auto; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 2px solid #dee2e6; }
                .employee { margin-bottom: 25px; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; }
                .task { background-color: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 3px solid #6c757d; }
                .summary { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                .no-data { color: #6c757d; font-style: italic; }
            </style>
        </head>
        <body>
            <div class="report">
                <div class="header">
                    <h2>📋 Daily Work Report</h2>
                    <h3>' . $this->reportDate . '</h3>
                </div>';
        $html .= '<pre>' . nl2br(htmlspecialchars($textContent)) . '</pre>';
        $html .= '</div></body></html>';
        return $html;
    }

    public function cleanup() {
        if (file_exists($this->reportFilePath)) {
            unlink($this->reportFilePath);
        }
    }
}

// Initialize and run the report
try {
    $workReport = new DailyWorkReport($con);
    $reportContent = $workReport->generateReport();

    // Log the report content for debugging
    error_log("Generated work report content:\n" . $reportContent);

    if ($workReport->sendEmail($reportContent)) {
        error_log("Work report processed successfully");
    } else {
        error_log("Failed to send work report email");
    }

} catch (Exception $e) {
    error_log("Error generating work report: " . $e->getMessage());
} finally {
    if (isset($workReport)) {
        $workReport->cleanup();
    }

    if (isset($con)) {
        mysqli_close($con);
    }
}
?>
