# WTTZap — Moving the test site to a live domain

This guide covers exporting the existing test application, importing it into a new domain, checking it, and scheduling points and notifications. Run terminal commands from the website root: the folder containing `dashboard` and the root login page.

The known test-site folder is `/home/bethelin/wttzap`. Your live folder may be different. Never type `/absolute/site/path` literally: that was a placeholder.

## 1. How often to run commands

| Task | Frequency |
| --- | --- |
| Database/module installers | During initial setup, or when an update explicitly requires them. Not daily. |
| `bash dashboard/bin/sync-participation.sh` | Schedule every 5 minutes. Also run manually during deployment and diagnosis. |
| `bash dashboard/bin/release-check.sh` | After deployment, after application updates, and when diagnosing problems. |
| Backups | Before each deployment; maintain regular database and uploaded-file backups appropriate to your activity. |

The combined sync updates participation points first, then generates points notifications. Under a healthy five-minute schedule, new eligible activity normally appears by the next successful run. It does not award unlimited points for every click: eligibility, caps, ownership and source coverage still apply.

## 2. Prepare the export

- Confirm whether test-site employee records, reports, balances and points should become live records. A full database copy includes test activity. Do not delete ledger rows or zero totals manually to make it look clean; a clean launch needs a separate, reviewed data-cleanup plan.
- Export the complete database and back up the complete matching application, including uploaded files. The earlier users-table SQL file is not a complete database.
- Include root login files such as `index.php` and `gist.php`, `dashboard`, assets, dependencies and required server configuration. Incremental update ZIPs are not a complete installation.
- Include database triggers in the export where supported. Their original DEFINER accounts may not exist on the destination server; review any import errors. The points action installer below recreates its own triggers, not every legacy trigger.
- Pause writes and background jobs while taking the final matching database/files snapshot. Prevent new test-site activity from being lost during the move.
- Keep database exports and backups outside the publicly accessible website folder.

## 3. Prepare the live domain

In the hosting control panel:

1. Create the live domain and identify its document-root folder.
2. Enable HTTPS and select PHP 8.1 or newer. Confirm the web PHP and terminal PHP versions are compatible.
3. Create a separate live database and database user. Grant the permissions required by the application and installers, including TRIGGER for points action tracking.
4. Upload and extract the full application into the document root. Keep `dashboard` directly beneath it; the application contains root-relative `/dashboard/...` links.
5. Import the complete exported database into the new database. Confirm the import completes without errors.
6. Update `dashboard/config.php` and any other database connection files, including root `db.php` if used. Ensure the live copy does not still connect to the test database.
7. Review domain-specific links, email/SMTP settings, reset links and integrations. Keep test jobs and test email delivery isolated from production.
8. Confirm uploaded-file directories are writable by the application without making the whole website world-writable. Turn off public debug/error display and retain server error logging.

The steps above happen before the module commands. Those commands extend an existing portal database; they cannot build the entire legacy application from an empty database.

## 4. Open the correct terminal folder

This prompts you for the actual live folder instead of asking you to edit a placeholder. Run it in Bash:

```bash
read -r -p "Full path to the LIVE website folder: " portal_root
cd -- "$portal_root"
```

If `cd` fails, stop and correct the path. Then check:

```bash
pwd
ls dashboard/bin/sync-participation.sh dashboard/bin/release-check.sh
php -v
command -v php
```

Save the PHP executable path: cron may have a different PATH from your interactive terminal.

## 5. Initialize or verify the imported modules

Run the following commands one at a time. Stop on any error; do not continue to the next step until it is resolved. Take the backup first, because installers can change schema and triggers.

### A. Points and action tracking

```bash
php dashboard/points/install.php --with-actions
```

This installs the points schema and action adapters. If TRIGGER permission is denied, ask the host to resolve the permission issue; omitting action tracking leaves participation coverage incomplete.

### B. Inventory

```bash
php dashboard/inventory/install.php
```

Existing stock tables must already exist and use InnoDB. The installer preserves quantities and skips opening records already represented in inventory history.

### C. Cashup reliability

```bash
php dashboard/reports/install-reliability.php
```

The base report tables must already exist and use InnoDB. This adds request tracking and archive records; it does not create the entire cashup schema.

### D. Initial participation sync

```bash
php dashboard/points/sync.php
```

Resolve missing sources, ownership problems or incomplete coverage before proceeding. Do not silently interpret missing activity as zero.

### E. Notifications

```bash
php dashboard/notifications/install.php
```

On a database where notifications have never been installed, running the first points sync BEFORE this installer establishes a baseline that excludes historical awards from new notifications.

On a copied database with notifications already installed, the existing baseline and notification history are preserved. Rerunning this installer does not reset them. If the test site initialized notifications before its first points sync, importing it preserves that earlier baseline too; this sequence does not retroactively suppress pending historical notices.

### F. Run the combined job and checks

```bash
bash dashboard/bin/sync-participation.sh
```

```bash
bash dashboard/bin/release-check.sh
```

Do not open the site to staff while the release check reports BLOCKED. Passing automated checks is also not a complete security or production certification; perform the checks below.

## 6. Schedule points and notifications

Create ONE scheduled job for the live installation. Start with every five minutes:

```text
*/5 * * * *
```

In cPanel, this is the schedule: minute `*/5`, hour `*`, day `*`, month `*`, weekday `*`. Put the command in the command field separately.

For the CURRENT TEST SITE only, the command is:

```bash
cd /home/bethelin/wttzap && /bin/bash dashboard/bin/sync-participation.sh >> /home/bethelin/logs/wttzap-participation.log 2>&1
```

For the LIVE SITE, use its actual folder and a writable log path outside its public document root. Do not copy the test path unchanged unless it is truly the live location.

The following optional Bash commands print a live cron command using paths you enter and the current terminal PHP location. They do not install a cron job:

```bash
read -r -p "Full path to LIVE website folder: " portal_root
read -r -p "Full log file path outside the public website: " portal_log
portal_php="$(command -v php)"
printf 'cd %q && PATH=%q /bin/bash dashboard/bin/sync-participation.sh >> %q 2>&1\n' "$portal_root" "$(dirname -- "$portal_php"):/usr/local/bin:/usr/bin:/bin" "$portal_log"
```

Confirm `portal_php` is a real PHP executable path and the log's parent directory exists. Copy the printed command into cPanel. For ordinary hosting paths without spaces or special characters, this produces a standard shell command. If your paths contain unusual characters, have the command reviewed for the cron shell before saving it.

After the first scheduled run, inspect the log (enter your chosen log path):

```bash
read -r -p "Full path to the participation log: " portal_log
tail -n 80 -- "$portal_log"
```

If cron says `php: command not found`, use the PATH-aware command above or change the two PHP calls in the sync wrapper to the verified absolute executable path. Check its version as well.

Do not schedule the individual points and notification scripts as additional jobs if the combined wrapper is already scheduled. Disable the old test-site job when retiring that site. A retained test environment must use a separate database and isolated delivery settings.

Review log growth and arrange rotation. If sync duration approaches five minutes, investigate workload and coverage before changing the interval. The points audit currently expects a successful sync less than one hour old.

## 7. Check the employee experience before opening access

Use designated test accounts and track any test transactions so they can be reconciled properly.

- Sign in and sign out; test permitted roles and forbidden pages. Check password changes and password reset with letters, numbers, symbols and spaces.
- Open My Profile and My Work Report; save a draft, submit a report and check attachments.
- Submit a cashup and verify totals and history. Check duplicate submission handling and report access restrictions.
- Receive and issue stock; check movement history and balances. Verify insufficient-stock and duplicate-request handling.
- Create a forum topic and reply; exercise public and private messages. Confirm another user cannot read private conversations they do not belong to.
- Perform an eligible points action, run or wait for sync, and verify the points entry and its owner. Check that the notification links to the points page.
- Run sync again and confirm the same activity does not earn points or notifications twice.
- Check mobile navigation and the light, dark, morning and afternoon themes.
- Verify email delivery and links use the live domain.
- Test backup restoration in an isolated environment.

The last supplied server output passed syntax, environment and several policy/arithmetic tests, but participation coverage was unverified and the notification audit was unavailable. Those issues are not confirmed resolved until the corrected sync and release checks pass.

## 8. Troubleshooting

| Symptom | What to check |
| --- | --- |
| No such file or directory | Run `pwd`; confirm the complete app is uploaded and the path points to the folder containing `dashboard`. |
| PHP missing or wrong version | Compare `command -v php` and `php -v` with the cron executable and web PHP version. |
| Database connection fails | Check live credentials, hostname and database-user permissions in every active connection file. |
| Unknown column or missing table | Check the complete database import and required module installation. Do not add guessed columns or keep rerunning SQL blindly. |
| Points say “Run sync first” | Run the combined sync from the correct root and inspect its errors. Confirm cron actually runs. |
| An activity does not earn points | Inspect points source coverage, ownership, eligibility, caps and action-trigger installation. |
| Notification audit unavailable | Check the notification schema and server PHP log. Successful installation alone does not prove the audit works. |
| Notifications lag behind points | Check the combined job output. Its points step must succeed before the notification step runs. |
| Inventory balance mismatch | Stop writes to the affected item and investigate out-of-band changes. Do not erase ledger history or assume a stocktake repairs an existing mismatch. |
| Blank page or HTTP 500 | Inspect the server PHP error log; keep detailed errors away from public pages. |
| Styling or links broken | Confirm full assets were deployed, HTTPS works, `/dashboard` is at the expected location and no old-domain links remain. |
| Release check is BLOCKED | Read the first failing check and its corresponding module logs. Fix it, rerun sync where relevant, then rerun the checks. |

Useful commands from the website root:

```bash
php dashboard/points/audit.php
php dashboard/inventory/audit.php
php dashboard/notifications/audit.php
bash dashboard/bin/release-check.sh
```

## 9. Cutover and rollback

Only switch staff to the live domain after the checks pass. Keep the old site read-only or unavailable for writes during cutover so records do not split between databases.

If deployment fails before live users have created data, keep access closed and restore the matching backed-up application, database and uploads. Recheck configuration before restarting the correct scheduled job.

If live transactions already exist, pause writes and scheduled jobs, preserve a backup of that state, and reconcile those transactions before any restore. Restoring the old database outright would discard new cashups, stock movements, messages and points. Never roll back only balances or only ledger tables.

## 10. Developer reference

| File or area | Responsibility |
| --- | --- |
| Root `index.php`, `gist.php` | Login page and existing authentication handler; review both during a move. |
| `dashboard/config.php` | Main database connection configuration; other legacy connections may exist. |
| `dashboard/points/install.php` | Points schema and optional action triggers. |
| `dashboard/points/sync.php` | Converts eligible participation sources into ledger changes. |
| `dashboard/points/audit.php` | Points consistency, source coverage and sync freshness checks. |
| `dashboard/inventory/install.php` | Inventory support tables and opening records. |
| `dashboard/inventory/audit.php` | Inventory ledger and balance checks. |
| `dashboard/reports/install-reliability.php` | Cashup request and archive tracking tables. |
| `dashboard/notifications/install.php` | Notification tables and first-install baseline. |
| `dashboard/notifications/sync.php` | Generates notifications from points ledger changes. |
| `dashboard/notifications/audit.php` | Notification coverage and integrity checks. |
| `dashboard/bin/sync-participation.sh` | Scheduled points-then-notifications wrapper. |
| `dashboard/bin/release-check.sh` | Selected PHP lint, environment, policy and audit checks. |
| Module `README.md` files | Module-specific setup, limitations and troubleshooting. |

### Fresh database instead of moving the existing test database

A fresh installation additionally requires the complete base schema and reviewed seed data, including an administrator account. The available module installers do not supply that complete bootstrap.

If the new forum tables are absent, import `dashboard/forums/database/install.sql` after the base schema and before points setup. Do not run the forum uninstall SQL. Then follow section 5 in order. Do not import only a users-table dump and expect all modules to work.

The instructions in this README do not execute a deployment, change DNS, install cron jobs or certify the application as production ready.
