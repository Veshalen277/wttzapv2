# Pre-test review

This is a source-code review and compatibility patch, not a certification of live scoring. PHP/MySQL and browser rendering were unavailable here. JavaScript appearance tests passed.

## Corrections included

- Item reactions now resolve employee-wall posts against messages, the table used by that wall; legacy post reactions retain their posts mapping.
- Previously ineligible zero awards can be credited when ownership becomes verifiable; newly ineligible awards receive a reversal entry. Original daily budget reservations remain to prevent remove/restore farming.
- Profile edits now track name and emergency-contact changes when optional action triggers are reinstalled.
- My Profile now has an actual password-change handler, CSRF-protected prepared contact updates, escaped output, and validated photo uploads.
- Profile leave totals no longer claim to be an available balance using the old calendar-day calculation. Only the recorded accrued value is shown.
- Added Light, Dark, Morning (warm cream), Afternoon (soft green), and System appearance choices. Morning/Afternoon are manual palettes, not scheduled modes.

## Password blocker

The supplied dashboard has no numeric-only restriction in its profile password inputs. Its password form previously had no processing handler. The active /index.php sign-in code is outside the supplied archive. Supply that file and any JavaScript it loads to finish the login restriction fix. Do not assume end-to-end login is repaired yet.

Password changes preserve exact text, including spaces, punctuation and Unicode; new passwords accept 8–72 bytes. Existing SHA-1 accounts remain SHA-1 for compatibility with the unseen login handler. Other supported password_hash accounts use password_hash. This is a temporary compatibility measure; replacing SHA-1 requires a coordinated login upgrade. The separate dashboard/login.php uses a different users table and was not substituted for your main login.

## Activity mapping inventory

Every configured source references an existing scoring rule. Each row below STILL requires a live source, actor, timestamp, cap and ledger test. Missing/uninstrumented sources must not be described as working.

| Source | Table | Points/action | Daily action cap | Minimum text length |
|---|---|---:|---:|---:|
| stock_change | portal_points_actions | 2 | 10 | 0 |
| profile_update | portal_points_actions | 1 | 1 | 0 |
| project_update | portal_points_actions | 2 | 5 | 0 |
| file_upload | portal_points_actions | 1 | 5 | 0 |
| work | work_tbl | 10 | 1 | 10 |
| cashup | reports | 10 | 1 | 0 |
| mru_cashup | mauritius_reports | 10 | 1 | 0 |
| special_cashup | special_reports | 10 | 1 | 0 |
| manager | manager_report_tbl | 8 | 1 | 20 |
| login | user_logins | 1 | 1 | 0 |
| login_archive | user_logins_archive | 1 | 1 | 0 |
| messages | messages | 3 | 5 | 20 |
| replies | replies | 2 | 10 | 10 |
| forum_topics | portal_forum_topics | 5 | 3 | 20 |
| forum_posts | portal_forum_posts | 2 | 10 | 10 |
| wall | posts | 3 | 5 | 20 |
| item_reactions | item_reactions | 1 | 5 | 0 |
| post_reactions | post_reactions | 1 | 5 | 0 |
| private | private_messages | 1 | 5 | 10 |
| group_messages | group_messages | 2 | 10 | 10 |
| groups | groups | 2 | 1 | 0 |
| projects | projects_tbl | 5 | 2 | 0 |
| project_messages | project_messages | 2 | 10 | 10 |
| suggestions | suggestions | 4 | 2 | 20 |
| suggestion_replies | suggestion_replies | 2 | 5 | 10 |
| bulletins | bulletins | 3 | 2 | 20 |
| bulletin_reads | bulletin_reads | 1 | 5 | 0 |
| acknowledgments | acknowledgments | 1 | 5 | 0 |
| checklist_items | checklist_items | 1 | 5 | 5 |
| checklist_completed | checklist_items | 3 | 10 | 0 |
| orders | orders | 5 | 3 | 0 |
| leave | leave_applications | 0 | 0 | 0 |
| policies | policies | 2 | 2 | 0 |
| assignments | adv_user_tasks_tbl | 0 | 0 | 0 |
| group_members | group_members | 0 | 0 | 0 |

## Live testing sequence

1. Back up database and matching application files. Apply this patch to staging first. It assumes the previous core, forum and points patches are installed.
2. Run `php dashboard/points/install.php --with-actions` to refresh the changed profile trigger. This command modifies schema/triggers; do not run it without the backup.
3. Run `php dashboard/tests/passwords.php` and `php dashboard/points/tests/policy.php`. The ledger test requires a disposable test database as documented in that file. These PHP tests were supplied but not run here.
4. Run `php dashboard/points/sync.php`, then `php dashboard/points/audit.php`. Audit is read-only and returns nonzero for missing/stale/problem sources or ledger inconsistencies. Record intentionally unused modules as exclusions.
5. For each enabled source: act as user A, sync, check A’s ledger and rule points; sync twice more and confirm no duplicate award; hit the daily cap; repeat as user B. Verify recorded timestamp and day boundary in South African time.
6. Test own-post reactions (zero), another user’s post (one), removal (reversal), restoration (same original award), forum reply deletion, checklist uncheck/recheck, and missing tables (explicit coverage issue, no destructive reconciliation).
7. Test profile edits and photo updates with refreshed triggers. Routes using separate DB connections without the authenticated actor variable cannot earn trigger-based points; inspect their coverage and instrument each affected handler before claiming full capture.
8. Test login and password changes with spaces, apostrophes, Unicode, and symbols AFTER providing/updating the real login handler. Verify old-password rejection, wrong-current-password rejection, CSRF rejection, and a second concurrent password change.
9. Inspect common pages on mobile and desktop in all four themes; test saved preferences, switching accounts, and System mode. Custom inline legacy styles may still need adjustments.

## Remaining scoring limitations

- Points are reconciled by scheduled sync, not instant. Ensure cron runs and monitor the latest successful sync.
- Historical missing dates/owners cannot be reconstructed safely. They remain unscored with coverage warnings.
- Daily caps are shared across sources using the same rule. Cashup variants share one cap; login/archive share the login cap. Archived login representations can appear twice as events but the daily cap prevents a second point. A future stable cross-table login identity would improve tracking clarity.
- Zero-point events caused by caps stay zero. Editing a short post into a longer one does not automatically earn a new award.
- Rule changes apply to new awards; no bulk historical repricing is included.
- Forum archival is not deletion and does not revoke a topic award; soft-deleted replies do revoke.
- Actor logging is connection-local. No claim is made that every legacy endpoint is instrumented until tested on staging.

## Recommended next additions

1. Add a sync-health banner and administrator alerts for stale or partial scoring runs.
2. Add a points appeal workflow linking an employee’s disputed ledger entry to its original activity.
3. Add moderator approval or helpful-answer awards, with duplicate-content and rapid-posting controls, to reward quality instead of volume.
4. Add separate role/team leaderboards so employees with different access and responsibilities can compete fairly.
5. Add versioned scoring rules, a policy-change preview, and documented backfill decisions.
6. Add badges and personal milestones after the ledger passes the source-by-source tests; avoid tying compensation to unverified participation totals.
