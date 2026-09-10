<?php
require_once __DIR__.'/autoload.php';
require_once dirname(__DIR__).'/app/bootstrap.php';
$portalNavigationPath='/dashboard/modules/participation/index.php';
$pointsUser=$portalUser;
$pointsManager=in_array($pointsUser->role,[2,5,7],true);
$store=new \Participation\Store($con);
$pointPolicy=require __DIR__.'/rules.php';
if(!$store->installed()){
 http_response_code(503);$pageTitle='Points setup';include dirname(__DIR__).'/header.php';
 echo '<div class="alert alert-info">The new points ledger needs to be installed. Ask your administrator to run points/install.php and points/sync.php on the server.</div>';
 include dirname(__DIR__).'/footer.php';exit;
}
