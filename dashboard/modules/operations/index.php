<?php
require_once __DIR__.'/../../app/bootstrap.php';
\Portal\Modules::guard('operations');\Portal\Auth::requireRoles([7]);
$checks=[];
$check=static function(string $label,string $sql,callable $explain)use($con,&$checks):void{
 try{$row=$con->query($sql)->fetch_assoc();$checks[]=['label'=>$label,'detail'=>$explain($row?:[])];}
 catch(Throwable $e){error_log('Operations '.$label.': '.$e->getMessage());$checks[]=['label'=>$label,'detail'=>'Could not check. Review the module installation and server log.'];}
};
$check('Participation updates',"SELECT status,TIMESTAMPDIFF(MINUTE,finished_at,NOW()) age FROM portal_points_runs ORDER BY id DESC LIMIT 1",static function($r){if(!$r||$r['age']===null)return 'No completed update found. Check the scheduled job.';return 'Latest run: '.$r['status'].', '.$r['age'].' minutes ago.'.((int)$r['age']>15?' The scheduled update needs attention.':'');});
$check('Participation coverage',"SELECT COUNT(*) n FROM portal_points_sources WHERE status<>'ok'",static fn($r)=>$r['n'].' sources need review. Open Points Coverage for the reason.');
$check('Inventory balances','SELECT COUNT(*) n FROM (SELECT i.item_id FROM sc_stock_items i LEFT JOIN portal_inventory_movements m ON m.item_id=i.item_id GROUP BY i.item_id,i.qty_on_hand HAVING i.qty_on_hand<>COALESCE(SUM(m.delta),0)) mismatches',static fn($r)=>$r['n'].' balances differ from movement history.'.((int)$r['n']?' Investigate before changing affected items.':' No balance differences found.'));
$check('Cashup submission records','SELECT COUNT(*) n FROM portal_cashup_requests WHERE report_id IS NULL',static fn($r)=>$r['n'].' submission records are missing a saved report reference.');
$check('Archived reports','SELECT COUNT(*) n FROM portal_report_archives',static fn($r)=>$r['n'].' archive actions recorded for review.');
$pageTitle='System checks';require __DIR__.'/../../header.php';
?>
<div class="container-fluid"><h1>System checks</h1><p>Operational checks for administrators. These are not a security certification or a replacement for backup and restore tests.</p><div class="row g-3">
<?php foreach($checks as $item):?><div class="col-md-6"><section class="card p-4 h-100"><h2><?= \Portal\Html::escape($item['label']) ?></h2><p><?= \Portal\Html::escape($item['detail']) ?></p></section></div><?php endforeach; ?></div>
<p class="mt-4"><a href="/dashboard/points/coverage.php">Points coverage</a> · <a href="/dashboard/inventory/index.php">Inventory</a> · <a href="/dashboard/reports/view_income_reports.php">Cashup history</a></p>
<details><summary>Developer and release information</summary><p>Run the CLI checks in PRODUCTION-READINESS.md. This page reads a database snapshot on load; it does not send alerts, repair records or execute migrations.</p></details></div>
<?php require __DIR__.'/../../footer.php'; ?>
