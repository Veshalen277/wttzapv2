<?php
require __DIR__.'/bootstrap.php';
require_once __DIR__.'/src/Adjustment.php';
use Portal\Html;
$target=$pointsUser->id;
if($pointsManager && isset($_GET['user']))$target=max(1,(int)$_GET['user']);
$user=$store->rows('SELECT id,fullname FROM users_tbl WHERE id=?','i',[$target])[0]??null;
if(!$user){http_response_code(404);exit('User not found.');}
$balances=\Participation\Adjustment::totals($store,$target);
$range=in_array($_GET['range']??'', ['month','all'],true)?$_GET['range']:'month';
$from=$range==='all'?'1000-01-01':date('Y-m-01');$until=date('Y-m-d',strtotime('+1 day'));
$total=$balances[$range==='all'?'all_time':'month'];
$breakdown=$store->rows('SELECT e.kind,SUM(l.delta) points FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id WHERE l.user_id=? AND l.occurred_at>=? AND l.occurred_at<? GROUP BY e.kind ORDER BY points DESC','iss',[$target,$from,$until]);
$page=max(1,(int)($_GET['page']??1));
$count=(int)$store->rows('SELECT COUNT(*) n FROM portal_points_ledger WHERE user_id=?','i',[$target])[0]['n'];$pages=max(1,(int)ceil($count/30));$page=min($page,$pages);
$history=$store->rows('SELECT l.*,e.kind,e.source,e.source_ref,e.rule_version FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id WHERE l.user_id=? ORDER BY l.id DESC LIMIT 30 OFFSET ?','ii',[$target,($page-1)*30]);
$last=$store->rows('SELECT * FROM portal_points_runs ORDER BY id DESC LIMIT 1')[0]??null;
$unscored=(int)$store->rows('SELECT COUNT(*) n FROM portal_points_events WHERE user_id=? AND occurred_at IS NULL','i',[$target])[0]['n'];
$pageTitle='Participation points';include dirname(__DIR__).'/header.php';
?>
<link rel="stylesheet" href="<?= Html::escape(Html::asset('points/assets/points.css')) ?>">
<div class="points-module"><div class="points-toolbar"><div><small>PARTICIPATION LEDGER</small><h1><?= Html::escape($user['fullname']) ?></h1></div><nav><a href="/dashboard/points/rules-view.php">How points work</a><?php if($pointsManager): ?> · <a href="/dashboard/points/leaderboard.php">Leaderboard</a> · <a href="/dashboard/points/coverage.php">Coverage</a><?php endif; ?></nav></div>
<p class="text-muted">Last sync: <?= Html::escape($last['finished_at']??'Not yet completed') ?> · <?= Html::escape($last['status']??'Pending') ?>. Updates follow the server sync schedule.</p>
<?php if(!$last || !$last['finished_at'] || $last['status']!=='complete'): ?><div class="alert alert-warning">These totals may be incomplete. The administrator can review source coverage and sync status.</div><?php endif; ?>
<?php if($unscored): ?><p class="text-muted"><?= $unscored ?> activities have no reliable timestamp and remain unscored.</p><?php endif; ?>
<?php if($pointsUser->role===7): ?><p><a href="/dashboard/points/adjust.php?user_id=<?= $target ?>&amp;range=<?= $range ?>">Adjust this employee’s points</a></p><?php endif; ?>
<section class="card points-summary"><span><?= $range==='all'?'All-time points':'This month’s points' ?></span><strong><?= number_format($total) ?></strong><span>points</span><nav><a href="?user=<?= $target ?>&amp;range=month">This month</a> · <a href="?user=<?= $target ?>&amp;range=all">All time</a></nav></section>
<p>All time: <strong><?= number_format($balances['all_time']) ?></strong> · This month: <strong><?= number_format($balances['month']) ?></strong>. Historical activity is included only in the all-time total. Corrections are dated when recorded.</p>
<h2>Points by activity · <?= $range==='all'?'All time':'This month' ?></h2><div class="points-grid"><?php foreach($breakdown as $row): ?><div class="card points-category"><span><?= Html::escape($pointPolicy['rules'][$row['kind']]['label']??($row['kind']==='adjustment'?'Manual correction':$row['kind'])) ?></span><strong><?= (int)$row['points'] ?></strong></div><?php endforeach; ?><?php if(!$breakdown): ?><p>No scored activity in this period.</p><?php endif; ?></div>
<h2>Complete award and correction history</h2><p class="text-muted">History includes zero-point events and all dates. Removed content produces a separate correction; previous entries stay visible.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Activity date</th><th>Activity</th><th>Points</th><th>Explanation</th><th>Recorded</th></tr></thead><tbody>
<?php foreach($history as $entry): ?><tr><td><?= Html::escape($entry['occurred_at']??'Unknown') ?></td><td><?= Html::escape($pointPolicy['rules'][$entry['kind']]['label']??($entry['kind']==='adjustment'?'Manual correction':$entry['kind'])) ?><small class="d-block text-muted"><?= Html::escape($entry['source'].' '.$entry['source_ref']) ?></small></td><td><?= (int)$entry['delta']>0?'+':'' ?><?= (int)$entry['delta'] ?></td><td><?= Html::escape($entry['reason']) ?></td><td><?= Html::escape($entry['recorded_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><nav class="points-pagination"><?php if($page>1): ?><a href="?user=<?= $target ?>&amp;range=<?= $range ?>&amp;page=<?= $page-1 ?>">Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if($page<$pages): ?><a href="?user=<?= $target ?>&amp;range=<?= $range ?>&amp;page=<?= $page+1 ?>">Next</a><?php endif; ?></nav></div>
<?php include dirname(__DIR__).'/footer.php'; ?>
