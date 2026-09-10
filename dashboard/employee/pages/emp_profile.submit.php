<?php

/* ======================================================================

   FILE 4/4: /dashboard/employee/pages/emp_profile.submit.php

   ====================================================================== */



$id         = (int)($user[5] ?? 0);

$fullname   = (string)($user[0] ?? '');

$department = (string)($user[2] ?? '');

$userTask   = (string)($user[3] ?? '');



$formAction = $_POST['form_action'] ?? '';

$work_desc  = trim((string)($_POST['work_desc'] ?? ''));



/* ------------------------------------------------------

   CLEAR DRAFT

------------------------------------------------------ */

if ($formAction === 'clear_draft' || isset($_POST['clear_work_draft'])) {

  // IMPROVEMENT 4: also clear the draft timestamp when clearing the draft
  $stmt = $con->prepare("UPDATE users_tbl SET work_draft = NULL, work_draft_updated_at = NULL WHERE id = ?");

  if ($stmt) {

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $stmt->close();

  } else {

    // Fallback if work_draft_updated_at column doesn't exist yet
    $stmt = $con->prepare("UPDATE users_tbl SET work_draft = NULL WHERE id = ?");

    if ($stmt) {

      $stmt->bind_param("i", $id);

      $stmt->execute();

      $stmt->close();

    }

  }



  $_SESSION['msg'] = "Draft cleared.";

  $_SESSION['msg_type'] = "success";

  header("Location: /dashboard/employee/emp_profile.php");

  exit;

}



/* ------------------------------------------------------

   SAVE DRAFT ONLY (autosave)

------------------------------------------------------ */

if ($formAction === 'save_draft') {

  // IMPROVEMENT 4: save timestamp alongside draft so view can detect stale drafts
  $stmt = $con->prepare("UPDATE users_tbl SET work_draft = ?, work_draft_updated_at = NOW() WHERE id = ?");

  if ($stmt) {

    $stmt->bind_param("si", $work_desc, $id);

    $stmt->execute();

    $stmt->close();

  } else {

    // Fallback if work_draft_updated_at column doesn't exist yet
    $stmt = $con->prepare("UPDATE users_tbl SET work_draft = ? WHERE id = ?");

    if ($stmt) {

      $stmt->bind_param("si", $work_desc, $id);

      $stmt->execute();

      $stmt->close();

    }

  }

  exit;

}



/* ------------------------------------------------------

   NORMAL PAGE LOAD

------------------------------------------------------ */

if (
  $_SERVER['REQUEST_METHOD'] !== 'POST' ||
  (!isset($_POST['work_btn']) && $formAction !== 'save_draft' && $formAction !== 'clear_draft' && !isset($_POST['clear_work_draft']))
) {
  return;
}



/* ------------------------------------------------------

   FINAL SUBMIT

------------------------------------------------------ */



// Save latest textarea content as draft too (with timestamp)
$draftStmt = $con->prepare("UPDATE users_tbl SET work_draft = ?, work_draft_updated_at = NOW() WHERE id = ?");

if ($draftStmt) {

  $draftStmt->bind_param("si", $work_desc, $id);

  $draftStmt->execute();

  $draftStmt->close();

} else {

  // Fallback if work_draft_updated_at column doesn't exist yet
  $draftStmt = $con->prepare("UPDATE users_tbl SET work_draft = ? WHERE id = ?");

  if ($draftStmt) {

    $draftStmt->bind_param("si", $work_desc, $id);

    $draftStmt->execute();

    $draftStmt->close();

  }

}



if (mb_strlen($work_desc) < 10) {

  $_SESSION['msg'] = "Work report too short.";

  $_SESSION['msg_type'] = "error";

  header("Location: /dashboard/employee/emp_profile.php");

  exit;

}



// Build HTML report

$work_desc_html = '<ul>';

foreach (preg_split('/\r\n|\r|\n/', $work_desc) as $line) {

  $line = trim($line);

  if ($line === '') continue;

  $work_desc_html .= '<li>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';

}

$work_desc_html .= '</ul>';



$html_report = "<html><head><style>

  body{font-family:Arial,sans-serif}

  .report-container{padding:20px}

  .report-header{font-size:18px;font-weight:bold}

  .report-content{margin-top:10px}

  .report-footer{margin-top:20px;font-size:12px;color:#888}

  ul{list-style-type:disc;margin-left:20px}

</style></head><body>

  <div class='report-container'>

    <div class='report-header'>Work Report</div>

    <div class='report-content'>{$work_desc_html}</div>

    <div class='report-footer'><p>Best regards,</p><p>WTTZAP Work Report Mailer</p></div>

  </div>

</body></html>";



$tmpDir = $BASE_EMPLOYEE . '/tmp';

if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);



$report_file = $tmpDir . '/work_report_' . time() . '.html';

file_put_contents($report_file, $html_report);



$attachments = ['report' => $report_file];



// Optional upload

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {

  $uploadDir = $BASE_EMPLOYEE . '/uploads';

  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);



  $original = (string)($_FILES['attachment']['name'] ?? 'file');

  $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);

  $upload_file = $uploadDir . '/' . time() . '_' . $safeName;



  if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_file)) {

    $attachments['image'] = $upload_file;

  } else {

    $_SESSION['msg'] = "Failed to move uploaded file.";

    $_SESSION['msg_type'] = "error";

    if (file_exists($report_file)) unlink($report_file);

    header("Location: /dashboard/employee/emp_profile.php");

    exit;

  }

}



$date = date("Y-m-d H:i:s");



// One per day check

$stmt = $con->prepare("SELECT 1 FROM work_tbl WHERE employee_id = ? AND DATE(work_date) = CURDATE() LIMIT 1");

if (!$stmt) {

  $_SESSION['msg'] = "DB error: " . $con->error;

  $_SESSION['msg_type'] = "error";

  goto cleanup_and_redirect;

}

$stmt->bind_param("i", $id);

$stmt->execute();

$stmt->store_result();

$already = ($stmt->num_rows > 0);

$stmt->close();



if ($already) {

  $_SESSION['msg'] = "You have already submitted work for today.";

  $_SESSION['msg_type'] = "error";

  goto cleanup_and_redirect;

}



$attachmentPath = (string)($attachments['image'] ?? '');



$stmt = $con->prepare("

  INSERT INTO work_tbl (employee_id, work_desc, work_date, assigned_to, department, task, attachment_path)

  VALUES (?,?,?,?,?,?,?)

");

if (!$stmt) {

  $_SESSION['msg'] = "DB error: " . $con->error;

  $_SESSION['msg_type'] = "error";

  goto cleanup_and_redirect;

}

$stmt->bind_param("issssss", $id, $work_desc, $date, $fullname, $department, $userTask, $attachmentPath);

$ok = $stmt->execute();

$err = $stmt->error;

$stmt->close();



if (!$ok) {

  $_SESSION['msg'] = "Failed to submit work report. " . $err;

  $_SESSION['msg_type'] = "error";

  goto cleanup_and_redirect;

}



$emailAddresses = ['altaafs@wtt.co.za','accounts@wtt.co.za','zulekhas@wtt.co.za','shuaibk@wtt.co.za','amata@wtt.co.za'];

$subject = "New Work Report Submitted";

$message = "A new work report has been submitted by {$fullname}.\nDate: {$date}\nDepartment: {$department}\nTask: {$userTask}";



$emailResult = sendEmail($emailAddresses, $subject, $message, $attachments);



if ($emailResult === true) {

  $_SESSION['msg'] = "Work report submitted successfully.";

  $_SESSION['msg_type'] = "success";

} else {

  $_SESSION['msg'] = (string)$emailResult;

  $_SESSION['msg_type'] = "error";

}



cleanup_and_redirect:



if (!empty($attachments['image']) && file_exists($attachments['image'])) unlink($attachments['image']);

if (!empty($report_file) && file_exists($report_file)) unlink($report_file);



header("Location: /dashboard/employee/emp_profile.php");

exit;