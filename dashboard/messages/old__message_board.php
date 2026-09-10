<?php
include '../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

include 'message_logic.php'; // Ensure this handles your $result, $page, and $total_pages
?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="sticky-top" style="top: 20px;">
                <div class="bg-white border p-3 rounded shadow-sm mb-3">
                    <h5 class="h6 fw-bold text-uppercase border-bottom pb-2 mb-3">Menu</h5>
                    <?php include '../inc/sidebar.php'; ?>
                </div>
                <a href="/dashboard/messages/message_form.php" class="btn btn-danger w-100 shadow-sm">
                    <i class="bi bi-pencil-square me-2"></i> Post New Update
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 fw-bold mb-0"><i class="bi bi-chat-left-text text-danger me-2"></i> Site Wide Feed</h2>
                <span class="badge bg-light text-dark border">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=' . $page; ?>">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; font-weight: bold;">
                                            <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['fullname']); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i><?php echo date('M d, Y - H:i', strtotime($row['posted_at'])); ?> 
                                                <span class="ms-2 badge bg-light text-secondary border"><?php echo htmlspecialchars($row['user_scale']); ?></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input border-danger" type="checkbox" name="message_ids[]" value="<?php echo $row['id']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="card-body py-3">
                                <p class="card-text fs-5">
                                    <?php echo nl2br(htmlspecialchars($row['message_text'])); ?>
                                </p>
                            </div>

                            <div class="card-footer bg-light border-top-0 px-4">
                                <?php
                                $message_id = $row['id'];
                                $sql_replies = "SELECT replies.id, users_tbl.fullname, replies.reply_text, replies.replied_at
                                                FROM replies
                                                INNER JOIN users_tbl ON replies.user_id = users_tbl.id
                                                WHERE replies.message_id = $message_id
                                                ORDER BY replies.replied_at ASC";
                                $result_replies = mysqli_query($con, $sql_replies);
                                ?>

                                <?php if (mysqli_num_rows($result_replies) > 0): ?>
                                    <div class="py-2 border-start border-3 border-danger ps-3 my-3">
                                        <h6 class="small fw-bold text-uppercase text-muted mb-3">Discussion</h6>
                                        <?php while ($reply_row = mysqli_fetch_assoc($result_replies)): ?>
                                            <div class="mb-3 position-relative">
                                                <div class="d-flex justify-content-between">
                                                    <span class="small fw-bold"><?php echo htmlspecialchars($reply_row['fullname']); ?></span>
                                                    <form method="POST" action="message_board.php" class="d-inline">
                                                        <input type="hidden" name="reply_id" value="<?php echo $reply_row['id']; ?>">
                                                        <button type="submit" name="delete_reply" class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Delete this reply?')">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <p class="small mb-1 text-dark"><?php echo htmlspecialchars($reply_row['reply_text']); ?></p>
                                                <small class="text-muted italic" style="font-size: 0.75rem;"><?php echo date('H:i', strtotime($reply_row['replied_at'])); ?></small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <div class="d-flex gap-2">
                                        <textarea name="reply_text_<?php echo $row['id']; ?>" class="form-control form-control-sm border-0 shadow-none" rows="1" placeholder="Write a reply..." style="background: #f8f9fa;"></textarea>
                                        <button type="submit" name="submit_reply" value="<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm">Send</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-white p-3 rounded shadow-sm border mt-4">
                        <button type="submit" name="delete_messages" class="btn btn-outline-danger btn-sm mb-3 mb-md-0" onclick="return confirm('Delete selected posts?')">
                            <i class="bi bi-trash me-1"></i> Delete Selected
                        </button>

                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link text-danger" href="message_board.php?page=<?php echo $page - 1; ?>">Previous</a></li>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link <?php echo ($page == $i) ? 'bg-danger border-danger' : 'text-danger'; ?>" href="message_board.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link text-danger" href="message_board.php?page=<?php echo $page + 1; ?>">Next</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>

                <?php else: ?>
                    <div class="text-center py-5 bg-white border rounded shadow-sm">
                        <i class="bi bi-chat-square-dots text-muted display-1"></i>
                        <p class="text-muted mt-3">The feed is currently empty.</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>