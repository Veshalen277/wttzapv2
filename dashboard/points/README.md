# Audited participation points

## What changed

The old overall score combined live table counts with streak multipliers. Some counts were date-filtered while checklist completion was all-time, and the new forum was absent. There was no award ledger to explain a total or detect a replay. The new system stores one stable activity identity and an append-only award/correction history. Personal points and manager rankings use that same ledger.

This patch requires the previously supplied developer refactor. It leaves the forum independent: points reads its dedicated tables but does not alter forum code or add a points dependency to it.

## Install in this order

1. Back up the included existing files and database. Test on a copy of the live schema first.
2. Deploy points/ and modules/participation/. With the existing config.php pointing at the intended database, run:

       php /home/bethelin/wttzap/dashboard/points/install.php --with-actions

   This creates five points tables and adds nullable orders.points_user_id and checklist_items.points_completed_at where those source tables exist. It does not infer historical authors from full names or set fabricated historical completion dates. DDL is not atomic in MariaDB; if installation is interrupted, correct the error and rerun the idempotent installer.
3. Deploy the included orders/orderForm.php, users/checklist.php and three statistics page replacements. The order/checklist changes REQUIRE step 2 first. The checklist is rebuilt because the supplied file contains malformed PHP delimiters and mixes multiple implementations. It now enforces ownership and CSRF on mutations and records first completion. Order creation records the authenticated user ID and checks CSRF.
4. Run the initial reconciliation:

       php /home/bethelin/wttzap/dashboard/points/sync.php

5. Review Points → Coverage as role 2, 5 or 7. Resolve missing/incompatible sources before treating the leaderboard as comprehensive. The installer does not alter unknown legacy tables to force a guessed mapping.
6. Schedule sync.php through the host's cron service, for example every five minutes using your actual PHP CLI path:

       */5 * * * * /usr/local/bin/php /home/bethelin/wttzap/dashboard/points/sync.php

   Cron has NOT been installed by this package. Use the host's actual PHP path. Large histories may need a less frequent schedule: reconciliation scans source histories, not merely an ID watermark, so it can detect removals and state changes. A database-specific named lock prevents overlapping workers. Point pages do not award on refresh or run scans in a user's request.

Check permissions, coverage, initial totals and representative source records on staging before publishing scores. Business data needs a stable identity, a numeric attributed user and an activity timestamp for an automatic dated award. The existing source's user ID is its recorded attribution; this refactor cannot prove that every older write endpoint authenticated that attribution correctly.

## Scoring defaults

Full values and minimum text lengths are in rules.php and the user-facing rules page.

| Interaction | Points | Maximum scored events/day |
| --- | ---: | ---: |
| Inventory action | 2 | 10 |
| Profile update | 1 | 1 |
| Project status/priority change | 2 | 5 |
| Project attachment upload | 1 | 5 |
| Work report | 10 | 1 |
| Cashup across normal/Mauritius/special sources | 10 | 1 combined |
| Manager report | 8 | 1 |
| Forum topic | 5 | 3 |
| Forum reply | 2 | 10 |
| Message-board / employee-wall post | 3 | 5 per type |
| Message-board reply | 2 | 10 |
| Private message sent | 1 | 5 |
| Group or project message | 2 | 10 per type |
| Project created | 5 | 2 |
| Suggestion | 4 | 2 |
| Suggestion reply | 2 | 5 |
| Bulletin published | 3 | 2 |
| Bulletin read or acknowledgment | 1 | 5 per type |
| Reaction to another person's item | 1 | 5 |
| Checklist creation | 1 | 5 |
| Checklist completion | 3 | 10 |
| Order | 5 | 3 |
| Group created | 2 | 1 |
| Policy upload | 2 | 2 |
| Daily sign-in | 1 | 1 |
| Leave request, received assignment, group membership | 0 | Tracked only |

Received assignments and memberships do not prove the recipient acted. Leave should not become a points incentive. Password changes, page reloads, autosaves, received notifications and moderation/destructive actions do not earn points. They are not presented as earned contributions. This is broader participation tracking, not a click counter or a complete security audit log.

Editing the same record does not award again. Text lengths are only minimum eligibility checks, not content-quality judgments. Reactions are keyed by actor/target, so changing reaction type or toggling repeatedly cannot create fresh credit. Self-reactions and unverified reaction targets score zero. When both legacy post_reactions and item_reactions exist, item_reactions is canonical; the legacy source is flagged for review rather than risking duplicate credit.

## Accuracy and audit semantics

- Stable source + source key + user yields one event. Re-running synchronization updates its seen marker, not its score.
- Awards are frozen at first eligible ingestion, with a rule version. Change rule values and bump version for future events; existing awards do not silently change.
- Sources run in the explicit order in sources.php and rows in activity-date/key order. Daily allocations go to the first imported eligible events for that user/rule/day. Historical backfill uses that documented order, not a claimed global ordering across tables.
- Award allocations remain reserved even if an item is removed. Deleting and reposting cannot reopen that day's budget.
- Discussion/post/reply/reaction/suggestion removals and checklist reversal produce negative ledger entries on the original activity date. Restoring the same record restores its original award only. The old entries remain visible with recorded-at timestamps.
- Work reports, cashups, login history and similar durable submission events retain awards if source rows move into archives. A missing whole table also retains recorded awards and is flagged, so uninstalling the forum does not erase historic participation.
- A source that errors or has invalid owner/key rows does not revoke missing events from that scan. The coverage screen marks it as incomplete rather than claiming its totals are exact.
- Unknown dates create zero-point, explicitly unscored records. If a trustworthy timestamp later becomes available, an audited resolution can award it. Missing user attribution is skipped and counted as a coverage gap, never matched by name.
- Timestamps default to Africa/Johannesburg. Verify the live database/application time conventions before initial import. If a source stores UTC, set `'timezone'=>'UTC'` on that source adapter. No server-wide timezone setting is silently changed.
- Future/impossible dates remain unscored. This-month filtering uses activity date; complete history separately shows when the award/correction was recorded.
- Role 7 can enter a signed adjustment with a reason (±1000 maximum per request). Its request key prevents replay, and its administrator/reason remains in the ledger. Adjustments use today's date. No delete-history or reset-total button is provided.

The legacy formula has not been imported as an opening balance because that would perpetuate its errors and double-count backfilled activity. Initial totals are rebuilt from available source evidence only.

## Source coverage and remaining data limitations

sources.php maps 35 existing/dedicated stores. It validates column names against the live schema before querying. Missing tables, required columns, invalid user IDs, unavailable dates and failed reads are surfaced. It does not guess a schema based on a filename or return silent success for an unavailable source.

The database dump supplied in this conversation contains only users_tbl. Other mappings are grounded in application SQL and guarded against live-schema differences. Some deployment-specific sources will need adjustment after reviewing Coverage. The new forum uses its known portal_forum_topics/posts schema.

Important limits:
- Historical orders that stored only fullname cannot safely be attributed automatically. New orders carry points_user_id. Do not backfill it by matching names without verified identities.
- Historical checked items lack completion times. They remain unscored until a real completion timestamp is recorded; creation date is not used as completion date.
- Older records already deleted before installation cannot be reconstructed. Private-message deletion between scheduled scans also cannot be observed retroactively.
- Historical inventory/profile/project-status changes cannot be reconstructed from overwritten rows. The --with-actions installation adds database triggers for new stock changes, profile changes, project status/priority changes and project-file inserts where the confirmed columns exist. Those operations write an actor/time action record atomically with the business mutation, even in older stock handlers. Shared app/bootstrap.php sets the connection-local authenticated actor. Endpoints bypassing that bootstrap must set or clear the actor explicitly; never use persistent connections that retain an actor across unguarded requests. Events without a known actor are not guessed. Coverage reports missing trigger adapters. TRIGGER privileges are required; install without the flag leaves those sources explicitly incomplete. Additional unrecorded workflows need the same durable actor/time pattern.
- Legacy endpoints outside the included order/checklist changes still need a separate authorization/CSRF audit. The ledger cannot repair forged historical attribution.
- The points interface contains no private message bodies or forum text, only action labels/source IDs. Regular users see their own ledger. Roles 2, 5 and 7 may inspect all users and the leaderboard; only role 7 can adjust.

## Transactional action adapters

--with-actions creates namespaced triggers on available stock, user-profile, project and project-file tables. It does not touch forum tables. A trigger writes portal_points_actions within the same statement/transaction as the source mutation, so rollback cannot leave a scored action behind. A user/target/day unique key limits repeated updates to the same entity to one action per day. Source changes that leave tracked fields unchanged do not create actions. Only the authenticated connection-local actor is recorded, not the user whose profile was edited.

Inspect skipped-adapter notices during installation. The metadata detector verifies trigger presence but cannot prove every legacy endpoint initializes the actor. The supplied bootstrap does so on each authenticated page request. Verify the active endpoints on staging. When dropping the points action table, first drop its portal_points_* triggers; leaving triggers pointing at a missing table would break affected writes. Retaining the points tables and disabling the scheduler/UI is the simplest rollback. The app/bootstrap.php change adds only the optional connection-local actor marker and works without any trigger.

## Existing pages affected

statistics/rankings.php and all-stats.php now show the ledger leaderboard. user-stats.php shows the selected user's ledger. This replaces the unreliable general statistics screen; the original income/report business pages remain available elsewhere. The old stats-helpers.php remains on disk but these routes no longer call its score formula. The Points module adds My points and Scoring rules for roles 0–7.

## Verification

Checks run here: prepared-query placeholder/type consistency for 34 static statements, existing navigation regression tests, and draft-client regression tests. PHP runtime/lint and live MariaDB integration were unavailable; no production points have been changed or claimed verified.

On a development host:

    php points/tests/policy.php

For ledger integration, create an empty disposable database ending _test or _testing and import database/install.sql there. Set POINTS_TEST_HOST, POINTS_TEST_USER, POINTS_TEST_PASSWORD, POINTS_TEST_DATABASE, then run:

    php points/tests/ledger.php

The ledger test covers duplicate observations, daily cap enforcement, reversal, restoration and timestamp resolution; it rolls back its fixtures. Lint all changed PHP files. On staging, run sync twice and compare totals, test reply removal/restoration, test private access to other users' ledgers, inspect old null-date rows, create an order, and complete/reopen/re-complete a checklist item. Validate coverage against actual source counts before relying on the leaderboard.

## Extending and rollback

Add a published rule in rules.php, then an explicit source mapping in sources.php. Prefer immutable source IDs and timestamps. Never expose a browser endpoint that accepts an arbitrary claimed event or point value. If new source fields are required, add a versioned, reviewed migration. Keep the points tables when removing a feature whose history you want to preserve.

Rollback by restoring the backed-up business/statistics files and disabling modules/participation/module.php. Stop the cron job. Keep points data for audit. The nullable metadata columns do not need to be dropped to restore prior code. No uninstall/reset SQL is included because the award history should be preserved deliberately.
