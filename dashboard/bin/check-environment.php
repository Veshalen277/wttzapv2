<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/autoload.php';
ini_set('display_errors', '0');
$failed = false;
$report = static function (string $label, bool $ok) use (&$failed): void {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) $failed = true;
};
$report('PHP 8.1 or newer', PHP_VERSION_ID >= 80100);
foreach (['mysqli', 'mbstring', 'openssl', 'fileinfo'] as $extension) $report('Extension: ' . $extension, extension_loaded($extension));
$report('Composer dependencies present', is_file(dirname(__DIR__) . '/vendor/autoload.php'));
try {
    $db = \Portal\Connection::get();
    $report('Database connection', true);
    $required = ['users_tbl' => ['id', 'user_id', 'user_pass', 'work_draft']];
    foreach ($required as $table => $columns) {
        $statement = $db->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $statement->bind_param('s', $table);
        $statement->execute();
        $statement->bind_result($column);
        $actual = [];
        while ($statement->fetch()) $actual[] = $column;
        $statement->close();
        foreach ($columns as $name) $report($table . '.' . $name, in_array($name, $actual, true));
        echo 'INFO work_draft_updated_at is ' . (in_array('work_draft_updated_at', $actual, true) ? 'present' : 'absent (not required by the new draft API)') . PHP_EOL;
    }
} catch (\Throwable $error) {
    $report('Database/schema inspection (check server logs for details)', false);
    error_log('Portal environment check: ' . $error->getMessage());
}
exit($failed ? 1 : 0);
