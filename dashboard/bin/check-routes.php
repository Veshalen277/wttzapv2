<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/autoload.php';
require_once dirname(__DIR__) . '/menu_config.php';
$seen = [];
$missing = 0;
$check = static function (array $items) use (&$check, &$seen, &$missing): void {
    foreach ($items as $item) {
        if (isset($item['submenu'])) { $check($item['submenu']); continue; }
        $path = parse_url($item['link'] ?? '', PHP_URL_PATH) ?: '';
        if (strpos($path, '/dashboard/') !== 0 || isset($seen[$path])) continue;
        $seen[$path] = true;
        if (!is_file(dirname(__DIR__) . substr($path, strlen('/dashboard')))) {
            echo 'MISSING ' . $path . PHP_EOL;
            $missing++;
        }
    }
};
foreach (range(0, 7) as $role) $check(array_merge(getMenuItems($role), \Portal\Modules::menu($role)));
echo count($seen) . " unique routes inspected; {$missing} missing. No changes made.\n";
exit($missing ? 1 : 0);
