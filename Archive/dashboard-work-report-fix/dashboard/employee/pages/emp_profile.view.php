<?php
/* ======================================================================
   FILE 3/4: /dashboard/employee/pages/emp_profile.view.php
   - HTML/UI only
   - Uses e() helper for safe output
   - Loads persistent work draft
   ====================================================================== */

// The bootstrap already fetched this employee. Optional draft metadata must
// never prevent the form from rendering on an older database schema.
$workDraft = (string)($result['work_draft'] ?? '');
require_once __DIR__ . '/../support/PageSections.php';
?>
<div class="container-fluid">
  <div class="mb-3 p-2 bg-white border">
    <div style="padding:10px;background-color:#f0f8ff;border-left:4px solid #0073e6;font-size:14px;color:#333;">
      🌍 Location : <strong><?= e($visitorCountry) ?></strong><br>
      🧭 IP Address: <strong><?= e($visitorIP) ?></strong>
    </div>
  </div>

  <div class="row g-3">
    <!-- CENTER -->
    <div class="col-12 col-xl-7">
      <h3>News</h3>
      <div class="bg-white border p-2 mb-3">
        <?php
          \EmployeePage\PageSections::render('Bulletins', static function () use ($con, $BASE_DASHBOARD): void {
              // The legacy widget defines $user, $stmt and $db. Keep them local.
              require $BASE_DASHBOARD . '/bulletins/widget.php';
          });
        ?>
      </div>

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
        <?php
          \EmployeePage\PageSections::render('Activity feed', static function () use ($con, $logged_in_user_id, $current_user_role): void {
              render_employee_wall($con, $logged_in_user_id, $current_user_role);
          });
        ?>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="col-12 col-xl-5 p-2">
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
        <form method="POST" enctype="multipart/form-data" id="workReportForm">
          <input type="hidden" name="form_action" id="form_action" value="submit_work">

          <label><strong>I did :</strong></label>
          <textarea
            required
            class="form-control"
            rows="5"
            name="work_desc"
            id="work_desc"
            minlength="10"
            placeholder="You can paste what you copied from the checklist, or type out your work report"><?= e($workDraft ?? '') ?></textarea>

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