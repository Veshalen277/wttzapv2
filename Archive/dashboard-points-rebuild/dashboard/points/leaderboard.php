<?php
require __DIR__.'/bootstrap.php';
if(!$pointsManager){http_response_code(403);exit('Access denied.');}
use Portal\Html;
$from=($_GET['range']??'')==='all'?'1000-01-01':date('Y-m-01');$until=date('Y-m-d',strtotime('+1 day'));
$page=max(1,(int)($_GET['page']??1));
$count=(int)$store->rows('SELECT COUNT(*) n FROM users_tbl')[0]['n'];$pages=max(1,(int)ceil($count/50));$page=min($page,$pages);
$rows=$store->rows('SELECT u.id,u.fullname,COALESCE(SUM(l.delta),0) points FROM users_tbl u LEFT JOIN portal_points_ledger l ON l.user_id=u.id AND l.occurred_at>=? AND l.occurred_at<? GROUP BY u.id,u.fullname ORDER BY points DESC,u.fullname,u.id LIMIT 50 OFFSET ?','ssi',[$from,$until,($page-1)*50]);
$last=$store->rows('SELECT * FROM portal_points_runs ORDER BY id DESC LIMIT 1')[0]??null;
$pageTitle='Participation leaderboard';include dirname(__DIR__).'/header.php';
?>
<div class="container-fluid"><?php if(!$last || !$last['finished_at'] || $last['status']!=='complete'): ?><div class="alert alert-warning">Coverage is incomplete or the latest sync has not completed. Review source coverage before comparing totals.</div><?php endif; ?><h1 class="h4">Participation leaderboard</h1><p>Ledger totals · <a href="?range=month">This month</a> · <a href="?range=all">All time</a> · <a href="/dashboard/points/coverage.php">Check coverage</a> · <a href="/dashboard/points/rules-view.php">Scoring rules</a></p><p class="text-muted">These are contribution totals, not a measure of job performance. Roles have different opportunities to contribute.</p><div class="table-responsive"><table class="table"><thead><tr><th>User</th><th>Points</th><th>Tracking</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><?= Html::escape($row['fullname']) ?></td><td><?= (int)$row['points'] ?></td><td><a href="/dashboard/points/index.php?user=<?= (int)$row['id'] ?>&amp;range=<?= $from==='1000-01-01'?'all':'month' ?>">View ledger</a></td></tr><?php endforeach; ?></tbody></table></div><p>Page <?= $page ?> of <?= $pages ?> <?php if($page>1): ?><a href="?page=<?= $page-1 ?>&amp;range=<?= $from==='1000-01-01'?'all':'month' ?>">Previous</a><?php endif; ?> <?php if($page<$pages): ?><a href="?page=<?= $page+1 ?>&amp;range=<?= $from==='1000-01-01'?'all':'month' ?>">Next</a><?php endif; ?></p></div>
<?php include dirname(__DIR__).'/footer.php'; ?>
