<?php
include '../../../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once __DIR__ . '/../../inc/schema_introspection.php';

$schema = get_schema_summary($con, 200);
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h6 fw-bold text-uppercase mb-0">Schema Explorer</h3>
        <a href="../index.php" class="btn btn-sm btn-outline-dark" style="font-size:0.75rem;">Back</a>
    </div>

    <div class="bg-white border shadow-sm rounded">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Table</th>
                        <th>Rows (est)</th>
                        <th>Engine</th>
                        <th class="text-end pe-3">Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($schema as $t): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($t['table_name'] ?? '') ?></td>
                        <td><?= number_format((int)($t['rows_est'] ?? 0)) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($t['engine'] ?? '') ?></td>
                        <td class="text-end pe-3 text-muted"><?= htmlspecialchars($t['update_time'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($schema)): ?>
                    <tr><td colspan="4" class="ps-3 text-muted">No tables found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../../footer.php'; ?>
