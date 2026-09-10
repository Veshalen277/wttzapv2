<?php
require_once __DIR__ . '/app/autoload.php';
$user = \Portal\Auth::requireUser();
\Portal\Http\Response::redirect($user->role === 0
    ? '/dashboard/admin/admin_profile.php'
    : '/dashboard/employee/emp_profile.php', 302);
