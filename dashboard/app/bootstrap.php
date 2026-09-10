<?php
require_once __DIR__ . '/autoload.php';

// Application setup only. New pages process requests before including header.php.
error_reporting(E_ALL);
ini_set('display_errors', getenv('PORTAL_DEBUG') === '1' ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('Africa/Johannesburg');
$portalUser = \Portal\Auth::requireUser();

try {
    $con = \Portal\Connection::get();
    // Connection-local identity for optional transactional activity adapters.
    $con->query('SET @portal_points_actor = ' . (int) $portalUser->id);
} catch (\Throwable $error) {
    error_log('Portal bootstrap: ' . $error->getMessage());
    http_response_code(503);
    exit('The portal is temporarily unavailable. Please try again.');
}
// Compatibility aliases: existing pages continue to use their original variables.
$db = $con;
$conn = $con;
$data = $_SESSION['u_data'];
$role = $portalUser->role;
$fullName = \Portal\Html::escape($portalUser->name);
$current_month = date('m');
$current_year = date('Y');
if (!defined('BASE_URL')) define('BASE_URL', '../');
