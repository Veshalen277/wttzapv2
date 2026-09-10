<?php
// Presentation only: keep the existing report and draft submission contract.
$workDraft = (string)($result['work_draft'] ?? '');
require_once __DIR__ . '/../support/PageSections.php';
$reportTasks = array_values(array_filter(array_map('trim',
    preg_split('/(?<=\.)\s+|\r\n|\r|\n/', (string)$userTasksRaw)), static function ($task) {
        return $task !== '';
    }));
$reportName = (string)($user[0] ?? 'Employee');
?>
<link rel="stylesheet" href="/dashboard/employee/assets/work-report.css?v=<?= (int)filemtime(__DIR__ . '/../assets/work-report.css') ?>">
<div class="wr-page">
  <header class="wr-heading">
    <div>
      <p class="wr-eyebrow">WORKSPACE / MY WORK REPORT</p>
      <h1>Your work, clearly recorded.</h1>
      <p class="wr-subtitle">Capture your progress, share outcomes, and keep your team informed.</p>
    </div>
    <time class="wr-date" datetime="<?= date('Y-m-d') ?>"><?= date('l') ?><strong><?= date('d M Y') ?></strong></time>
  </header>

  <div class="wr-layout">
    <div class="wr-primary">
      <section class="wr-panel wr-editor" aria-labelledby="wr-editor-title">
        <div class="wr-panel-heading">
          <div><p class="wr-eyebrow">DAILY UPDATE</p><h2 id="wr-editor-title">Work report</h2></div>
          <span class="wr-tag">New submission</span>
        </div>
        <form method="POST" enctype="multipart/form-data" id="workReportForm">
          <input type="hidden" name="form_action" id="form_action" value="submit_work">
          <div class="wr-form-body">
            <label class="wr-label" for="work_desc">What did you accomplish?</label>
            <p class="wr-help" id="wr-report-help">Include completed tasks, key outcomes, and anything that needs follow-up.</p>
            <textarea required rows="12" name="work_desc" id="work_desc" minlength="10"
              aria-describedby="wr-report-help wr-word-count"
              placeholder="Today I completed…&#10;&#10;The outcome was…&#10;&#10;Next steps or support needed…"><?= e($workDraft) ?></textarea>
            <div class="wr-editor-meta">
              <span id="draftStatus" role="status" aria-live="polite"><?= $workDraft !== '' ? 'Saved draft loaded' : 'Draft saves as you type' ?></span>
              <span id="wr-word-count">At least 10 characters</span>
            </div>
            <div class="wr-attachment">
              <div><label class="wr-label" for="wr-attachment">Supporting file <span class="wr-optional">Optional</span></label>
                <p class="wr-help" id="wr-file-help">Add a document or image that helps explain your work.</p></div>
              <input type="file" id="wr-attachment" name="attachment" aria-describedby="wr-file-help" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
              <details class="wr-file-details"><summary>Supported file types</summary><p>Images, PDF, Word, Excel, PowerPoint, text, CSV, and ZIP files.</p></details>
            </div>
          </div>
          <div class="wr-form-footer">
            <p>Review your report before submitting.</p>
            <div class="wr-actions">
              <button type="submit" name="clear_work_draft" value="1" class="wr-button wr-button-secondary" formnovalidate
                onclick="document.getElementById('form_action').value='clear_draft';">Clear draft</button>
              <button type="submit" name="work_btn" value="<?= (int)($user[5] ?? 0) ?>" class="wr-button wr-button-primary"
                onclick="document.getElementById('form_action').value='submit_work';">Submit report <span aria-hidden="true">↗</span></button>
            </div>
          </div>
        </form>
      </section>

      <details class="wr-panel wr-activity" <?= isset($_GET['post_page']) ? 'open' : '' ?>>
        <summary><span>Team activity<small>Recent updates and work reports</small></span><span class="wr-disclosure" aria-hidden="true">+</span></summary>
        <div class="wr-activity-body">
          <?php
          \EmployeePage\PageSections::render('Activity feed', static function () use ($con, $logged_in_user_id, $current_user_role): void {
              render_employee_wall($con, $logged_in_user_id, $current_user_role);
          });
          ?>
        </div>
      </details>
    </div>

    <aside class="wr-context" aria-label="Your profile and work guidance">
      <section class="wr-panel wr-person">
        <p class="wr-eyebrow">REPORTING AS</p>
        <div class="wr-person-info"><img src="<?= e($profilePhoto) ?>" alt="" width="48" height="48">
          <div><h2><?= e(ucwords($reportName)) ?></h2><p><?= e((string)($userDes ?: ($user[1] ?? ''))) ?></p></div>
        </div>
        <a href="/dashboard/employee/my_profile.php">View my profile <span aria-hidden="true">→</span></a>
      </section>

      <section class="wr-panel wr-tasks" aria-labelledby="wr-tasks-title">
        <div class="wr-panel-heading"><h2 id="wr-tasks-title">Your responsibilities</h2><span class="wr-count"><?= count($reportTasks) ?></span></div>
        <?php if ($reportTasks): ?>
          <ol><?php foreach ($reportTasks as $task): ?><li><?= e($task) ?></li><?php endforeach; ?></ol>
        <?php else: ?>
          <p class="wr-empty">No responsibilities have been listed for your profile yet.</p>
        <?php endif; ?>
      </section>

      <section class="wr-panel wr-bulletins" aria-labelledby="wr-bulletins-title">
        <div class="wr-panel-heading"><h2 id="wr-bulletins-title">Noticeboard</h2></div>
        <div class="wr-bulletin-body">
          <?php
          \EmployeePage\PageSections::render('Bulletins', static function () use ($con, $BASE_DASHBOARD): void {
              require $BASE_DASHBOARD . '/bulletins/widget.php';
          });
          ?>
        </div>
      </section>
      <details class="wr-session"><summary>Session details</summary><p>Location: <?= e($visitorCountry) ?><br>IP address: <?= e($visitorIP) ?></p></details>
    </aside>
  </div>
</div>
<script src="/dashboard/employee/assets/work-report.js?v=<?= (int)filemtime(__DIR__ . '/../assets/work-report.js') ?>" defer></script>

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