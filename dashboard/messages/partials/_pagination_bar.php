<div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-white p-3 rounded shadow-sm border mt-4">
  <button type="submit" name="delete_messages" class="btn btn-outline-danger btn-sm mb-3 mb-md-0"
          onclick="return confirm('Delete selected posts?')">
    <i class="bi bi-trash me-1"></i> Delete Selected
  </button>

  <nav>
    <ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link text-danger" href="message_board.php?page=<?php echo $page - 1; ?>">Previous</a>
        </li>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
      ?>
        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
          <a class="page-link <?php echo ($page == $i) ? 'bg-danger border-danger' : 'text-danger'; ?>"
             href="message_board.php?page=<?php echo $i; ?>">
            <?php echo $i; ?>
          </a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <li class="page-item">
          <a class="page-link text-danger" href="message_board.php?page=<?php echo $page + 1; ?>">Next</a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</div>