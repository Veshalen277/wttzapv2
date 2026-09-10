<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/src/Adjustment.php';
if ($pointsUser->role !== 7) { http_response_code(403); exit('Access denied.'); }
use Portal\Html;
use Participation\Adjustment;

$error = null;
$post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$input = $post ? $_POST : $_GET;
$range = ($input['range'] ?? '') === 'all' ? 'all' : 'month';
$uid = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$nonce = is_string($input['request_id'] ?? null) && preg_match('/^[a-f0-9]{32}$/D', $input['request_id'])
    ? $input['request_id'] : bin2hex(random_bytes(16));
$operation = ($input['operation'] ?? '') === 'add' ? 'add' : 'remove';
$amount = is_string($input['amount'] ?? null) ? $input['amount'] : '';
$reason = is_string($input['reason'] ?? null) ? trim($input['reason']) : '';
if ($post) {
    if (!\Portal\Csrf::valid($_POST['_csrf'] ?? null)) { http_response_code(403); exit('Refresh and try again.'); }
    try {
        if (!is_string($_POST['request_id'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D', $_POST['request_id'])) {
            throw new \InvalidArgumentException('Reload the correction form before submitting.');
        }
        $delta = Adjustment::delta($_POST['operation'] ?? null, $_POST['amount'] ?? null);
        $receipt = Adjustment::record($store, $pointsUser->id, $uid, $delta, $reason, $nonce);
        if ($receipt['duplicate']) {
            $message = 'Correction #' . $receipt['id'] . ' was already recorded. This submission changed no points. Current all-time total: ' . $receipt['after']['all_time'] . '; this month: ' . $receipt['after']['month'] . '.';
        } else {
            $message = 'Correction #' . $receipt['id'] . ': ' . ($delta < 0 ? 'removed ' : 'added ') . abs($delta) . ' points. All time: ' . $receipt['before']['all_time'] . ' → ' . $receipt['after']['all_time'] . '. This month: ' . $receipt['before']['month'] . ' → ' . $receipt['after']['month'] . '. No other awards were created by this correction.';
        }
        \Portal\Flash::set($message);
        \Portal\Http\Response::redirect('/dashboard/points/index.php?user=' . $uid . '&range=' . $range);
    } catch (\InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (\Throwable $exception) {
        error_log('Points adjustment: ' . $exception->getMessage());
        $error = 'Correction could not be confirmed. Check the ledger, then retry this same form; its request number is retained.';
    }
}
$users = $store->rows('SELECT id,fullname FROM users_tbl ORDER BY fullname');
$selected = null;
foreach ($users as $user) if ((int) $user['id'] === $uid) $selected = $user;
$totals = $selected ? Adjustment::totals($store, $uid) : null;
$pageTitle = 'Adjust employee points';
include dirname(__DIR__) . '/header.php';
?>
<div class="container" style="max-width:800px">
<h1 class="h4">Adjust employee points</h1>
<p>Choose the employee, then add or remove points. Corrections are dated today and retain your administrator ID and reason in the audit trail.</p>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Html::escape($error) ?></div><?php endif; ?>
<form method="get" class="card p-4 mb-3">
<input type="hidden" name="range" value="<?= $range ?>">
<label for="choose-user">Employee</label><select id="choose-user" name="user_id" class="form-select mb-3" required>
<option value="">Choose an employee</option>
<?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>" <?= (int) $user['id'] === $uid ? 'selected' : '' ?>><?= Html::escape($user['fullname']) ?></option><?php endforeach; ?>
</select><button class="btn btn-secondary" type="submit">Load employee totals</button></form>
<?php if ($selected): ?>
<section class="card p-4 mb-3"><h2 class="h5"><?= Html::escape($selected['fullname']) ?></h2>
<p>All time: <strong><?= number_format($totals['all_time']) ?></strong> points · This month: <strong><?= number_format($totals['month']) ?></strong> points</p>
<p class="text-muted mb-0">Balances may change during synchronization. The saved receipt shows the actual before-and-after totals. A correction changes both totals by the same amount; it does not set a target balance.</p></section>
<form method="post" class="card p-4">
<?= \Portal\Csrf::field() ?>
<input type="hidden" name="request_id" value="<?= Html::escape($nonce) ?>">
<input type="hidden" name="user_id" value="<?= $uid ?>"><input type="hidden" name="range" value="<?= $range ?>">
<fieldset class="mb-3"><legend class="h6">Action</legend>
<label class="me-3"><input type="radio" name="operation" value="remove" <?= $operation === 'remove' ? 'checked' : '' ?> required> Remove points</label>
<label><input type="radio" name="operation" value="add" <?= $operation === 'add' ? 'checked' : '' ?>> Add points</label></fieldset>
<label for="adjust-amount">Number of points</label><input id="adjust-amount" class="form-control mb-3" type="number" name="amount" min="1" max="1000" step="1" value="<?= Html::escape($amount) ?>" required>
<label for="adjust-reason">Reason for this correction</label><textarea id="adjust-reason" class="form-control mb-3" name="reason" minlength="15" maxlength="180" required><?= Html::escape($reason) ?></textarea>
<p>Example: Remove 13 changes an all-time balance of 1,088 to 1,075. Historical participation stays recorded. A monthly total can become negative.</p>
<button class="btn btn-primary" type="submit">Record correction</button>
<a class="mt-3" href="/dashboard/points/index.php?user=<?= $uid ?>&amp;range=<?= $range ?>">Back to employee points</a>
</form><?php endif; ?></div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
