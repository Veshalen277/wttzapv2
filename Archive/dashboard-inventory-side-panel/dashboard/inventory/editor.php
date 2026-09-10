<?php
require __DIR__ . '/../app/bootstrap.php';
\Portal\Auth::requireRoles([2,3,4,5,7]);
require __DIR__ . '/src/Inventory.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Cache-Control: no-store');
$inventory = new \Inventory\Inventory($con);
$e = static fn($value) => \Portal\Html::escape((string) $value);
$json = ($_SERVER['HTTP_X_INVENTORY_EDITOR'] ?? '') === '1';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$input = $post ? $_POST : $_GET;
$mode = ($input['mode'] ?? '') === 'move' ? 'move' : 'item';
$q = is_string($input['q'] ?? null) ? trim($input['q']) : '';
$return = '/dashboard/inventory/index.php' . ($q !== '' ? '?q=' . rawurlencode($q) : '');
$id = filter_var($input['item_id'] ?? '0', FILTER_VALIDATE_INT);
$error = '';
$status = 200;
$item = [];
$categories = [];
$choices = [];
$key = is_string($input['request_key'] ?? null) ? $input['request_key'] : ($post ? '' : bin2hex(random_bytes(32)));
try {
    if ($id === false || $id < 0) throw new InvalidArgumentException('Invalid item. Return to the stock list and select it again.');
    if ($id) {
        $item = $inventory->rows('SELECT i.*,COALESCE(s.reorder_level,0) reorder_level,COALESCE(s.archived,0) archived FROM sc_stock_items i LEFT JOIN portal_inventory_settings s ON s.item_id=i.item_id WHERE i.item_id=?', 'i', [$id])[0] ?? [];
        if (!$item) { $status = 404; throw new InvalidArgumentException('This item no longer exists. Return to the stock list.'); }
    }
    if ($post) {
        if (!\Portal\Csrf::valid($_POST['_csrf'] ?? null)) {
            $status = 403;
            throw new InvalidArgumentException('Your session expired. Close and reopen the editor before saving.');
        }
        if ($mode === 'move') {
            $kind = is_string($_POST['kind'] ?? null) ? $_POST['kind'] : '';
            $created = $inventory->move([$_POST], $kind, $key, $portalUser->id);
            $message = $created ? 'Stock update saved.' : 'This stock update was already saved; it was not applied twice.';
        } else {
            $data = $_POST;
            $data['action'] = 'item';
            $inventory->catalog($data);
            $message = 'Item details saved.';
        }
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>true, 'message'=>$message], JSON_THROW_ON_ERROR);
        } else {
            \Portal\Flash::set($message);
            header('Location: ' . $return, true, 303);
        }
        exit;
    }
} catch (InvalidArgumentException $exception) {
    $error = $exception->getMessage();
    if ($status === 200) $status = 422;
} catch (Throwable $exception) {
    error_log('Inventory editor: ' . $exception->getMessage());
    $error = 'The request could not be confirmed. Check stock history before retrying. Your entries have been kept.';
    $status = 500;
}
try {
    $categories = $inventory->rows('SELECT category_id,category_name FROM sc_stock_categories ORDER BY category_name');
    if ($mode === 'move' && !$id) {
        $choices = $inventory->rows('SELECT i.item_id,i.item_name,i.qty_on_hand FROM sc_stock_items i LEFT JOIN portal_inventory_settings s ON s.item_id=i.item_id WHERE COALESCE(s.archived,0)=0 AND i.item_name LIKE ? ORDER BY i.item_name LIMIT 500', 's', ['%' . $q . '%']);
    }
} catch (Throwable $exception) {
    error_log('Inventory editor options: ' . $exception->getMessage());
    $error = 'Inventory is unavailable. Close the editor and try again later.';
    $status = 503;
}
$value = static function (string $name, string $default = '') use ($post, $item): string {
    $source = $post ? $_POST : $item;
    return is_scalar($source[$name] ?? null) ? (string) $source[$name] : $default;
};
$title = $mode === 'move' ? 'Update stock' : ($id ? 'Edit item details' : 'Add an item');
ob_start();
?>
<section class="inventory-editor-content">
<p class="inventory-eyebrow">INVENTORY</p><h2 id="inventory-editor-title" tabindex="-1"><?= $e($title) ?></h2>
<?php if ($item): ?><p><strong><?= $e($item['item_name']) ?></strong> · <?= (int) $item['qty_on_hand'] ?> on hand</p><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert" tabindex="-1"><?= $e($error) ?></div><?php endif; ?>
<?php if (!in_array($status, [403,404,503], true) && $id !== false && $id >= 0): ?>
<form method="post" action="/dashboard/inventory/editor.php" data-inventory-form>
<?= \Portal\Csrf::field() ?>
<input type="hidden" name="mode" value="<?= $mode ?>"><input type="hidden" name="q" value="<?= $e($q) ?>">
<?php if ($mode === 'move'): ?>
<input type="hidden" name="request_key" value="<?= $e($key) ?>">
<?php if ($id): ?><input type="hidden" name="item_id" value="<?= $id ?>"><?php else: ?>
<label>Item<select name="item_id" required><option value="">Choose an item</option><?php foreach ($choices as $choice): ?><option value="<?= (int) $choice['item_id'] ?>"><?= $e($choice['item_name']) ?> — <?= (int) $choice['qty_on_hand'] ?> on hand</option><?php endforeach; ?></select></label>
<p>Showing up to 500 items matching your search. To narrow the list, return to inventory and search.</p><?php endif; ?>
<label>What happened?<select name="kind"><?php foreach (['receive'=>'New stock arrived','issue'=>'Stock was used or sold','count'=>'I counted what is left'] as $kind=>$label): ?><option value="<?= $kind ?>" <?= $value('kind','receive') === $kind ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></label>
<label>Quantity<input name="quantity" type="number" min="0" max="999999999" step="1" value="<?= $e($value('quantity')) ?>" required></label>
<p>For arrivals or usage, enter the amount added or removed. For a count, enter the total quantity remaining.</p>
<label>Reason<input name="reason" maxlength="255" value="<?= $e($value('reason')) ?>" required></label>
<?php else: ?>
<input type="hidden" name="item_id" value="<?= (int) $id ?>">
<p>Edit the item’s details here. Use Update stock to change its quantity.</p>
<label>Item name<input name="item_name" maxlength="100" value="<?= $e($value('item_name')) ?>" required></label>
<label>Category<select name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['category_id'] ?>" <?= $value('category_id') === (string) $category['category_id'] ? 'selected' : '' ?>><?= $e($category['category_name']) ?></option><?php endforeach; ?></select></label>
<div class="inventory-fields"><?php foreach (['cost_price'=>'Unit cost','retail_price'=>'Retail price','reorder_level'=>'Order more at'] as $name=>$label): ?><label><?= $e($label) ?><input type="number" name="<?= $name ?>" min="0" step="<?= $name === 'reorder_level' ? '1' : '0.01' ?>" value="<?= $e($value($name,'0')) ?>" required></label><?php endforeach; ?></div>
<label>Supplier<input name="supplier" maxlength="100" value="<?= $e($value('supplier')) ?>"></label>
<?php if ($id): ?><label><input type="checkbox" name="archived" <?= ($post ? isset($_POST['archived']) : !empty($item['archived'])) ? 'checked' : '' ?>> Archive item (stock must be zero)</label><?php endif; ?>
<?php endif; ?>
<div class="inventory-editor-actions"><button class="btn btn-primary" type="submit">Save <?= $mode === 'move' ? 'stock update' : 'item' ?></button><a href="<?= $e($return) ?>" data-inventory-cancel>Cancel</a></div>
</form><?php else: ?><a href="<?= $e($return) ?>" data-inventory-cancel>Back to inventory</a><?php endif; ?>
</section>
<?php
$html = ob_get_clean();
http_response_code($status);
if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false, 'html'=>$html], JSON_THROW_ON_ERROR);
    exit;
}
$pageTitle = $title;
$portalNavigationPath = '/dashboard/inventory/index.php';
require __DIR__ . '/../header.php';
?>
<link rel="stylesheet" href="<?= $e(\Portal\Html::asset('inventory/assets/inventory.css')) ?>">
<link rel="stylesheet" href="<?= $e(\Portal\Html::asset('inventory/assets/editor.css')) ?>">
<div class="inventory-page inventory-editor-page"><?= $html ?></div>
<?php require __DIR__ . '/../footer.php'; ?>
