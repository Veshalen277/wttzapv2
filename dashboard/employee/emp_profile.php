<?php
/* ============================================================
   FILE 1/4: /dashboard/employee/emp_profile.php  (HOLDING FILE)
   ============================================================ */

require_once __DIR__ . '/support/PageSections.php';

try {
    require __DIR__ . '/pages/emp_profile.bootstrap.php';
    require __DIR__ . '/pages/emp_profile.view.php';
    require __DIR__ . '/pages/emp_profile.submit.php';
} catch (\Throwable $error) {
    if (!headers_sent()) http_response_code(500);
    \EmployeePage\PageSections::notice('Work report', $error);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        echo '<p>Please check your saved reports before submitting again.</p>';
    }
}
