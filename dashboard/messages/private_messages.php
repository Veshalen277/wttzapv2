<?php
include '../header.php';

// Guard: must be logged in
$current_user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;
if ($current_user_id <= 0) {
    header("Location: ../index.php");
    exit;
}

// Fetch users excluding current user (prepared)
$userOptions = [];
$stmt = $con->prepare("SELECT id, fullname FROM users_tbl WHERE id != ? ORDER BY fullname ASC");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $userOptions[] = $row;
}
$stmt->close();
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
                <div class="page-title">Private Messages</div>
                <div class="hint">Compose and send a private message to a staff member</div>
            </div>

            <div class="card border shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title-sm text-danger mb-1">Send private message</div>
                            <div class="hint">Select a recipient and type your message below</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Messaging</span>
                        </div>
                    </div>

                    <hr>

                    <form method="post" action="send_private_message.php" class="needs-validation" novalidate>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase">Recipient</label>
                                <select name="receiver_id" class="form-select" required>
                                    <option value="">Select a recipient</option>
                                    <?php foreach ($userOptions as $u): ?>
                                        <option value="<?= (int)$u['id']; ?>">
                                            <?= htmlspecialchars($u['fullname'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a recipient.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase">Message</label>
                                <textarea
                                    name="message_text"
                                    class="form-control"
                                    rows="6"
                                    placeholder="Write your message here..."
                                    required></textarea>
                                <div class="invalid-feedback">Please enter a message.</div>
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-danger" type="submit">
                                    Send Message
                                </button>
                                <a class="btn btn-outline-dark" href="inbox.php">
                                    View Inbox
                                </a>
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
