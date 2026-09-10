<?php
require __DIR__.'/bootstrap.php';
if(!$pointsManager){http_response_code(403);exit('Access denied.');}
$rows=$store->rows('SELECT * FROM portal_points_sources ORDER BY source');
$runs=$store->rows('SELECT * FROM portal_points_runs ORDER BY id DESC LIMIT 10');
$pageTitle='Points coverage';include dirname(__DIR__).'/header.php';
?>
<h1 class="h4">Points source coverage</h1><?php if($pointsUser->role===7): ?><p><a href="/dashboard/points/adjust.php">Record a verified adjustment</a></p><?php endif; ?><p>Missing or incompatible sources are not silently treated as zero participation. Undated activity remains unscored. A failed scan never removes existing awards.</p><div class="table-responsive"><table class="table"><thead><tr><th>Source</th><th>Status</th><th>Rows scanned</th><th>Details</th><th>Checked</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><?php foreach(['source','status','processed','detail','checked_at'] as $key): ?><td><?= \Portal\Html::escape($row[$key]) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><h2 class="h5">Recent sync runs</h2><?php foreach($runs as $run): ?><p>#<?= $run['id'] ?> · <?= \Portal\Html::escape($run['started_at']) ?> · <?= \Portal\Html::escape($run['status']) ?> · <?= \Portal\Html::escape($run['finished_at']??'Not finished') ?></p><?php endforeach; ?>
<?php include dirname(__DIR__).'/footer.php'; ?>
