# Participation points rebuild — deploy in order

This patch replaces the count-based score with an auditable participation ledger, published rules, personal tracking, manager rankings, source coverage, and administrator corrections. It requires the previously supplied developer refactor. The new forum is detected independently; no forum files are replaced.

1. Back up the existing files and test on a copy of the live database.
2. Copy points/ and modules/participation/ into dashboard/. Keep the existing database config.
3. On the server, run:

       php /home/bethelin/wttzap/dashboard/points/install.php --with-actions

   This creates five points tables, adds nullable order-author/checklist-completion metadata, and installs transactional action triggers where supported. TRIGGER permission is required for --with-actions. Without that permission you can install the base ledger without the flag, but inventory/profile/project action coverage will remain incomplete and be shown as such.
4. Deploy the included app/bootstrap.php, order form, checklist and statistics page replacements together. The order and checklist changes require the metadata columns from step 3.
5. Run:

       php /home/bethelin/wttzap/dashboard/points/sync.php

6. Sign in as role 2, 5 or 7 and inspect Points → Coverage. Resolve live-schema discrepancies. The historical database available in this conversation included only users_tbl, so the importer explicitly checks other tables rather than guessing.
7. Schedule sync.php in the hosting control panel, typically every five minutes. The package does not install cron or change the live database by itself.

Every new award has a stable activity ID and reason. Repeated syncs do not duplicate awards. Source removals create separate reversals where configured. Missing identity/date information stays flagged instead of becoming invented points. This-month and all-time scores use the ledger; the old count/streak formula is no longer used by the statistics routes.

Read dashboard/points/README.md for the full rule table, limitations, live-source diagnostics, trigger rollback, permissions and test instructions. It also explains the replacement of the old general statistics pages, source priority during historical import, and which actions are tracked at zero points.

Verification performed here: 34 static prepared-query contracts, 35 source/rule mappings, existing JavaScript regression suites, and package integrity. PHP runtime/lint, database triggers, migrations, live awards and browser rendering have NOT been executed/tested here. Run the supplied PHP policy and disposable-database ledger tests, then verify source records and totals on staging before publishing the leaderboard.

Included existing business files are based on the supplied archive. If those files have been independently customized on the server, merge the documented changes into that current version rather than blindly replacing custom work.

Files included:
- dashboard/points/README.md
- dashboard/points/leaderboard.php
- dashboard/points/coverage.php
- dashboard/points/bootstrap.php
- dashboard/points/autoload.php
- dashboard/points/index.php
- dashboard/points/rules-view.php
- dashboard/points/sync.php
- dashboard/points/adjust.php
- dashboard/points/install.php
- dashboard/points/sources.php
- dashboard/points/rules.php
- dashboard/points/assets/points.css
- dashboard/points/src/Sync.php
- dashboard/points/src/Triggers.php
- dashboard/points/src/Store.php
- dashboard/points/src/Policy.php
- dashboard/points/database/install.sql
- dashboard/points/tests/policy.php
- dashboard/points/tests/ledger.php
- dashboard/modules/participation/index.php
- dashboard/modules/participation/module.php
- dashboard/modules/participation/rules.php
- dashboard/app/bootstrap.php
- dashboard/orders/orderForm.php
- dashboard/users/checklist.php
- dashboard/statistics/rankings.php
- dashboard/statistics/all-stats.php
- dashboard/statistics/user-stats.php
