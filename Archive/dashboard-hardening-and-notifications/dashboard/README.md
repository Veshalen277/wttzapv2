# WTTZap employee portal — maintainer guide

This guide covers the application and refactors developed from the supplied dashboard archive. It is a map and operational guide, not a claim that every legacy file has been executed or every live database column verified. Deploy patches to staging, keep backups, and finish the acceptance checks before production use.

## Start here

- Main employee entry: `/dashboard/employee/emp_profile.php`.
- Profile/account management: `/dashboard/employee/my_profile.php`.
- Inventory: `/dashboard/inventory/index.php`. Read `inventory/README.md` before deploying stock route replacements.
- Forum: `/dashboard/forums/view_topics.php`.
- Points: `/dashboard/points/index.php`; administrators should inspect Coverage.
- Website-root login: `/index.php` includes `/gist.php`. The root login redesign was delivered separately; `gist.php` has not been supplied or audited.

## Application map

| Files / directory | Responsibility |
|---|---|
| `app/autoload.php` | Loads the Portal classes without changing Composer |
| `app/bootstrap.php` | Authentication, database connection, environment/error settings, authenticated points actor |
| `app/Auth.php`, `app/User.php` | Session adaptation and server-side role checks |
| `app/Connection.php`, `config.php` | Existing mysqli configuration and connection aliases; keep credentials private |
| `app/Csrf.php` | Token creation and validation for refactored POST forms |
| `app/Flash.php`, `messages.php`, `views/flash.php` | One-time escaped user feedback |
| `app/Passwords.php` | Exact-text password verification and compatible replacement; legacy SHA-1 is temporary |
| `app/Html.php` | Escaping, versioned assets and scoped templates |
| `app/Menu.php`, `menu_config.php` | Role-based sections, submenu definitions and active page selection |
| `app/Modules.php`, `modules/*/module.php` | Optional module registration and visibility |
| `header.php`, `footer.php` | Shared page shell and assets |
| `navigation.php`, `views/navigation.php`, `views/submenu.php` | Header, contextual sidebar, search, account and appearance controls |
| `css/navigation.css`, `css/portal-theme.css` | Shared layout and legacy content presentation |
| `css/color-modes.css`, `js/theme.js` | Light, Dark, Morning, Afternoon and System appearance; browser/account preference |
| `js/navigation.js`, `js/portal.js` | Sidebar/search behavior and shared collapse indicators |
| `employee/emp_profile.php` | Work-report entry and catchable-error notice |
| `employee/pages/emp_profile.bootstrap.php` | Employee data and legacy mail helpers |
| `employee/pages/emp_profile.view.php` | Work editor, responsibilities, profile and optional widgets |
| `employee/pages/emp_profile.submit.php` | Legacy report submission, attachments, mail and draft actions |
| `employee/support/PageSections.php` | Isolates optional widget failures and logs error references |
| `employee/assets/work-report.*` | Work-report presentation and word count |
| `employee/my_profile.php`, `employee/assets/profile.css` | Profile forms, password changes and professional profile layout |
| `employee/upload.php` | Authenticated, CSRF-protected profile photo validation/storage |
| `employee/employee_wall.php`, `employee/reactions_helper.php` | Existing employee feed and reaction queries |
| `api/work-draft.php`, `app/Repositories/WorkDraft.php`, `js/api/drafts.mjs` | Standalone opt-in draft API/client; verify which draft path a given view actually uses |
| `bulletins/` | Notices, read tracking and widget; widget is scoped in work-report view |
| `forums/` | Independent forum controllers, views, role policy, repository and schema |
| `messages/` | Legacy messageboard, replies and private/group messaging |
| `points/rules.php` | Scores, daily caps, minimum text lengths and policy version |
| `points/sources.php` | Activity tables, actor/date/identity mapping and reversibility |
| `points/src/Policy.php` | Eligibility, timestamps and daily award rules |
| `points/src/Store.php` | Event identities, ledger entries, corrections and restorations |
| `points/src/Sync.php`, `points/sync.php` | Scheduled reconciliation and per-source status |
| `points/src/Triggers.php`, `points/install.php` | Optional mutation adapters and schema installation |
| `points/audit.php`, `points/coverage.php` | Ledger consistency and data capture health |
| `points/index.php`, `leaderboard.php`, `rules-view.php` | Employee points ledger, rankings and rules (all under `points/`) |
| `statistics/` | Legacy analytics and wrappers to the new points screens |
| `inventory/` | Transactional active Stock module; its README has a full file map |
| `stock/sc_*.php`, `stock/upload.php`, `stock/process_stock.php` | Retired stock entrypoints retained as guarded redirects / POST rejection |
| Other `stock/` files, `fs/` | Separate legacy reports/datasets; not silently migrated or merged |
| `orders/` | Ordering workflows and submitted-order attribution |
| `reports/`, `admin/` | Cashups, manager reports and administrative workflows |
| `users/` | Employee administration, checklists, login history and role-specific tools |
| `suggestions/` | Employee suggestions and replies |
| `tests/`, `points/tests/`, `bin/` | Checks, scaffolding, environment and route utilities |
| `vendor/`, `fpdf/`, third-party JS | External dependencies; do not hand-edit bundled code |

## Session and database conventions

The legacy session stores `u_data`: name at index 0, designation 1, department 2, responsibilities 3, role 4, numeric database user ID 5. `Portal\User` adapts it. Never guess numeric user IDs from display names. Use authenticated IDs for attribution, not hidden form fields.

Use prepared statements for values, explicit role checks, CSRF protection and transactions for multi-row writes. Escape at HTML output, not before database storage. Do not include presentation headers in JSON APIs. Do not make deletes or other mutations happen on GET. Never strip punctuation from password fields or store plaintext passwords in sessions/logs.

Existing credentials are embedded in some legacy files. Do not publish archives or screenshots containing configuration, SMTP passwords, uploaded private documents, or database dumps. Move secrets to deployment-managed configuration as a dedicated follow-up.

## Setup and patch order

The current working tree contains cumulative changes, but each delivered ZIP is a focused patch. Preserve server-only modifications when merging. The logical prerequisites are shared developer core → forum/points migrations → profile/work-report/theme updates → inventory migration and route cutover. The website-root login package installs beside `gist.php`, not inside dashboard.

PHP must support the application's language features (PHP 7.4 or newer syntax is used; choose a maintained release for deployment), mysqli with mysqlnd for `get_result`, mbstring for points text length, fileinfo for uploads, sessions, and the existing Composer mail dependencies. Inventory requires InnoDB. Points installers may require trigger permissions. CLI and web PHP can have different versions/extensions; check both.

Do not run every SQL file in the archive. Use only the installer for the module being deployed. Record which patches/migrations were installed, their date, and backup location. The runtime environment used to build these patches had no PHP/MySQL, so PHP execution and integration validation remain deployment gates.

## Points operations

1. Review `points/README.md`, `rules.php`, and `sources.php`.
2. Install via `php dashboard/points/install.php --with-actions` only after backing up. Rerun when an explicitly changed trigger definition needs deployment.
3. Configure hosting cron to run `php /absolute/website/path/dashboard/points/sync.php`, for example every five minutes. Confirm the CLI PHP binary and environment are correct. Log output somewhere private outside public web folders.
4. Run `php dashboard/points/audit.php`. Nonzero status means an inconsistency, stale sync or source requiring review; document deliberately disabled modules instead of calling all sources healthy.
5. Test one action per enabled source, repeat sync to prove no double award, hit caps, test self-reactions and removals, and check user attribution and South African calendar-day boundaries.

Points are scheduled, not instant. Historical missing ownership/timestamps remain unscored. Source disappearance retains earlier awards with a warning instead of destructively zeroing totals. Inventory uses the existing stock action trigger; do not also add a movement source without a duplicate-award migration. Changes to rules affect new events; historical repricing needs a reviewed migration.

## Common troubleshooting

| Symptom | First checks |
|---|---|
| Blank work-report page | Confirm work-report patch files match. Look up the displayed `work-...` reference in the PHP error log. Optional bulletins/feed should not block the editor. |
| Unknown column | Compare live schema with the specific module's query/migration. Do not add guessed columns. `work_draft_updated_at` was not in the supplied users schema. |
| Password accepts only numbers | Inspect root `gist.php` and any root-page JavaScript. It has not been supplied. New profile/login UI does not impose numeric-only filtering; backend verification is still required. |
| Password change succeeds but login fails | Check SHA-1/password_hash compatibility in the real sign-in handler before changing hash formats. Never “fix” this by storing plaintext. |
| Draft says failed | Check response status, expired login/CSRF, users_tbl.work_draft and server logs. The legacy form and opt-in API are different implementations. |
| Missing points | Check source mapping, actor/date fields, installed action trigger, sync schedule and Coverage status. User activity is not proven by a nonempty leaderboard. |
| Forum unavailable | Verify forum schema and configured roles. Its repository must remain independent of the points module. |
| Inventory unavailable | Follow `inventory/README.md`; check schema prerequisites, obsolete POST forms and movement audit. |
| Dark mode inconsistent | Check `theme.js`, `color-modes.css`, account browser storage and custom inline legacy styles. Inspect missing asset responses; avoid broad color-inversion filters. |
| Session/redirect problems | Confirm HTTPS/cookie configuration and no output before redirects; check which header/bootstrap a legacy route loads. |
| Upload fails | Check PHP upload limits, target directory permissions, allowed MIME/size and PHP logs. Do not grant world-writable permissions indiscriminately. |
| HTTP 500 / mail failure | Use server logs, exact request time and user/action details. Never ask users to share credentials or full unredacted config. |

On staging, `PORTAL_DEBUG=1` enables shared-bootstrap display errors; keep production display errors off. Some untouched legacy scripts set their own error behavior. Share redacted error messages and matching filenames when requesting support.

## Acceptance and rollback

Use nonproduction test accounts with realistic roles. Test authorization, CSRF, duplicate submissions, concurrent writes, failure rollback, empty data and missing optional modules—not just visual appearance. Check mobile and all themes. Email-producing work-report handlers should use test recipients in staging.

For code rollback, restore the matching backed-up files. Once new business transactions exist, do not restore a stale database independently of code. Keep audit ledgers, uploaded files and migration records; reconcile carefully before any destructive rollback. Inventory has its own rollback notes.

## Developing further

Use `bin/create-module.php` and `docs/DEVELOPMENT.md` for optional modules. Keep policies and persistence separate from templates. Add new scoring adapters to `points/sources.php` only when actor, stable event identity, timestamp and reversal rules are explicit. Keep module migrations separate and idempotent.

Suggested next work: stocktake approval roles; filtered/exportable movement reports; purchase-order/receipt matching; per-location stock; reliable actor logging on every remaining legacy writer; points sync alerts and appeals; coordinated modern password migration once `gist.php` is available. Add these incrementally after the transaction and scoring acceptance tests pass.

## Production hardening and notifications

Read PRODUCTION-READINESS.md before release. It describes the current blocked release status, cashup identity/precision/retry fixes, audited report archiving, private-conversation guards, and role-7 Operations checks. See notifications/README.md for the points-earned inbox, unread bell, install baseline and scheduled delivery. These additions require their documented schema installers before route cutover.
