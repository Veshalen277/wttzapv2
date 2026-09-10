<?php
require __DIR__.'/../app/bootstrap.php';
\Portal\Auth::requireRoles([2,3,4,5,7]);
require __DIR__.'/src/Inventory.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$inventory=new \Inventory\Inventory($con);$e=static fn($v)=>\Portal\Html::escape((string)$v);
$error='';$ready=true;$pageTitle='Inventory';$portalNavigationPath='/dashboard/inventory/index.php';
try{$inventory->rows('SELECT item_id FROM portal_inventory_settings LIMIT 1');$inventory->rows('SELECT id FROM portal_inventory_movements LIMIT 1');}catch(Throwable $ex){$ready=false;}
if($ready&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 try{
  if(!\Portal\Csrf::valid($_POST['_csrf']??null))throw new InvalidArgumentException('Your session expired. Refresh and try again.');
  $action=$_POST['action']??'';
  if($action==='move'){$inventory->move([$_POST],(string)($_POST['kind']??''),(string)($_POST['request_key']??''),$portalUser->id);}
  elseif($action==='csv_preview'){
   $file=$_FILES['csv']??[];if(($file['error']??-1)!==UPLOAD_ERR_OK||($file['size']??0)>262144)throw new InvalidArgumentException('Use a CSV smaller than 256 KB.');
   $h=fopen($file['tmp_name'],'r');if(fgetcsv($h)!==['item_id','quantity','reason'])throw new InvalidArgumentException('CSV header must be item_id,quantity,reason.');
   $lines=[];while(($r=fgetcsv($h))!==false){if(count($r)!==3||count($lines)>=200)throw new InvalidArgumentException('Use three columns and at most 200 rows.');$lines[]=['item_id'=>\Inventory\Inventory::number($r[0]),'quantity'=>\Inventory\Inventory::number($r[1]),'reason'=>$r[2]];}fclose($h);
   if(!in_array($_POST['kind']??'',['receive','issue','count'],true)||!$lines)throw new InvalidArgumentException('Select an operation and provide rows.');
   $_SESSION['inventory_preview']=['lines'=>$lines,'kind'=>$_POST['kind'],'key'=>bin2hex(random_bytes(32))];
  }elseif($action==='csv_commit'){
   $preview=$_SESSION['inventory_preview']??null;
   if(!$preview||!hash_equals($preview['key'],(string)($_POST['request_key']??'')))throw new InvalidArgumentException('Preview expired. Upload again.');
   $inventory->move($preview['lines'],$preview['kind'],$preview['key'],$portalUser->id);unset($_SESSION['inventory_preview']);
  }elseif(in_array($action,['item','category'],true)){$inventory->catalog($_POST);}
  else throw new InvalidArgumentException('Unknown action.');
  $_SESSION['msg']=$action==='csv_preview'?'Review the import below before applying it.':'Inventory saved.';$_SESSION['msg_type']='success';header('Location: /dashboard/inventory/index.php');exit;
 }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){error_log('Inventory: '.$ex->getMessage());$error='Nothing was saved. Check the server error log or ask your administrator.';}
}
$items=[];$categories=[];$history=[];$opening=[];$summary=[];$edit=[];$q=is_string($_GET['q']??null)?trim($_GET['q']):'';
if($ready){
 try{
  $categories=$inventory->rows('SELECT category_id,category_name FROM sc_stock_categories ORDER BY category_name');
  $items=$inventory->rows('SELECT i.*,c.category_name,COALESCE(s.reorder_level,0) reorder_level,COALESCE(s.archived,0) archived FROM sc_stock_items i LEFT JOIN sc_stock_categories c ON c.category_id=i.category_id LEFT JOIN portal_inventory_settings s ON s.item_id=i.item_id WHERE i.item_name LIKE ? ORDER BY i.item_name LIMIT 501','s',['%'.$q.'%']);
  $summary=$inventory->rows('SELECT COUNT(*) active_items,COALESCE(SUM(i.qty_on_hand=0),0) empty_items,COALESCE(SUM(i.qty_on_hand>0 AND i.qty_on_hand<=COALESCE(s.reorder_level,0)),0) low_items FROM sc_stock_items i LEFT JOIN portal_inventory_settings s ON s.item_id=i.item_id WHERE COALESCE(s.archived,0)=0')[0];
  $opening=$inventory->rows("SELECT m.*,i.item_name FROM portal_inventory_movements m LEFT JOIN sc_stock_items i ON i.item_id=m.item_id WHERE m.kind='opening' ORDER BY m.id DESC LIMIT 50");
  $history=$inventory->rows('SELECT m.*,i.item_name,u.fullname FROM portal_inventory_movements m LEFT JOIN sc_stock_items i ON i.item_id=m.item_id LEFT JOIN users_tbl u ON u.id=m.actor_id WHERE m.kind<>\'opening\' ORDER BY m.id DESC LIMIT 100');
 }catch(Throwable $ex){error_log('Inventory read: '.$ex->getMessage());$ready=false;$error='Inventory schema needs attention. Ask your administrator to check the setup.';}
}
require __DIR__.'/../header.php';
?>
<link rel="stylesheet" href="<?= $e(\Portal\Html::asset('inventory/assets/inventory.css')) ?>">
<div class="inventory-page"><header class="inventory-heading"><div><p class="inventory-eyebrow">OPERATIONS / STOCK CONTROL</p><h1>Inventory</h1><p>See what’s in stock, what needs ordering, and what your team has updated.</p></div><a class="btn btn-primary" data-inventory-editor data-editor-mode="move" href="editor.php?mode=move&amp;q=<?= $e(rawurlencode($q)) ?>">Update stock</a></header>
<?php if($error):?><div class="alert alert-danger" role="alert"><?= $e($error) ?></div><?php endif; ?>
<?php if(!$ready):?><div class="alert alert-warning">Inventory setup is incomplete. Your administrator needs to follow the inventory installation guide before using this screen.</div><?php else: ?>
<div class="inventory-summary" aria-label="Stock overview">
<article><span>Items you stock</span><strong><?= (int)$summary['active_items'] ?></strong><small>Active items in your inventory</small></article>
<article><span>Running low</span><strong><?= (int)$summary['low_items'] ?></strong><small>Still available, but ready to reorder</small></article>
<article><span>Out of stock</span><strong><?= (int)$summary['empty_items'] ?></strong><small>Items with none left on hand</small></article>
</div>
<nav class="inventory-tabs" aria-label="Inventory sections"><a href="#catalog">Stock on hand</a><a data-inventory-editor data-editor-mode="move" href="editor.php?mode=move&amp;q=<?= $e(rawurlencode($q)) ?>">Update stock</a><a data-inventory-editor data-editor-mode="item" href="editor.php?mode=item&amp;q=<?= $e(rawurlencode($q)) ?>">Add item</a><a href="#item">Categories</a><a href="#history">Recent activity</a><a href="#import">Upload a spreadsheet</a></nav>
<section class="inventory-panel" id="catalog"><div class="inventory-panel-heading"><div><h2>Stock on hand</h2><p>Find an item, check how much is left, or record a stock update.</p></div><form method="get"><label class="visually-hidden" for="inventory-search">Search items</label><input id="inventory-search" name="q" value="<?= $e($q) ?>" placeholder="Search item name"><button class="btn btn-outline-secondary">Search</button></form></div>
<div class="inventory-table"><table><thead><tr><th>Item</th><th>Category</th><th>On hand</th><th>Order more at</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach(array_slice($items,0,500) as $row): ?><tr data-inventory-row="<?= (int)$row['item_id'] ?>"><td><strong><?= $e($row['item_name']) ?></strong><small><?= $e($row['supplier']??'') ?></small></td><td><?= $e($row['category_name']??'Uncategorised') ?></td><td class="inventory-quantity"><?= (int)$row['qty_on_hand'] ?></td><td><?= (int)$row['reorder_level'] ?></td><td><span class="inventory-status"><?= $row['archived']?'Archived':((int)$row['qty_on_hand']===0?'Out of stock':((int)$row['qty_on_hand']<=(int)$row['reorder_level']?'Reorder':'Available')) ?></span></td><td><a data-inventory-editor data-editor-mode="item" href="editor.php?mode=item&amp;item_id=<?= (int)$row['item_id'] ?>&amp;q=<?= $e(rawurlencode($q)) ?>">Edit details</a> · <a data-inventory-editor data-editor-mode="move" href="editor.php?mode=move&amp;item_id=<?= (int)$row['item_id'] ?>&amp;q=<?= $e(rawurlencode($q)) ?>">Update stock</a></td></tr><?php endforeach; ?>
<?php if(!$items):?><tr><td colspan="6">No matching stock items. Choose Add item to get started.</td></tr><?php endif; ?></tbody></table></div><?php if(count($items)>500):?><p>Showing 500 items. Narrow your search to find other items.</p><?php endif; ?></section>
<section class="inventory-panel" id="item"><details><summary>Manage categories</summary><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="category"><label>Category<select name="category_id"><option value="0">Create new category</option><?php foreach($categories as $c):?><option value="<?= (int)$c['category_id'] ?>">Rename <?= $e($c['category_name']) ?></option><?php endforeach; ?></select></label><label>Category name<input name="category_name" maxlength="100" required></label><button class="btn btn-outline-secondary">Save category</button></form></details></section>
<details class="inventory-panel inventory-disclosure" id="import" <?= isset($_SESSION['inventory_preview'])?'open':'' ?>><summary>Update several items from a spreadsheet</summary><p>Upload a CSV file and review the changes before saving.</p><details><summary>File format and item IDs</summary><p>Use the columns <code>item_id,quantity,reason</code>, with one row per item, up to 200 rows. You can find an item’s ID below. Use its ID in your file; keep quantities as whole numbers.</p><div class="inventory-table"><table><thead><tr><th>Item</th><th>ID</th></tr></thead><tbody><?php foreach(array_slice($items,0,500) as $i):?><tr><td><?= $e($i['item_name']) ?></td><td><?= (int)$i['item_id'] ?></td></tr><?php endforeach; ?></tbody></table></div></details><form method="post" enctype="multipart/form-data" class="inventory-import"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="csv_preview"><label>Operation<select name="kind"><option value="receive">Receive</option><option value="issue">Usage</option><option value="count">Stocktake</option></select></label><label>CSV file<input type="file" name="csv" accept=".csv,text/csv" required></label><button class="btn btn-outline-secondary">Preview import</button></form>
<?php if(isset($_SESSION['inventory_preview'])):$preview=$_SESSION['inventory_preview'];?><h3>Review <?= $e($preview['kind']) ?> import</h3><div class="inventory-table"><table><thead><tr><th>Item ID</th><th>Quantity</th><th>Reason</th></tr></thead><tbody><?php foreach($preview['lines'] as $line):?><tr><td><?= (int)$line['item_id'] ?></td><td><?= (int)$line['quantity'] ?></td><td><?= $e($line['reason']) ?></td></tr><?php endforeach; ?></tbody></table></div><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="csv_commit"><input type="hidden" name="request_key" value="<?= $e($preview['key']) ?>"><button class="btn btn-primary">Apply these movements</button></form><?php endif; ?></details>
<section class="inventory-panel" id="history"><h2>Recent stock activity</h2><p>See who updated your stock and why. Showing the latest 100 updates.</p>
<?php if(!$history):?><div class="inventory-empty"><strong>No stock updates yet</strong><p>Your current stock is shown above. New deliveries, usage and counts will appear here as your team records them.</p></div><?php endif; ?>
<div class="inventory-table"><table><thead><tr><th>Item</th><th>What happened</th><th>Quantity change</th><th>Stock left</th><th>Team member / note</th><th>When</th></tr></thead><tbody>
<?php foreach($history as $m):?><tr><td><?= $e($m['item_name']??'Unavailable item') ?></td><td><?= $e(['receive'=>'Stock received','issue'=>'Used or sold','count'=>'Stock counted'][$m['kind']]??'Stock updated') ?></td><td><?= (int)$m['delta']>0?'+':'' ?><?= (int)$m['delta'] ?></td><td><?= (int)$m['quantity_after'] ?></td><td><?= $e($m['fullname']??'Team member unavailable') ?><small><?= $e($m['reason']) ?></small></td><td><?= $e($m['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<details class="inventory-panel inventory-disclosure inventory-technical"><summary>Record details and starting balances</summary><p>For troubleshooting: these are the balances recorded when the new inventory system was set up. They are kept separately from your team’s stock updates. Showing the latest 50 setup records; the full record remains in the inventory history database.</p><div class="inventory-table"><table><thead><tr><th>Item</th><th>Starting quantity</th><th>Recorded at</th><th>Record ID</th></tr></thead><tbody><?php foreach($opening as $m):?><tr><td><?= $e($m['item_name']??('#'.$m['item_id'])) ?></td><td><?= (int)$m['quantity_after'] ?></td><td><?= $e($m['created_at']) ?></td><td><?= (int)$m['id'] ?></td></tr><?php endforeach; ?></tbody></table></div><p>Developer reference: <code>inventory/README.md</code> explains migrations, reconciliation and troubleshooting. No records are removed by this view.</p></details>
<link rel="stylesheet" href="<?= $e(\Portal\Html::asset('inventory/assets/editor.css')) ?>">
<script src="<?= $e(\Portal\Html::asset('inventory/assets/editor.js')) ?>" defer></script>
<?php endif; ?></div><?php require __DIR__.'/../footer.php'; ?>
