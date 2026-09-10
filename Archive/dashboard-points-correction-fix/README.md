# Points correction fix and database findings

## What happened

The supplied database records one deduction of **13 points** for employee ID 27, at **2026-09-09 12:18:22**, ledger/event ID **2354**. The all-time total changed from **1,088 to 1,075**.

| Activity | Points |
| --- | ---: |
| Work reports | 610 |
| Cashups | 420 |
| Daily sign-ins | 58 |
| Manual correction | -13 |
| Total | 1,075 |

The 1,088 earned points were recorded during the initial sync at 12:10:21–12:10:24, before the correction. They relate to activity dated in 2024 and 2025. September 2026 has only the -13 correction in this employee's supplied ledger.

**There is no +1,070 entry caused by the correction in this snapshot.** The old correction handler always redirected to `range=all`, even though the points page defaults to the current month. That is a confirmed misleading display behavior. The database cannot prove which earlier screen or total you were comparing against, but it proves the deduction's sign and resulting balance.

Do not subtract another 1,070 to compensate: that would remove existing historical participation.

## What this patch changes

- Separate **Add points** and **Remove points** controls; enter a positive whole-number amount.
- Explicit employee selection, with monthly and all-time balances before editing.
- Receipts show the actual before-and-after totals and a correction reference number.
- Preserve the selected monthly/all-time view after saving. Show both balances on the points page.
- Retain the request identifier after errors so retrying does not silently become a new correction.
- Reject reuse of a request identifier with different employee, amount or reason. The old handler treated these collisions as success without checking the payload.
- Serialize corrections with the existing points sync lock so sync backfills cannot contaminate the receipt's before-and-after calculation.
- Extend the audit to detect missing employees/events and inconsistent revision counts.
- Add adjustment sign/bounds regression checks to the release check.

No migration is needed for this patch. It does not rewrite balances, delete history, reset notification state, or infer missing employee ownership.

## Install

1. Back up the deployed files and database. Use the test site first.
2. Extract this ZIP and merge its `dashboard` folder into your current website root, replacing the included files. This is a focused patch, not the whole application.
3. Reload any open correction forms; forms opened before the update need refreshing.
4. From your CURRENT site root run:

```bash
cd /home/bethelin/wttzap
php -l dashboard/points/adjust.php
php -l dashboard/points/index.php
php -l dashboard/points/src/Adjustment.php
php dashboard/points/tests/adjustment.php
php dashboard/points/audit.php
```

For another domain use its actual folder. The audit will still report the unresolved source-data issues below; this patch deliberately does not hide them.

Before running the combined sync again, review the notification backlog below. The supplied snapshot has no notifications and a zero baseline, so the next notification sync can announce imported historical awards.

## Test the correction flow

Use a designated test account on staging, not another corrective deduction on the employee involved in this incident.

1. Open an employee's monthly points page, choose Adjust, then Remove 13 with a meaningful reason.
2. Confirm the monthly view stays selected. Both totals must decrease by exactly 13; the receipt must show it.
3. Re-submit the identical POST/request identifier: no extra ledger entry; the response says it was already recorded.
4. Submit a changed amount with that same request identifier: reject it.
5. Make a separate Add 13 request if reversing the test; both entries must remain in history.
6. Try zero, a negative amount, a decimal, more than 1,000 and an unauthorized account: reject them.
7. Run sync during a correction test: the correction should wait briefly or ask for a retry; it must not include sync changes in its receipt.
8. Confirm the all-time view also remains selected when starting there.

This environment has no PHP/MySQL runtime. The supplied SQL snapshot was parsed and reconciled offline; PHP execution, browser testing and database concurrency testing must be run on staging. The included PHP regression test is not a substitute for those integration checks.

## Database findings

Scope: the complete supplied SQL export (59 tables) was parsed; the focused checks below concern points, linked activity, notification state, active inventory and cashup income arithmetic. This is not an exhaustive security review of every application route or every possible data relationship.

### Points ledger: internally consistent in the snapshot

- 2,354 events and 2,354 ledger entries.
- Zero event/ledger balance mismatches.
- Zero missing ledger events, mismatched owners or events linked to nonexistent employees.
- Zero duplicate event keys or duplicate event revisions.
- Zero daily budget breaches under the current published policy.
- Zero nonzero awards with an unknown activity date.

This validates internal consistency, not that every historical source record was attributable or that every action route is instrumented.

### 156 source records have no valid current employee link

| Table/source | Affected rows | Finding |
| --- | ---: | --- |
| `work_tbl` | 50 | Employee ID is NULL. |
| `reports` | 77 | Referenced numeric employee ID is absent from `users_tbl.id`. |
| `mauritius_reports` | 1 | Referenced employee ID is absent. |
| `special_reports` | 1 | Referenced employee ID is absent. |
| `leave_applications` | 16 | Referenced employee ID is absent. |
| `orders` | 7 | `points_user_id` is NULL. |
| `suggestions` | 2 | Referenced employee ID is absent. |
| `suggestion_replies` | 1 | Referenced employee ID is absent. |
| `acknowledgments` | 1 | Employee ID is NULL. |

Do not match these by display name automatically. They may involve deleted users, historical identifiers or incomplete attribution. Establish ownership from original evidence; do not create replacement users merely to satisfy an audit.

Three otherwise attributable leave requests have no reliable submission timestamp. Leave is tracked with zero points under the current policy, but these still make source coverage partial.

The database has four legacy `post_reactions` records and five canonical `item_reactions` records. Legacy reactions are marked superseded pending review, not imported automatically; verify target identity and ownership before merging or declaring them excluded.

### Notifications: historical backlog

`portal_notification_state` has baseline 0 and `portal_notifications` is empty. There are **2,285 nonzero ledger entries** after that baseline. The notification worker processes up to 1,000 per invocation, so multiple successful invocations would be needed to announce all of them.

Most are historical awards imported during the first points sync. They are not new employee actions. Before enabling the live notification job, decide whether those historical awards should be announced. If only new activity should be announced, a reviewed cutoff must preserve genuine recent corrections and awards. Blindly moving the cursor to MAX(id) would also skip the -13 correction in this incident. No cursor-reset SQL is supplied or executed here.

A zero notification count in this snapshot does not establish whether the worker failed, was not run, or the export preceded its execution; inspect its output/log.

### Inventory: consistent opening balances

All 326 active-stock rows match their 326 movement records. There are no movement arithmetic mismatches in the snapshot. These are opening records; this does not validate concurrent receipt/issue handling under real use.

### Historical Mauritius cashups: 27 income inconsistencies

For 27 `mauritius_reports` rows, stored `total_income` differs from `income_cash + income_card + income_other`. Regular and special cashup income totals pass this particular comparison.

Review the original financial records and the legacy Mauritius submission/calculation path. Do not automatically overwrite stored financial totals: the differences may require reconciliation of components, totals or historical semantics. These discrepancies do not explain the employee's -13 points correction; cashup points are currently a fixed eligible submission award, not an amount conversion.

## Further work before release

1. Verify the correction patch on staging.
2. Resolve notification-history policy before resuming announcements.
3. Review the 156 ownership gaps and three undated leave records using original records.
4. Review legacy reactions before approving an explicit exclusion or migration.
5. Reconcile the 27 Mauritius cashup discrepancies and inspect that legacy writer.
6. Exercise forum, messages, stock and profile actions through their real forms, then verify source events, ledger entries, caps and notices. Empty source tables cannot prove those routes work.
7. Rerun the full release check after these findings are resolved or formally documented where appropriate. Do not treat the present partial coverage as production-ready.

## Files in this patch

| File | Responsibility |
| --- | --- |
| `dashboard/points/adjust.php` | Employee selection, Add/Remove form, validation, receipt and period-preserving redirect. |
| `dashboard/points/src/Adjustment.php` | Signed amount validation, transactional correction, retry checks, synchronized receipt totals. |
| `dashboard/points/index.php` | Explicit monthly/all-time totals and employee correction link. |
| `dashboard/points/audit.php` | Existing audit plus owner/event/revision integrity checks. |
| `dashboard/points/tests/adjustment.php` | Sign, bounds and incident-arithmetic regression checks. |
| `dashboard/bin/release-check.sh` | Includes the new files and regression checks. |
| `diagnostics.sql` | Read-only database diagnostics; no corrections or migrations. |

Keep the original database export private. It is not included in this patch.
