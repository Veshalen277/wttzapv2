<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['u_data'])) {
  header('Location: ../index.php');
  die;
}

date_default_timezone_set('Africa/Johannesburg');

if (!defined('BASE_URL')) {
    define('BASE_URL', '../');
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block'); // deprecated in modern browsers, but kept as in original
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

$current_month = date('m');
$current_year  = date('Y');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Employee Portal</title>

  <link href="/dashboard/css/bootstrap.min.css" rel="stylesheet">
  <link href="/dashboard/css/sb-admin.css" rel="stylesheet">
  <link href="/dashboard/css/styles.css" rel="stylesheet">
  <link href="/dashboard/css/portal-theme.css" rel="stylesheet">

  <link href="/dashboard/css/font.all.min.css" rel="stylesheet" type="text/css">
  <link href="/dashboard/css/fontsa.all.min.css" rel="stylesheet" type="text/css">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">


  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

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