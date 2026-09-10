<?php
require_once __DIR__ . '/app/bootstrap.php';
if (ob_get_level() === 0) ob_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
?>
<!doctype html>
<html lang="en" data-theme-user="<?= (int)$portalUser->id ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="<?= \Portal\Html::escape(\Portal\Html::asset('js/theme.js')) ?>"></script>
  <title><?= \Portal\Html::escape($pageTitle ?? 'Employee Portal') ?></title>

  <link href="/dashboard/css/bootstrap.min.css" rel="stylesheet">
  <link href="/dashboard/css/sb-admin.css" rel="stylesheet">
  <link href="<?= \Portal\Html::escape(\Portal\Html::asset('css/styles.css')) ?>" rel="stylesheet">
  <link href="<?= \Portal\Html::escape(\Portal\Html::asset('css/portal-theme.css')) ?>" rel="stylesheet">

  <link href="<?= \Portal\Html::escape(\Portal\Html::asset('css/color-modes.css')) ?>" rel="stylesheet">

  <link href="/dashboard/css/font.all.min.css" rel="stylesheet" type="text/css">
  <link href="/dashboard/css/fontsa.all.min.css" rel="stylesheet" type="text/css">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">


  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <meta name="csrf-token" content="<?= \Portal\Html::escape(\Portal\Csrf::token()) ?>">
  <meta name="description" content="Employee Portal for managing tasks and reports.">
  <meta name="author" content="Veshalen">
  <meta name="robots" content="index, follow">
  <link rel="manifest" href="/manifest.json">




  <style> 
  /* Concolidater 1A */
    .bi-chevron-down { transition: transform .15s ease; }
  </style>
</head>

<body class="bg-light portal-app">
  <script>
    "serviceWorker" in navigator && window.addEventListener("load", function () {
      navigator.serviceWorker.register("service-worker.js")
        .then(function (reg) { console.log("Service Worker registered with scope:", reg.scope); })
        .catch(function (err) { console.log("Service Worker registration failed:", err); });
    });
  </script>

  <?php include __DIR__ . '/navigation.php'; ?>

  <main class="main-bg" id="portal-content" tabindex="-1">
    <?php include __DIR__ . '/messages.php'; ?>