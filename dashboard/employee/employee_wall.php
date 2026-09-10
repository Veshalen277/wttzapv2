<?php
require_once __DIR__ . '/reactions_helper.php';

/**
 * Company-wide feed with fixed pagination logic + reactions for posts + work
 */
function render_employee_wall(mysqli $con, int $currentUserId, int $currentUserRole = 0): void
{
    if ($currentUserId <= 0) {
        echo '<p>Invalid user.</p>';
        return;
    }

    $canSeeReports = ($currentUserRole == 7);

    // -----------------------------
    // Pagination setup
    // -----------------------------
    $perPage = 10;
    $page = isset($_GET['post_page']) ? max(1, (int)$_GET['post_page']) : 1;
    $offset = ($page - 1) * $perPage;

    // -----------------------------
    // Count total items
    // -----------------------------
    if ($canSeeReports) {
        $countSQL = "SELECT (SELECT COUNT(*) FROM messages) + (SELECT COUNT(*) FROM work_tbl) AS total";
    } else {
        $countSQL = "SELECT COUNT(*) AS total FROM messages";
    }

    $countRes = mysqli_query($con, $countSQL);
    $totalRows = ($countRes) ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    // -----------------------------
    // Fetch paginated items
    // -----------------------------
    if ($canSeeReports) {
        $sql = "
            (SELECT 'post' AS item_type,
                    m.id AS item_id,
                    m.posted_at AS created_at,
                    m.message_text,
                    NULL AS work_desc,
                    u.fullname AS author_name,
                    NULL AS department,
                    NULL AS task,
                    NULL AS attachment_path
             FROM messages m
             JOIN users_tbl u ON u.id = m.user_id)

            UNION ALL

            (SELECT 'work' AS item_type,
                    w.id AS item_id,
                    w.work_date AS created_at,
                    NULL AS message_text,
                    w.work_desc,
                    u.fullname AS author_name,
                    w.department,
                    w.task,
                    w.attachment_path
             FROM work_tbl w
             JOIN users_tbl u ON u.id = w.employee_id)

            ORDER BY created_at DESC
            LIMIT $perPage OFFSET $offset
        ";
    } else {
        $sql = "
            SELECT 'post' AS item_type,
                   m.id AS item_id,
                   m.posted_at AS created_at,
                   m.message_text,
                   NULL AS work_desc,
                   u.fullname AS author_name,
                   NULL AS department,
                   NULL AS task,
                   NULL AS attachment_path
            FROM messages m
            JOIN users_tbl u ON u.id = m.user_id
            ORDER BY created_at DESC
            LIMIT $perPage OFFSET $offset
        ";
    }

    $res = mysqli_query($con, $sql);
    $items = [];
    $postIds = [];
    $workIds = [];

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
            if (($row['item_type'] ?? '') === 'post') $postIds[] = (int)$row['item_id'];
            if (($row['item_type'] ?? '') === 'work') $workIds[] = (int)$row['item_id'];
        }
    }

    // -----------------------------
    // Load reactions for both types
    // -----------------------------
    // Requires helper: get_item_reactions($con, $type, $ids, $currentUserId)
    $post_reactions = !empty($postIds) ? get_item_reactions($con, 'post', $postIds, $currentUserId) : [];
    $work_reactions = !empty($workIds) ? get_item_reactions($con, 'work', $workIds, $currentUserId) : [];

    ?>

    <style>
        .wall-item {
            background:#fff;
            border:1px solid #dee2e6;
            padding:15px;
            margin-bottom:12px;
            border-radius:8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .wall-item-header {
            font-size:0.75rem;
            color:#6c757d;
            margin-bottom:8px;
            display:flex;
            justify-content:space-between;
            gap:10px;
        }
        .type-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-post { background: #e7f3ff; color: #1877f2; }
        .badge-work { background: #fff0f0; color: #dc3545; }

        /* IMPROVEMENT 1: department + task meta row */
        .wall-item-meta {
            font-size: 0.72rem;
            color: #888;
            margin-bottom: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .wall-meta-pill {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            padding: 1px 8px;
            font-size: 0.70rem;
            color: #555;
        }

        /* IMPROVEMENT 3: attachment indicator */
        .wall-attachment-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.70rem;
            color: #6c757d;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 2px 7px;
            margin-top: 6px;
        }

        .pagination .page-link { color: #dc3545; border-color: #dee2e6; }
        .pagination .page-item.active .page-link { background-color: #dc3545; border-color: #dc3545; color: #fff; }
    </style>

    <div class="employee-wall">

        <?php if (empty($items)): ?>
            <div class="wall-item">
                <div class="text-muted">No items found.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($items as $row): ?>
            <?php
            $type = (string)($row['item_type'] ?? '');
            $id   = (int)($row['item_id'] ?? 0);

            $text = ($type === 'post')
                ? (string)($row['message_text'] ?? '')
                : (string)($row['work_desc'] ?? '');

            // Pick the correct map by type
            $map = ($type === 'post') ? $post_reactions : $work_reactions;

            $reaction = $map[$id]['user_reaction'] ?? null;
            $counts   = $map[$id]['counts'] ?? ['like'=>0,'dislike'=>0,'heart'=>0];

            // IMPROVEMENT 1: department + task (work reports only)
            $department     = (string)($row['department'] ?? '');
            $task           = (string)($row['task'] ?? '');

            // IMPROVEMENT 3: attachment
            $attachmentPath = (string)($row['attachment_path'] ?? '');
            $hasAttachment  = ($attachmentPath !== '');

            // IMPROVEMENT 1: full date with year + day name
            $rawDate     = (string)($row['created_at'] ?? 'now');
            $displayDate = date('D, d M Y  H:i', strtotime($rawDate));
            ?>

            <div class="wall-item">
                <div class="wall-item-header">
                    <span>
                        <strong><?= htmlspecialchars((string)($row['author_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        · <?= htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <span class="type-badge <?= $type === 'post' ? 'badge-post' : 'badge-work' ?>">
                        <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <?php if ($type === 'work' && ($department !== '' || $task !== '')): ?>
                    <!-- IMPROVEMENT 2: department + task pills on work reports -->
                    <div class="wall-item-meta">
                        <?php if ($department !== ''): ?>
                            <span class="wall-meta-pill">🏢 <?= htmlspecialchars(ucwords($department), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($task !== ''): ?>
                            <span class="wall-meta-pill">📋 <?= htmlspecialchars(ucwords($task), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-2" style="font-size: 0.9rem;">
                    <?= nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) ?>
                </div>

                <?php if ($hasAttachment): ?>
                    <!-- IMPROVEMENT 3: attachment indicator -->
                    <div>
                        <span class="wall-attachment-badge">📎 Attachment included</span>
                    </div>
                <?php endif; ?>

                <!-- Reactions for BOTH post + work -->
                <!-- <div class="d-flex gap-2 border-top pt-2">
                    <form action="post_react.php" method="post" class="m-0">
                        <input type="hidden" name="item_type" value="</?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="item_id" value="</?= $id ?>">
                        <input type="hidden" name="return" value="</?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                        <button name="reaction" value="like"
                                class="btn btn-sm </?= ($reaction === 'like') ? 'btn-primary' : 'btn-light' ?>"
                                style="font-size: 11px;">
                            👍 </?= (int)($counts['like'] ?? 0) ?>
                        </button>

                        <button name="reaction" value="dislike"
                                class="btn btn-sm </?= ($reaction === 'dislike') ? 'btn-danger' : 'btn-light' ?>"
                                style="font-size: 11px;">
                            👎 </?= (int)($counts['dislike'] ?? 0) ?>
                        </button>

                        <button name="reaction" value="heart"
                                class="btn btn-sm </?= ($reaction === 'heart') ? 'btn-warning text-white' : 'btn-light' ?>"
                                style="font-size: 11px;">
                            ❤️ </?= (int)($counts['heart'] ?? 0) ?>
                        </button>
                    </form>
                </div> -->




<!-- Replaced above with below after ajax change -> JS  for this is at the bottom ........your POES!?! -->



<form action="/dashboard/employee/post_react.php" method="post" class="m-0 react-form">
  <input type="hidden" name="item_type" value="<?= htmlspecialchars($type) ?>">
  <input type="hidden" name="item_id" value="<?= $id ?>">
  <input type="hidden" name="return" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

  <button type="submit" class="btn btn-sm react-btn <?= ($reaction==='like')?'btn-primary':'btn-light' ?>"
          data-reaction="like" style="font-size:11px;">
    👍 <span class="react-count" data-rt="like"><?= (int)$counts['like'] ?></span>
  </button>

  <button type="submit" class="btn btn-sm react-btn <?= ($reaction==='dislike')?'btn-danger':'btn-light' ?>"
          data-reaction="dislike" style="font-size:11px;">
    👎 <span class="react-count" data-rt="dislike"><?= (int)$counts['dislike'] ?></span>
  </button>

  <button type="submit" class="btn btn-sm react-btn <?= ($reaction==='heart')?'btn-warning text-white':'btn-light' ?>"
          data-reaction="heart" style="font-size:11px;">
    ❤️ <span class="react-count" data-rt="heart"><?= (int)$counts['heart'] ?></span>
  </button>
</form>


            </div>

        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-center mt-4">
                    <?php
                    $range = 2;

                    if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?post_page=1">First</a></li>
                        <li class="page-item"><a class="page-link" href="?post_page=<?= $page - 1 ?>">Prev</a></li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?post_page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?post_page=<?= $page + 1 ?>">Next</a></li>
                        <li class="page-item"><a class="page-link" href="?post_page=<?= $totalPages ?>">Last</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </div>

    <?php
}
?>


<script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.react-btn');
  if (!btn) return;

  e.preventDefault();

  const form = btn.closest('.react-form');
  const url = form.getAttribute('action');

  const fd = new FormData(form);
  fd.set('reaction', btn.dataset.reaction);

  try {
    btn.disabled = true;

    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    // If server returns non-JSON (HTML redirect etc), this will throw
    const data = await res.json();

    if (!data || !data.ok) throw new Error(data?.error || 'Bad response');

    // update counts
    form.querySelectorAll('.react-count').forEach((span) => {
      const rt = span.dataset.rt;
      if (data.counts && typeof data.counts[rt] !== 'undefined') {
        span.textContent = data.counts[rt];
      }
    });

    // update active styles
    const ur = data.user_reaction;
    form.querySelectorAll('.react-btn').forEach((b) => {
      const rt = b.dataset.reaction;

      b.classList.remove('btn-primary','btn-danger','btn-warning','text-white');
      b.classList.add('btn-light');

      if (ur === rt) {
        b.classList.remove('btn-light');
        if (rt === 'like') b.classList.add('btn-primary');
        if (rt === 'dislike') b.classList.add('btn-danger');
        if (rt === 'heart') b.classList.add('btn-warning','text-white');
      }
    });

  } catch (err) {
    // Silent failure: no ugly alert, user can click again
    console.log('Reaction AJAX failed:', err);
  } finally {
    btn.disabled = false;
  }
});
</script>

<!-- <script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.react-btn');
  if (!btn) return;

  e.preventDefault();

  const form = btn.closest('.react-form');
  const url = form.getAttribute('action');

  const fd = new FormData(form);
  fd.set('reaction', btn.dataset.reaction);

  try {
    btn.disabled = true;

    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const text = await res.text(); // <--- read raw response first
    console.log('React raw response:', res.status, text);

    let data;
    try { data = JSON.parse(text); } catch (e) {
      alert('Reaction failed: server did not return JSON. Check console (F12).');
      return;
    }

    if (!data.ok) {
      alert('Reaction failed: ' + (data.error || 'unknown'));
      return;
    }

    form.querySelectorAll('.react-count').forEach((span) => {
      const rt = span.dataset.rt;
      if (data.counts && typeof data.counts[rt] !== 'undefined') {
        span.textContent = data.counts[rt];
      }
    });

    const ur = data.user_reaction;
    form.querySelectorAll('.react-btn').forEach((b) => {
      const rt = b.dataset.reaction;
      b.classList.remove('btn-primary','btn-danger','btn-warning','text-white');
      b.classList.add('btn-light');

      if (ur === rt) {
        b.classList.remove('btn-light');
        if (rt === 'like') b.classList.add('btn-primary');
        if (rt === 'dislike') b.classList.add('btn-danger');
        if (rt === 'heart') b.classList.add('btn-warning','text-white');
      }
    });

  } finally {
    btn.disabled = false;
  }
}); 
</script>-->
<!-- </?php
require_once __DIR__ . '/reactions_helper.php';

/**
 * Company-wide feed with fixed pagination logic
 */
function render_employee_wall(mysqli $con, int $currentUserId, int $currentUserRole = 0): void
{
    if ($currentUserId <= 0) {
        echo '<p>Invalid user.</p>';
        return;
    }

    $canSeeReports = ($currentUserRole == 7);

    // -----------------------------
    // Pagination setup
    // -----------------------------
    $perPage = 10;
    // Note: Changed from $_GET['page'] to $_GET['post_page'] to avoid conflicts with other page variables
    $page = isset($_GET['post_page']) ? max(1, (int)$_GET['post_page']) : 1;
    $offset = ($page - 1) * $perPage;

    // -----------------------------
    // Count total items
    // -----------------------------
    if ($canSeeReports) {
        $countSQL = "SELECT (SELECT COUNT(*) FROM messages) + (SELECT COUNT(*) FROM work_tbl) AS total";
    } else {
        $countSQL = "SELECT COUNT(*) AS total FROM messages";
    }

    $countRes = mysqli_query($con, $countSQL);
    $totalRows = ($countRes) ? (int)mysqli_fetch_assoc($countRes)['total'] : 0;
    $totalPages = max(1, ceil($totalRows / $perPage));

    // -----------------------------
    // Fetch paginated items (UNION logic remains same)
    // -----------------------------
    if ($canSeeReports) {
        $sql = "
            (SELECT 'post' AS item_type, m.id AS item_id, m.posted_at AS created_at, m.message_text, NULL AS work_desc, u.fullname AS author_name 
             FROM messages m JOIN users_tbl u ON u.id = m.user_id)
            UNION ALL
            (SELECT 'work' AS item_type, w.id AS item_id, w.work_date AS created_at, NULL AS message_text, w.work_desc, u.fullname AS author_name 
             FROM work_tbl w JOIN users_tbl u ON u.id = w.employee_id)
            ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    } else {
        $sql = "SELECT 'post' AS item_type, m.id AS item_id, m.posted_at AS created_at, m.message_text, NULL AS work_desc, u.fullname AS author_name 
                FROM messages m JOIN users_tbl u ON u.id = m.user_id 
                ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    }

    $res = mysqli_query($con, $sql);
    $items = [];
    $postIds = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
        if ($row['item_type'] === 'post') $postIds[] = (int)$row['item_id'];
    }

    $reactions_map = (!empty($postIds)) ? get_post_reactions($con, $postIds, $currentUserId) : [];
    ?>

    <style>
        .wall-item { background:#fff; border:1px solid #dee2e6; padding:15px; margin-bottom:12px; border-radius:8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .wall-item-header { font-size:0.75rem; color:#6c757d; margin-bottom:8px; display:flex; justify-content:space-between; }
        .type-badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: bold; }
        .badge-post { background: #e7f3ff; color: #1877f2; }
        .badge-work { background: #fff0f0; color: #dc3545; }
        .pagination .page-link { color: #dc3545; border-color: #dee2e6; }
        .pagination .page-item.active .page-link { background-color: #dc3545; border-color: #dc3545; color: #fff; }
    </style>

    <div class="employee-wall">
        </?php foreach ($items as $row): ?>
            </?php
            $type = $row['item_type'];
            $id = (int)$row['item_id'];
            $text = ($type === 'post') ? $row['message_text'] : $row['work_desc'];
            $reaction = $reactions_map[$id]['user_reaction'] ?? null;
            $counts = $reactions_map[$id]['counts'] ?? ['like'=>0,'dislike'=>0,'heart'=>0];
            ?>
            <div class="wall-item">
                <div class="wall-item-header">
                    <span><strong></?= htmlspecialchars($row['author_name']) ?></strong> · </?= date('M d, H:i', strtotime($row['created_at'])) ?></span>
                    <span class="type-badge </?= $type === 'post' ? 'badge-post' : 'badge-work' ?>"></?= $type ?></span>
                </div>
                <div class="mb-2" style="font-size: 0.9rem;"></?= nl2br(htmlspecialchars($text)) ?></div>

                </?php if ($type === 'post'): ?>
                    <div class="d-flex gap-2 border-top pt-2">
                        <form action="post_react.php" method="post" class="m-0">
                            <input type="hidden" name="post_id" value="</?= $id ?>">
                            <button name="reaction" value="like" class="btn btn-sm </?= ($reaction === 'like')?'btn-primary':'btn-light' ?>" style="font-size: 11px;">👍 </?= $counts['like'] ?></button>
                            <button name="reaction" value="dislike" class="btn btn-sm </?= ($reaction === 'dislike')?'btn-danger':'btn-light' ?>" style="font-size: 11px;">👎 </?= $counts['dislike'] ?></button>
                            <button name="reaction" value="heart" class="btn btn-sm </?= ($reaction === 'heart')?'btn-warning text-white':'btn-light' ?>" style="font-size: 11px;">❤️ </?= $counts['heart'] ?></button>
                        </form>
                    </div>
                </?php endif; ?>
            </div>
        </?php endforeach; ?>

        </?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-center mt-4">
                    </?php 
                    $range = 2; // Show 2 pages before and after current page
                    
                    // First & Previous
                    if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?post_page=1">First</a></li>
                        <li class="page-item"><a class="page-link" href="?post_page=</?= $page - 1 ?>">Prev</a></li>
                    </?php endif;

                    // Numbered Pages
                    for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                        <li class="page-item </?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?post_page=</?= $i ?>"></?= $i ?></a>
                        </li>
                    </?php endfor;

                    // Next & Last
                    if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?post_page=</?= $page + 1 ?>">Next</a></li>
                        <li class="page-item"><a class="page-link" href="?post_page=</?= $totalPages ?>">Last</a></li>
                    </?php endif; ?>
                </ul>
            </nav>
        </?php endif; ?>
    </div>
    </?php
} -->