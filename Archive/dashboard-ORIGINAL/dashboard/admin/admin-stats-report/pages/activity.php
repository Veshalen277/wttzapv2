<?php
include '../../../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: ../../../index.php");
    exit;
}
$user = $_SESSION['u_data'];
$id = isset($user[5]) ? (int)$user[5] : 0;
if ($id <= 0) {
    header("Location: ../../../index.php");
    exit;
}

require_once __DIR__ . '/../../../inc/dash_metrics.php';

$feed = get_user_activity_feed($con, 120);
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h6 fw-bold text-uppercase mb-0">User Activity Feed</h3>
        <a class="btn btn-sm btn-outline-dark" href="../index.php" style="font-size:0.75rem;">Back</a>
    </div>

    <div class="bg-white border shadow-sm rounded">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Type</th>
                        <th class="pe-3">Detail</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$feed): ?>
                    <tr><td class="ps-3 text-muted" colspan="5">No activity found.</td></tr>
                <?php else: foreach ($feed as $r): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($r['event_time'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['fullname'] ?? '') ?></td>
                        <td class="text-muted"><?= htmlspecialchars((string)($r['user_role'] ?? '')) ?></td>
                        <td>
                            <span class="badge <?= ($r['event_type'] ?? '') === 'LOGIN' ? 'bg-success' : 'bg-primary' ?>">
                                <?= htmlspecialchars($r['event_type'] ?? '') ?>
                            </span>
                        </td>
                        <td class="pe-3 text-muted"><?= htmlspecialchars($r['detail'] ?? '') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../../footer.php'; ?>
