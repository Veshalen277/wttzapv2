<?php
include '../header.php';

$current_user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;
$current_fullname = isset($_SESSION['u_data'][0]) ? $_SESSION['u_data'][0] : '';

if ($current_user_id <= 0) {
    header("Location: ../index.php");
    exit;
}

$date = date('Y-m-d H:i:s');

// Fetch user_scale safely
$user_scale = 'Unknown';
$stmt = $con->prepare("SELECT user_scale FROM users_tbl WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row && isset($row['user_scale'])) {
    $user_scale = $row['user_scale'];
}

// Handle submission
if (isset($_POST['submit_suggestion'])) {
    $suggestion = trim($_POST['suggestion'] ?? '');

    if ($suggestion === '') {
        $_SESSION['error'] = "Suggestion cannot be empty.";
        header("Location: " . basename(__FILE__));
        exit;
    }

    $stmt = $con->prepare(
        "INSERT INTO suggestions (user_id, user_name, suggestion, submitted_at)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $current_user_id, $current_fullname, $suggestion, $date);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Suggestion submitted successfully. <a href='/dashboard/suggestions/suggestion_board.php'>View suggestions</a>";
    } else {
        $_SESSION['error'] = "Error submitting suggestion. Please try again.";
    }
    $stmt->close();

    header("Location: " . basename(__FILE__));
    exit;
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .page-title { font-size: .95rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .card-title-sm { font-size: .8rem; font-weight: 800; text-transform: uppercase; }
    .hint { font-size: .75rem; color: #6c757d; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- Sidebar -->
        <div class="col-12 col-md-3">
            <div class="bg-white border p-2 shadow-sm">
                <div class="page-title mb-2">Navigation</div>
                <?php include '../inc/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main -->
        <div class="col-12 col-md-9">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="page-title">Suggestions</div>
                <div class="hint">Submit an internal suggestion for review</div>
            </div>

            <!-- Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title-sm text-danger mb-1">Submit suggestion</div>
                            <div class="hint">Provide a clear, actionable suggestion. Include context if needed.</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Suggestions</span>
                        </div>
                    </div>

                    <hr>

                    <form action="" method="POST" class="needs-validation" novalidate>

                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Date</label>
                                <input type="text" class="form-control" value="<?= e($date) ?>" readonly>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">User</label>
                                <input type="text" class="form-control" value="<?= e($current_fullname) ?>" readonly>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">User ID</label>
                                <input type="text" class="form-control" value="<?= (int)$current_user_id ?>" readonly>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Department</label>
                                <input type="text" class="form-control" value="<?= e($user_scale) ?>" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase">Suggestion</label>
                                <textarea class="form-control" rows="7" name="suggestion" required
                                          placeholder="Write your suggestion here..."></textarea>
                                <div class="invalid-feedback">Please enter your suggestion.</div>
                            </div>

                            <div class="col-12 d-grid">
                                <button class="btn btn-danger btn-lg" name="submit_suggestion" type="submit">
                                    Submit Suggestion
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../footer.php'; ?>
