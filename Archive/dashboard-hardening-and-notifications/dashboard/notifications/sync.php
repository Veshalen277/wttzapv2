<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/autoload.php';require __DIR__.'/Service.php';
try{$service=new \PortalNotifications\Service(\Portal\Connection::get());echo $service->generatePoints()." point notifications created (maximum 1,000 ledger rows per run).\n";}
catch(Throwable $e){error_log('Notifications sync: '.$e->getMessage());fwrite(STDERR,"Notification sync failed; check server logs.\n");exit(1);}
