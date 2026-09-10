<?php
require_once dirname(__DIR__).'/app/autoload.php';
\Portal\Auth::requireRoles([2,5,7]);
if(isset($_GET['id']))$_GET['user']=$_GET['id'];
require dirname(__DIR__).'/points/index.php';
