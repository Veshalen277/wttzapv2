<?php
include '../header.php';

if (!isset($_SESSION['u_data'])) {
  header("Location: login.php");
  exit();
}

require_once __DIR__ . '/message_actions.php'; // handles POST then redirects
require_once __DIR__ . '/message_queries.php'; // prepares $result, $page, $total_pages
?>
<div class="container-fluid mt-4">
  <div class="row g-4">

    <div class="col-md-3">
      <?php include __DIR__ . '/partials/_menu_sidebar.php'; ?>
    </div>

    <div class="col-md-9">
      <?php include __DIR__ . '/partials/_feed_header.php'; ?>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=' . (int)$page; ?>">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
<div class="accordion" id="feedAccordion">
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php include __DIR__ . '/partials/_post_card.php'; ?>
          <?php endwhile; ?>
          </div>
          <?php include __DIR__ . '/partials/_pagination_bar.php'; ?>

        <?php else: ?>
          <?php include __DIR__ . '/partials/_empty_state.php'; ?>
        <?php endif; ?>
      </form>

    </div>
  </div>
</div>

<?php include '../footer.php'; ?>