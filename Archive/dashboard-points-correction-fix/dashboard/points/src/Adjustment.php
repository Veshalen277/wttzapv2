<?php
namespace Participation;

final class Adjustment
{
    public static function delta($operation, $amount): int
    {
        if (!in_array($operation, ['add', 'remove'], true) || !is_string($amount) ||
            !preg_match('/^[1-9][0-9]{0,3}$/D', $amount) || (int) $amount > 1000) {
            throw new \InvalidArgumentException('Choose Add or Remove and enter a whole number from 1 to 1000.');
        }
        return $operation === 'remove' ? -(int) $amount : (int) $amount;
    }

    public static function totals(Store $store, int $user): array
    {
        $row = $store->rows(
            'SELECT COALESCE(SUM(delta),0) all_time, COALESCE(SUM(CASE WHEN occurred_at>=? THEN delta ELSE 0 END),0) month FROM portal_points_ledger WHERE user_id=? AND occurred_at<?',
            'sis', [date('Y-m-01'), $user, date('Y-m-d', strtotime('+1 day'))]
        )[0];
        return ['all_time' => (int) $row['all_time'], 'month' => (int) $row['month']];
    }

    public static function record(Store $store, int $actor, int $user, int $delta, string $reason, string $nonce): array
    {
        if ($actor < 1 || $user < 1 || $delta === 0 || abs($delta) > 1000 ||
            mb_strlen($reason) < 15 || mb_strlen($reason) > 180 || !preg_match('/^[a-f0-9]{32}$/D', $nonce)) {
            throw new \InvalidArgumentException('Select an employee and provide a reason of 15–180 characters.');
        }
        // Cooperate with sync: before/after receipts must not include concurrent backfills.
        $lock = 'portal_points_' . substr(hash('sha256', $store->rows('SELECT DATABASE() db')[0]['db']), 0, 32);
        if (!(int) $store->rows('SELECT GET_LOCK(?,5) acquired', 's', [$lock])[0]['acquired']) {
            throw new \RuntimeException('Points sync is busy. Retry shortly.');
        }
        try {
            $store->db->begin_transaction();
            try {
                if (!$store->rows('SELECT id FROM users_tbl WHERE id=? FOR UPDATE', 'i', [$user])) {
                    throw new \InvalidArgumentException('Employee not found.');
                }
                $key = hash('sha256', 'manual:' . $actor . ':' . $nonce);
                $audit = 'Admin #' . $actor . ': ' . $reason;
                $existing = $store->rows('SELECT * FROM portal_points_events WHERE event_key=? FOR UPDATE', 's', [$key])[0] ?? null;
                $before = self::totals($store, $user);
                if ($existing) {
                    if ((int) $existing['user_id'] !== $user || (int) $existing['awarded'] !== $delta || $existing['reason'] !== $audit) {
                        throw new \InvalidArgumentException('This request was used for a different correction. Reload before making another correction.');
                    }
                    $id = (int) $existing['id'];
                } else {
                    $when = date('Y-m-d H:i:s');
                    $id = $store->write("INSERT INTO portal_points_events(event_key,source,source_ref,user_id,kind,occurred_at,awarded,rule_version,reason,seen_run) VALUES(?,'manual',?,?,'adjustment',?,?,1,?,0)", 'ssisis', [$key, $nonce, $user, $when, $delta, $audit]);
                    $store->write('INSERT INTO portal_points_ledger(event_id,revision,user_id,delta,occurred_at,reason) VALUES(?,1,?,?,?,?)', 'iiiss', [$id, $user, $delta, $when, $audit]);
                }
                $after = self::totals($store, $user);
                $store->db->commit();
                return ['id' => $id, 'duplicate' => (bool) $existing, 'before' => $before, 'after' => $after];
            } catch (\Throwable $error) {
                $store->db->rollback();
                throw $error;
            }
        } finally {
            $store->rows('SELECT RELEASE_LOCK(?)', 's', [$lock]);
        }
    }
}
