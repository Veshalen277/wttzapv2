<?php
$message_id = (int)$row['id'];

/* =========================
   Load Replies
========================= */
$sql_replies = "SELECT replies.id, users_tbl.fullname, replies.reply_text, replies.replied_at
                FROM replies
                INNER JOIN users_tbl ON replies.user_id = users_tbl.id
                WHERE replies.message_id = $message_id
                ORDER BY replies.replied_at ASC";
$result_replies = mysqli_query($con, $sql_replies);

/* =========================
   Prepare Preview / Collapse
========================= */
$raw = (string)$row['message_text'];
$plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));

$max = 140;
$is_truncated = (mb_strlen($plain) > $max);
$preview = $is_truncated ? (mb_substr($plain, 0, $max) . '…') : $plain;

$collapseId = "msgBodyFull_" . $message_id;
?>

<div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">

  <!-- HEADER -->
  <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3"
             style="width:45px;height:45px;font-weight:bold;">
          <?php echo strtoupper(substr((string)$row['fullname'], 0, 1)); ?>
        </div>
        <div>
          <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars((string)$row['fullname']); ?></h6>
          <small class="text-muted">
            <i class="bi bi-clock me-1"></i>
            <?php echo date('M d, Y - H:i', strtotime((string)$row['posted_at'])); ?>
            <span class="ms-2 badge bg-light text-secondary border">
              <?php echo htmlspecialchars((string)$row['user_scale']); ?>
            </span>
          </small>
        </div>
      </div>

      <div class="form-check">
        <input class="form-check-input border-danger"
               type="checkbox"
               name="message_ids[]"
               value="<?php echo $message_id; ?>">
      </div>
    </div>
  </div>

  <!-- MESSAGE BODY -->
  <div class="card-body py-3">

    <?php if ($is_truncated): ?>

      <!-- PREVIEW BUTTON (Bootstrap 5) -->
      <button
        type="button"
        class="btn btn-link p-0 text-start w-100 text-decoration-none js-msg-toggle"
        data-bs-toggle="collapse"
        data-bs-target="#<?php echo $collapseId; ?>"
        aria-controls="<?php echo $collapseId; ?>"
      >
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="card-text fs-5 text-dark">
            <?php echo htmlspecialchars($preview); ?>
            <span class="text-danger fw-semibold ms-1 small">Read more</span>
          </div>
          <i class="bi bi-chevron-down text-muted mt-1"></i>
        </div>
      </button>

      <!-- COLLAPSE (Bootstrap 5) -->
      <div
        id="<?php echo $collapseId; ?>"
        class="collapse mt-2"
        data-bs-parent="#feedAccordion"
      >
        <div class="bg-white border rounded-3 p-3">
          <div class="text-dark" style="white-space: pre-wrap;">
            <?php echo htmlspecialchars($raw); ?>
          </div>

          <!-- CLOSE BUTTON (Bootstrap 5) -->
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary mt-3 js-msg-toggle"
            data-bs-toggle="collapse"
            data-bs-target="#<?php echo $collapseId; ?>"
            aria-controls="<?php echo $collapseId; ?>"
          >
            Close
          </button>
        </div>
      </div>

    <?php else: ?>

      <div class="card-text fs-5 text-dark" style="white-space: pre-wrap;">
        <?php echo htmlspecialchars($raw); ?>
      </div>

    <?php endif; ?>

  </div>

  <!-- FOOTER -->
  <div class="card-footer bg-light border-top-0 px-4">

    <?php if ($result_replies && mysqli_num_rows($result_replies) > 0): ?>
      <div class="py-2 border-start border-3 border-danger ps-3 my-3">
        <h6 class="small fw-bold text-uppercase text-muted mb-3">Discussion</h6>

        <?php while ($reply_row = mysqli_fetch_assoc($result_replies)): ?>
          <div class="mb-3 position-relative">
            <div class="d-flex justify-content-between">
              <span class="small fw-bold">
                <?php echo htmlspecialchars((string)$reply_row['fullname']); ?>
              </span>

              <!-- NO NESTED FORM -->
              <button
                type="submit"
                name="delete_reply"
                value="<?php echo (int)$reply_row['id']; ?>"
                class="btn btn-link btn-sm text-danger p-0"
                onclick="return confirm('Delete this reply?')"
              >
                <i class="bi bi-x-circle"></i>
              </button>
            </div>

            <p class="small mb-1 text-dark">
              <?php echo htmlspecialchars((string)$reply_row['reply_text']); ?>
            </p>

            <small class="text-muted italic" style="font-size:0.75rem;">
              <?php echo date('H:i', strtotime((string)$reply_row['replied_at'])); ?>
            </small>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

    <!-- Reply Form -->
    <div class="mt-3">
      <div class="d-flex gap-2">
        <textarea name="reply_text_<?php echo $message_id; ?>"
                  class="form-control form-control-sm border-0 shadow-none"
                  rows="1"
                  placeholder="Write a reply..."
                  style="background:#f8f9fa;"></textarea>

        <button type="submit"
                name="submit_reply"
                value="<?php echo $message_id; ?>"
                class="btn btn-outline-danger btn-sm">
          Send
        </button>
      </div>
    </div>

  </div>
</div>