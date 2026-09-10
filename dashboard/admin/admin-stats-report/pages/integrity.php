<?php
include '../../../header.php';

if (!isset($_SESSION['u_data'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once __DIR__ . '/../../inc/schema_introspection.php';

$fk_health = get_fk_orphan_summary($con, 200);
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h6 fw-bold text-uppercase mb-0">FK Integrity Report</h3>
        <a href="../index.php" class="btn btn-sm btn-outline-dark" style="font-size:0.75rem;">Back</a>
    </div>

    <div class="bg-white border shadow-sm rounded">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Constraint</th>
                        <th>Child</th>
                        <th>Child Col</th>
                        <th>Parent</th>
                        <th>Parent Col</th>
                        <th class="text-end pe-3">Orphans</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($fk_health as $fk): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($fk['constraint'] ?? '') ?></td>
                        <td><?= htmlspecialchars($fk['child_table'] ?? '') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($fk['child_col'] ?? '') ?></td>
                        <td><?= htmlspecialchars($fk['parent_table'] ?? '') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($fk['parent_col'] ?? '') ?></td>
                        <td class="text-end pe-3 fw-bold <?= ((int)($fk['orphans'] ?? 0) > 0) ? 'text-danger' : 'text-success' ?>">
                            <?= number_format((int)($fk['orphans'] ?? 0)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($fk_health)): ?>
                    <tr><td colspan="6" class="ps-3 text-muted">No foreign keys found (or insufficient permissions to read information_schema)</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../../footer.php'; ?>
