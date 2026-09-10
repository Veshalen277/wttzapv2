<?php
include '../header.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    header("Location: login.php");
    exit();
}

// -----------------------------
// POST HANDLING LOGIC
// -----------------------------

// Handle posting a new suggestion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_new_suggestion'])) {
    $suggestion = mysqli_real_escape_string($con, htmlspecialchars($_POST['suggestion']));
    $user_id = $_SESSION['u_data'][5];
    $user_name = $_SESSION['u_data'][1];

    if (!empty(trim($suggestion))) {
        $sql = "INSERT INTO suggestions (user_id, user_name, suggestion, submitted_at) VALUES ('$user_id', '$user_name', '$suggestion', NOW())";
        if (mysqli_query($con, $sql)) {
            $_SESSION['msg'] = "Suggestion posted!";
            $_SESSION['msg_type'] = "success";
        }
    }
}

// Handle posting a reply
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_reply'])) {
    $reply = mysqli_real_escape_string($con, htmlspecialchars($_POST['reply']));
    $suggestion_id = intval($_POST['suggestion_id']);
    $user_id = $_SESSION['u_data'][5];
    $user_name = $_SESSION['u_data'][1];

    if (!empty(trim($reply))) {
        $sql_reply = "INSERT INTO suggestion_replies (suggestion_id, user_id, user_name, reply, submitted_at) 
                      VALUES ('$suggestion_id', '$user_id', '$user_name', '$reply', NOW())";
        mysqli_query($con, $sql_reply);
    }
}

// Handle bulk deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_suggestions'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $ids_str = implode(',', $ids);
        mysqli_query($con, "DELETE FROM suggestions WHERE id IN ($ids_str)");
        mysqli_query($con, "DELETE FROM suggestion_replies WHERE suggestion_id IN ($ids_str)");
        $_SESSION['msg'] = "Deleted successfully.";
    }
}

// Fetch all suggestions
$sql = "SELECT * FROM suggestions ORDER BY submitted_at DESC";
$result = mysqli_query($con, $sql);
?>

<style>
    .suggestion-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; transition: transform 0.2s; }
    .suggestion-card:hover { border-color: #dc3545; }
    .reply-box { background: #f8f9fa; border-radius: 8px; padding: 12px; margin-top: 10px; border-left: 4px solid #dc3545; }
    .avatar-sm { width: 35px; height: 35px; background: #dc3545; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
    .sticky-sidebar { position: sticky; top: 20px; }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="sticky-sidebar">
                <?php include '../inc/sidebar.php'; ?>
                <div class="card mt-3 bg-light border-0 shadow-sm">
                    <div class="card-body">
                        <h6>Quick Tip</h6>
                        <small class="text-muted">Suggestions are visible to all employees. Keep them constructive!</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark">Suggestion Board</h3>
                <span class="badge bg-danger"><?= mysqli_num_rows($result) ?> Topics</span>
            </div>

            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-body p-4">
                    <form method="post">
                        <textarea name="suggestion" class="form-control border-0 bg-light mb-2" rows="2" placeholder="What's on your mind for improvement?" required></textarea>
                        <div class="text-end">
                            <button type="submit" name="submit_new_suggestion" class="btn btn-danger btn-sm px-4">Post Suggestion</button>
                        </div>
                    </form>
                </div>
            </div>

            <form method="post">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="suggestion-card shadow-sm p-4 mb-4">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3"><?= strtoupper(substr($row['user_name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['user_name']) ?></div>
                                        <small class="text-muted"><?= date('M d, Y - H:i', strtotime($row['submitted_at'])) ?></small>
                                    </div>
                                </div>
                                <input type="checkbox" name="selected_ids[]" value="<?= $row['id'] ?>" class="form-check-input border-danger">
                            </div>

                            <p class="fs-5 text-dark mb-4"><?= nl2br(htmlspecialchars($row['suggestion'])) ?></p>

                            <?php
                            $s_id = $row['id'];
                            $replies = mysqli_query($con, "SELECT * FROM suggestion_replies WHERE suggestion_id = '$s_id' ORDER BY submitted_at ASC");
                            if (mysqli_num_rows($replies) > 0):
                            ?>
                                <div class="ms-4">
                                    <?php while ($rep = mysqli_fetch_assoc($replies)): ?>
                                        <div class="reply-box mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="fw-bold text-danger"><?= htmlspecialchars($rep['user_name']) ?></small>
                                                <small class="text-muted" style="font-size:10px;"><?= date('H:i', strtotime($rep['submitted_at'])) ?></small>
                                            </div>
                                            <p class="small mb-0"><?= htmlspecialchars($rep['reply']) ?></p>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <div class="input-group input-group-sm">
                                    <input type="hidden" name="suggestion_id" value="<?= $row['id'] ?>">
                                    <input type="text" name="reply" class="form-control bg-light" placeholder="Write a response...">
                                    <button type="submit" name="post_reply" class="btn btn-outline-danger">Reply</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="d-flex justify-content-end mb-5">
                        <button type="submit" name="delete_suggestions" class="btn btn-sm btn-link text-muted" onclick="return confirm('Delete selected?')">
                            <i class="bi bi-trash"></i> Delete Selected Suggestions
                        </button>
                    </div>

                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No suggestions yet. Be the first to start a conversation!</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>