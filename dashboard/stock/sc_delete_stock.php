<?php
// Compatibility route: never replay a legacy balance overwrite or GET deletion.
require __DIR__.'/../app/autoload.php';
\Portal\Auth::requireRoles([2,3,4,5,7]);
if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') {
 http_response_code(409);exit('This stock form has been replaced. Open /dashboard/inventory/index.php and submit through the new workflow.');
}
header('Location: /dashboard/inventory/index.php',true,302);exit;
