<?php
// Run: php tests/work-report-render.php (no database required).
require_once __DIR__ . '/../employee/support/PageSections.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function render_employee_wall($connection, $userId, $role): void {
    echo '<div class="unfinished-widget">';
    throw new RuntimeException('Simulated missing feed table');
}

session_start();
$user = ['Employee', 'Role', 'Department', 'Complete tasks.', 7, 34];
$_SESSION['u_data'] = $user;
$originalUser = $user;
$result = ['work_draft' => '<draft>'];
$con = new class {
    public function prepare($sql) {
        throw new RuntimeException('Simulated unavailable bulletin schema');
    }
};
$BASE_DASHBOARD = dirname(__DIR__);
$visitorCountry = 'Unknown';
$visitorIP = '127.0.0.1';
$profilePhoto = '/placeholder.jpg';
$logged_in_user_id = 34;
$current_user_role = 7;
$userTasksRaw = 'Complete tasks.';
$userDes = 'Role';
ob_start();
require __DIR__ . '/../employee/pages/emp_profile.view.php';
$html = ob_get_clean();
check($user === $originalUser, 'Bulletin widget changed the employee session adapter');
check(strpos($html, 'id="workReportForm"') !== false, 'Widget failure blocked the form');
check(strpos($html, '&lt;draft&gt;') !== false, 'Draft not preserved or escaped');
check(strpos($html, 'unfinished-widget') === false, 'Partial widget markup leaked');
check(strpos($html, 'Activity feed could not be loaded') !== false, 'Missing failure notice');
check(strpos($html, 'Simulated unavailable') === false, 'Internal exception leaked');

// A schema without optional draft columns must still render a form.
$result = [];
ob_start();
require __DIR__ . '/../employee/pages/emp_profile.view.php';
$html = ob_get_clean();
check(strpos($html, 'id="workReportForm"') !== false, 'Missing draft column blocked the form');

ob_start();
\EmployeePage\PageSections::render('Healthy', static function (): void { echo 'healthy widget'; });
check(ob_get_clean() === 'healthy widget', 'Healthy widget output changed');
echo "Work report rendering checks passed.\n";
