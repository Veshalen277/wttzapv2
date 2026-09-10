<?php
/* ======================================================================
   FILE 2/4: /dashboard/employee/pages/emp_profile.bootstrap.php
   - Fixes paths properly for /dashboard/employee/pages/*
   - Loads header + composer autoload
   - Loads session user + DB user row using prepared statements
   - Defines helpers used by view + submit
   ====================================================================== */

// Resolve base directories
$BASE_EMPLOYEE   = realpath(__DIR__ . '/..');      // .../dashboard/employee
$BASE_DASHBOARD  = realpath(__DIR__ . '/../../');  // .../dashboard
$BASE_PUBLICHTML = realpath(__DIR__ . '/../../../'); // .../public_html (optional)

if ($BASE_EMPLOYEE === false || $BASE_DASHBOARD === false) {
  die('Bootstrap path error: expected /dashboard/employee/pages structure');
}

// Include header (this should start session + provide $con)
$headerPath = $BASE_DASHBOARD . '/header.php';
if (!file_exists($headerPath)) {
  die('Missing file: ' . $headerPath);
}
include $headerPath;

// Composer autoload - try dashboard/vendor first, then public_html/vendor
$autoloadCandidates = [
  $BASE_DASHBOARD . '/vendor/autoload.php',
  ($BASE_PUBLICHTML ? $BASE_PUBLICHTML . '/vendor/autoload.php' : ''),
];

$autoload = '';
foreach ($autoloadCandidates as $p) {
  if ($p && file_exists($p)) { $autoload = $p; break; }
}
if (!$autoload) {
  die('Composer autoload not found. Tried: ' . implode(' | ', array_filter($autoloadCandidates)));
}
require $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Session guard
if (!isset($_SESSION['u_data'])) {
  header("Location: ../index.php");
  exit;
}
$user = $_SESSION['u_data'];

// Logged in user id
$logged_in_user_id = isset($user[5]) ? (int)$user[5] : 0;

// Ensure employee wall exists
$wallPath = $BASE_EMPLOYEE . '/employee_wall.php';
if (!file_exists($wallPath)) {
  die('Missing file: ' . $wallPath);
}
require $wallPath;

// Ensure DB connection exists
if (!isset($con) || !($con instanceof mysqli)) {
  die('DB connection $con missing in header.php');
}

// Ensure connection supports special chars
mysqli_set_charset($con, 'utf8mb4');

// HTML escape helper (use only when OUTPUTTING)
function e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Email sender
function sendEmail($toAddresses, $subject, $message, $attachments = []) {
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = 'mail.wttzap.co.za';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@wttzap.co.za';
    $mail->Password   = '!Mv130369$';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('info@wttzap.co.za', 'WTTZAP Work Report Mailer');

    foreach ((array)$toAddresses as $address) {
      $mail->addAddress($address);
    }

    $mail->isHTML(true);
    $mail->Subject = (string)$subject;

    $escapedMessage = htmlspecialchars((string)$message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $htmlMessage    = '<p>' . str_replace("\n", '</p><p>', $escapedMessage) . '</p>';

    $safeSubject = htmlspecialchars((string)$subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $mail->Body = "<html><head><style>
        body{font-family:Arial,sans-serif}
        .email-container{padding:20px}
        .email-header{font-size:18px;font-weight:bold}
        .email-content{margin-top:10px}
        .email-footer{margin-top:20px;font-size:12px;color:#888}
        p{margin:0;padding:0}
      </style></head><body>
      <div class='email-container'>
        <div class='email-header'>{$safeSubject}</div>
        <div class='email-content'>{$htmlMessage}</div>
        <div class='email-footer'><p>Best regards,</p><p>WTTZAP Work Report Mailer</p></div>
      </div>
      </body></html>";

    if (!empty($attachments['report']) && file_exists($attachments['report'])) {
      $mail->addAttachment($attachments['report']);
    }
    if (!empty($attachments['image']) && file_exists($attachments['image'])) {
      $mail->addAttachment($attachments['image']);
    }

    $mail->send();
    return true;
  } catch (Exception $e) {
    return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
  }
}

// IP helper
function getUserIP(): string {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) return (string)$_SERVER['HTTP_CLIENT_IP'];
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return (string)explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
  return (string)($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
}

$visitorIP      = getUserIP();
$visitorCountry = $_SESSION['user_country'] ?? 'Unknown';

// Fetch current user row (prepared)
$id = (int)($user[5] ?? 0);
$stmt = $con->prepare("SELECT * FROM users_tbl WHERE id = ? LIMIT 1");
if (!$stmt) die('Prepare failed (users_tbl): ' . $con->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
  // if user is missing in DB, force re-login
  header("Location: ../index.php");
  exit;
}

// role for wall permissions
$current_user_role = isset($result['user_role']) ? (int)$result['user_role'] : 0;

// Profile photo
$profilePhoto = $result['profile_photo'] ?? '';
if ($profilePhoto === '') $profilePhoto = '/dashboard/employee/uploads/placeholder.jpg';

// Tasks + designation
$userTasksRaw = $user[3] ?? '';
$userDes      = $result['user_des'] ?? '';