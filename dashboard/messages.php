<?php
require_once __DIR__ . '/app/autoload.php';
$portalFlash = \Portal\Flash::take();
if ($portalFlash) \Portal\Html::view('flash', ['message' => $portalFlash]);
