<?php
require_once '../header.php';           // keeps your nav / session checks
            // mysqli $con lives here

// ---------- 1. Get user ID safely ----------
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('Invalid user ID.');
}

// ---------- 2. Fetch user ----------
$stmt = $con->prepare("SELECT * FROM users_tbl WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die('No user found with the given ID.');
}

// ---------- 3. Fetch latest work report ----------
$workStmt = $con->prepare("
    SELECT *
    FROM   work_tbl
    WHERE  employee_id = ?
    ORDER  BY work_date DESC
    LIMIT  1
");
$workStmt->bind_param('i', $id);
$workStmt->execute();
$lastReport = $workStmt->get_result()->fetch_assoc();
$workStmt->close();
?>
<!-- ---------- 4. Page ---------- -->
<div class="container-fluid bg-light min-vh-100 d-flex align-items-start py-5">
  <div class="card shadow-lg w-100">
    <div class="card-header text-center bg-primary text-white">
      <h2 class="mb-0"><?= htmlspecialchars(ucwords($user['fullname'])) ?></h2>
      <small>
        (<?= htmlspecialchars(ucwords($user['user_des'])) ?> —
        Scale <?= htmlspecialchars($user['user_scale']) ?>)
      </small>
    </div>

    <div class="card-body row g-4">

      <!-- DETAILS -->
      <section class="col-lg-4">
        <h5 class="border-bottom pb-2">Personal details</h5>
        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
        <p class="mb-1"><strong>Role:</strong> <?= htmlspecialchars($user['user_role']) ?></p>
        <p class="mb-1"><strong>Leave balance:</strong> <?= (int) $user['leave_balance'] ?> days</p>
      </section>

      <!-- RESPONSIBILITIES -->
      <section class="col-lg-4">
        <h5 class="border-bottom pb-2">Job responsibilities</h5>
        <p><?= nl2br(htmlspecialchars($user['user_res'])) ?></p>
      </section>

      <!-- LAST WORK REPORT -->
      <section class="col-lg-4">
        <h5 class="border-bottom pb-2">Latest work report</h5>
        <?php if ($lastReport): ?>
          <p class="mb-1"><strong>Date:</strong>
            <?= date('d M Y H:i', strtotime($lastReport['work_date'])) ?>
          </p>
          <p class="mb-1"><strong>Department:</strong>
            <?= htmlspecialchars($lastReport['department'] ?: '—') ?>
          </p>
          <p class="mb-3"><strong>Task:</strong>
            <?= htmlspecialchars($lastReport['task'] ?: '—') ?>
          </p>
          <p><?= nl2br(htmlspecialchars($lastReport['work_desc'])) ?></p>

          <?php if (!empty($lastReport['attachment_path'])): ?>
            <a class="btn btn-sm btn-outline-secondary mt-2"
               href="<?= htmlspecialchars($lastReport['attachment_path']) ?>"
               target="_blank">
              View attachment
            </a>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted">No reports submitted.</p>
        <?php endif; ?>
      </section>

    </div><!-- /.card-body -->

    <div class="card-footer text-end bg-white border-0">
      <a href="users_list.php" class="btn btn-secondary">Back to list</a>
    </div>
  </div><!-- /.card -->
</div><!-- /.container -->
<?php require_once '../footer.php'; ?>
