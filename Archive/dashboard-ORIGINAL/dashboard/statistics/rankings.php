<?php
include '../header.php';
require_once __DIR__ . '/includes/stats-helpers.php';

if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: ../404.php");
    exit();
}
if ($role == 1) {
    header("Location: ../emp_profile.php");
    exit();
}

[$from, $to, $range] = stats_date_range($_GET);
$rankedDomains = stats_build_ranking($con, $from, $to);

$overall = [];
foreach ($rankedDomains as $domain => $items) {
    foreach ($items as $item) {
        $overall[] = $item;
    }
}
usort($overall, function ($a, $b) {
    return $b['overall_score'] <=> $a['overall_score'];
});
$overallTop = array_slice($overall, 0, 10);

$queryStringBase = http_build_query([
    'range' => $range,
    'from'  => $from,
    'to'    => $to,
]);
?>
<style>
.stats-page .panel,.stats-page .rank-card{border:1px solid #dee2e6;border-radius:14px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.stats-page .panel{padding:18px}
.stats-page .rank-card{padding:16px;height:100%}
.stats-page .rank-title{font-size:18px;font-weight:700;margin-bottom:10px}
.stats-page .rank-row{display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0}
.stats-page .rank-row:last-child{border-bottom:none}
.stats-page .rank-pos{font-weight:700;min-width:34px}
.stats-page .rank-name{font-weight:600}
.stats-page .rank-sub{font-size:12px;color:#6c757d}
</style>

<div class="container-fluid stats-page">
    <div class="row">
        <div class="col-12">
            <div class="container-fluid mt-2 py-3 panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4 class="mb-0">Rankings</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="all-stats.php?<?= $queryStringBase ?>" class="btn btn-outline-secondary btn-sm">Back to All Stats</a>
                    </div>
                </div>

                <form method="get" class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Range</label>
                        <select class="form-select" name="range">
                            <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All Time</option>
                            <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>Week</option>
                            <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>Month</option>
                            <option value="year" <?= $range === 'year' ? 'selected' : '' ?>>Year</option>
                            <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="from" value="<?= stats_h($from) ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="to" value="<?= stats_h($to) ?>">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>

                <hr>
                <div class="text-muted small">
                    Ranking range:
                    <?php if ($range === 'all'): ?>
                        All Time
                    <?php else: ?>
                        <?= stats_h($from) ?> to <?= stats_h($to) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container-fluid mt-3 py-3 panel">
                <h5 class="mb-3">Top 10 Overall</h5>
                <div class="row g-3">
                    <?php foreach (array_slice($overallTop, 0, 10) as $i => $item): ?>
                        <div class="col-md-6">
                            <div class="rank-card">
                                <div class="rank-row">
                                    <div>
                                        <div class="rank-name">#<?= $i + 1 ?> — <?= stats_h($item['user']['fullname'] ?? '') ?></div>
                                        <div class="rank-sub"><?= stats_h($item['domain'] ?: 'Unassigned') ?> • <?= stats_h($item['user']['email'] ?? '') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="rank-name"><?= number_format($item['overall_score']) ?></div>
                                        <div class="rank-sub">Score</div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="user-stats.php?id=<?= (int)$item['user']['id'] ?>&<?= $queryStringBase ?>" class="btn btn-dark btn-sm">Open User</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="container-fluid mt-3 py-3 panel">
                <h5 class="mb-3">Top 3 Per Domain</h5>
                <div class="row g-3">
                    <?php foreach ($rankedDomains as $domain => $items): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="rank-card">
                                <div class="rank-title"><?= stats_h($domain) ?></div>

                                <?php foreach (array_slice($items, 0, 3) as $row): ?>
                                    <div class="rank-row">
                                        <div class="d-flex gap-2">
                                            <div class="rank-pos">#<?= number_format($row['rank_in_domain']) ?></div>
                                            <div>
                                                <div class="rank-name"><?= stats_h($row['user']['fullname'] ?? '') ?></div>
                                                <div class="rank-sub">
                                                    Work <?= number_format($row['work_count']) ?> • Reports <?= number_format($row['combined_cashups']) ?> • Days <?= number_format($row['distinct_days']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="rank-name"><?= number_format($row['overall_score']) ?></div>
                                            <div class="rank-sub">Score</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mt-2">
                                    <?php if (!empty($items[0])): ?>
                                        <a href="user-stats.php?id=<?= (int)$items[0]['user']['id'] ?>&<?= $queryStringBase ?>" class="btn btn-outline-dark btn-sm">Open Top User</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../footer.php'; ?>