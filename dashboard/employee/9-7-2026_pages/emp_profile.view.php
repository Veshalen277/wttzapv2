<?php
/* ======================================================================
   FILE 3/4: /dashboard/employee/pages/emp_profile.view.php
   - HTML/UI only
   - Uses e() helper for safe output
   - Loads persistent work draft
   ====================================================================== */

$workDraft = '';
$workDraftUpdatedAt = null;

$userId = (int)($user[5] ?? 0);

$stmt = $con->prepare("SELECT work_draft, work_draft_updated_at FROM users_tbl WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($workDraft, $workDraftUpdatedAt);
    $stmt->fetch();
    $stmt->close();
} else {
    // Fallback: if work_draft_updated_at column doesn't exist yet, just load the draft
    $stmt2 = $con->prepare("SELECT work_draft FROM users_tbl WHERE id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $stmt2->bind_result($workDraft);
        $stmt2->fetch();
        $stmt2->close();
    }
}

// IMPROVEMENT 5: check if user already submitted today
$alreadySubmittedToday = false;
$todayStmt = $con->prepare("SELECT 1 FROM work_tbl WHERE employee_id = ? AND DATE(work_date) = CURDATE() LIMIT 1");
if ($todayStmt) {
    $todayStmt->bind_param("i", $userId);
    $todayStmt->execute();
    $todayStmt->store_result();
    $alreadySubmittedToday = ($todayStmt->num_rows > 0);
    $todayStmt->close();
}

// IMPROVEMENT 4: draft age warning — flag if draft is from a previous day
$draftIsStale = false;
if (!empty($workDraft) && !empty($workDraftUpdatedAt)) {
    $draftDate = date('Y-m-d', strtotime($workDraftUpdatedAt));
    $today     = date('Y-m-d');
    $draftIsStale = ($draftDate < $today);
}
?>
<div class="container-fluid">
  <div class="mb-3 p-2 bg-white border">
    <div style="padding:10px;background-color:#f0f8ff;border-left:4px solid #0073e6;font-size:14px;color:#333;">
      🌍 Location : <strong><?= e($visitorCountry) ?></strong><br>
      🧭 IP Address: <strong><?= e($visitorIP) ?></strong>
    </div>
  </div>

  <div class="row g-3">
    <!-- LEFT -->
    <div class="col-12 col-md-3">
      <h3>Menu</h3>
      <div class="bg-white border p-2 mb-3">
        <?php
          $sidebarPath = $BASE_DASHBOARD . '/inc/sidebar.php';
          if (!file_exists($sidebarPath)) die('Missing file: ' . $sidebarPath);
          include $sidebarPath;
        ?>
      </div>
      <div class="bg-white border p-2">
        <?php
          $bulletinsPath = $BASE_DASHBOARD . '/bulletins/widget.php';
          if (!file_exists($bulletinsPath)) die('Missing file: ' . $bulletinsPath);
          include $bulletinsPath;
        ?>
      </div>
    </div>

    <!-- CENTER -->
    <div class="col-12 col-md-5 border border-3 border-danger">
      <h3>News</h3>

      <div class="bg-white border p-3 mb-3">
        <div class="d-flex align-items-start">
          <img src="<?= e($profilePhoto) ?>"
               alt="Profile Photo"
               width="120"
               height="120"
               class="rounded-circle border border-3 border-white shadow-sm me-3">
          <div>
            <span class="emp_name d-block"><strong><?= e(ucwords($user[0] ?? '')) ?></strong></span>
            <span class="d-block"><strong><?= e(ucwords($user[1] ?? '')) ?></strong></span>
            <span class="d-block">Access Level : <?= e(ucwords($user[4] ?? '')) ?></span>
            <span class="d-block mt-2 text-danger">
              <?php
                echo date("l") . " | ";
                echo date("jS \\of F Y h:i:s A");
              ?>
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white border p-3 mb-3">
        <?php render_employee_wall($con, $logged_in_user_id, $current_user_role); ?>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="col-12 col-md-4 p-2">
      <h3>Submit Work Report</h3>

      <div class="bg-danger text-white font-weight-bold p-3 mb-3">
        <strong>Tasks:</strong>
        <div style="max-height:250px;overflow-y:auto;border:1px solid #ddd;padding:15px;">
          <ul class="mb-0">
            <?php
              $tasks = preg_split('/(?<=\.)\s*/', (string)$userTasksRaw);
              foreach ($tasks as $task) {
                $task = trim($task);
                if ($task === '') continue;
                echo "<li><small>" . e(ucwords($task)) . "</small></li>";
              }
            ?>
          </ul>
        </div>

        <?php if (!empty($userDes)): ?>
          <div class="mt-2">
            <small class="text-muted"><?= e(ucwords($userDes)) ?></small>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white border p-3 mb-3">

        <?php if ($alreadySubmittedToday): ?>
          <!-- IMPROVEMENT 5: already submitted today — grey out form with message -->
          <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert" style="font-size:0.88rem;">
            <span style="font-size:1.2rem;">✅</span>
            <div>
              <strong>Report submitted for today.</strong><br>
              <small class="text-muted">You can submit again tomorrow.</small>
            </div>
          </div>
          <fieldset disabled style="opacity:0.5;">
        <?php else: ?>
          <fieldset>
        <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="workReportForm">
            <input type="hidden" name="form_action" id="form_action" value="submit_work">

            <?php if ($draftIsStale && !empty($workDraft)): ?>
              <!-- IMPROVEMENT 4: stale draft warning -->
              <div class="alert alert-warning d-flex align-items-center gap-2 mb-2" role="alert" style="font-size:0.82rem;">
                <span>⚠️</span>
                <div>
                  <strong>Heads up:</strong> This draft was saved on
                  <strong><?= e(date('D d M', strtotime($workDraftUpdatedAt))) ?></strong>,
                  not today. Make sure it reflects today's work before submitting.
                </div>
              </div>
            <?php endif; ?>

            <label><strong>I did :</strong></label>
            <textarea
              required
              class="form-control"
              rows="5"
              name="work_desc"
              id="work_desc"
              minlength="10"
              maxlength="5000"
              placeholder="You can paste what you copied from the checklist, or type out your work report"><?= e($workDraft ?? '') ?></textarea>

            <!-- IMPROVEMENT 6: character counter -->
            <div class="d-flex justify-content-between align-items-center mt-1">
              <small id="charCounter" class="text-muted">
                <span id="charCount"><?= mb_strlen($workDraft ?? '') ?></span> / 5000 characters
                <span id="charMin" class="<?= mb_strlen($workDraft ?? '') >= 10 ? 'd-none' : 'text-danger' ?>"> — min 10</span>
              </small>
            </div>

            <label class="mt-2"><strong>Attach file (optional):</strong></label>
            <input
              type="file"
              class="form-control btn-sm"
              name="attachment"
              accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">

            <button
              type="submit"
              name="work_btn"
              value="<?= (int)($user[5] ?? 0) ?>"
              class="btn btn-primary mt-2"
              onclick="document.getElementById('form_action').value='submit_work';">
              Submit
            </button>

            <button
              type="submit"
              name="clear_work_draft"
              value="1"
              class="btn btn-outline-danger mt-2 ms-2"
              onclick="document.getElementById('form_action').value='clear_draft';">
              Clear Draft
            </button>

            <small class="text-muted d-block mt-1">
              <strong>Please ensure your file conforms to the permitted types before submission.</strong>
            </small>

            <small id="draftStatus" class="text-muted d-block mt-2"></small>
          </form>

        </fieldset>
      </div>

      <div class="bg-white border p-3">
        <div class="accordion" id="accordionExample">
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                <small>Non-permitted file types</small>
              </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
              <div class="accordion-body"><small>.exe, .bat, .js, .vbs, .scr, .cmd, .dll, .pl, .py, .sh, .cgi</small></div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                <small>Permitted file types</small>
              </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
              <div class="accordion-body"><small>.jpg, .png, .gif, .pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt, .csv, .zip</small></div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /RIGHT -->
  </div><!-- /row -->
</div><!-- /container -->

<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="messageModalLabel">Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const textarea = document.getElementById('work_desc');
  const draftStatus = document.getElementById('draftStatus');
  const formAction = document.getElementById('form_action');

  // IMPROVEMENT 6: character counter logic
  const charCount = document.getElementById('charCount');
  const charMin   = document.getElementById('charMin');

  if (textarea) {
    textarea.addEventListener('input', function () {
      const len = textarea.value.length;
      if (charCount) charCount.textContent = len;
      if (charMin) {
        if (len >= 10) {
          charMin.classList.add('d-none');
        } else {
          charMin.classList.remove('d-none');
        }
      }
    });
  }

  if (!textarea) return;

  let saveTimer = null;
  let lastSavedValue = textarea.value;

  textarea.addEventListener('input', function () {
    clearTimeout(saveTimer);

    draftStatus.textContent = 'Saving draft...';

    saveTimer = setTimeout(function () {
      const formData = new FormData();
      formData.append('form_action', 'save_draft');
      formData.append('work_desc', textarea.value);

      fetch('/dashboard/employee/emp_profile.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Draft save failed');
        }

        lastSavedValue = textarea.value;
        draftStatus.textContent = 'Draft saved';
      })
      .catch(function () {
        draftStatus.textContent = 'Draft could not be saved';
      });
    }, 800);
  });

  const form = document.getElementById('workReportForm');
  if (form) {
    form.addEventListener('submit', function () {
      if (formAction.value === 'submit_work') {
        draftStatus.textContent = '';
      }
    });
  }
});
</script>