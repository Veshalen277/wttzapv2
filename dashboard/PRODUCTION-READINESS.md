# Production readiness — release status: BLOCKED

This package fixes specific defects and adds operational features. It does not certify the entire portal. PHP CLI, MySQL and browser testing were unavailable here; the release script correctly returns BLOCKED rather than claiming checks passed. No production deployment, migration, email or points job was executed.

## Included changes

### Cashup integrity

- Identity and department come from the authenticated database user, not hidden form fields. Department text is no longer cast to an integer.
- Non-negative amounts are validated and calculated in integer cents before being passed to SQL as decimal strings. Invalid dates, excess precision and malformed sector rows are rejected.
- Manager form and server permissions agree on roles 2 and 7. Daily submissions allow roles 1–7.
- Server-side sector totals follow the sector-total option; the browser is not the authority.
- Persistent request keys bind user, report kind and normalized payload. A replay of an already saved submission returns an already-saved response. A reused key with changed data is rejected.
- Report, sector and request rows commit together. This depends on InnoDB; the installer refuses incompatible existing cashup tables.
- Email runs after commit. Delivery failure tells the user the report is saved and not to resubmit. There is not yet a durable retry outbox.

### Report access and retention

Report history, dashboard data and exports now require roles 2, 5, 6 or 7, matching existing income-report menu access. These roles still have cross-department visibility; organization-specific row-level restrictions require a confirmed access matrix.

Role 7 can archive a report with CSRF protection and a reason, instead of permanently deleting it. Archive actor, timestamp, prior status and reason are retained. Restore is not yet exposed in the UI. The points policy retains cashup awards when a report is archived.

History output is escaped. Dashboard totals use saved totals, including other income. CSV department cells receive basic formula-prefix neutralization. Export permissions now precede database access, including PDF's optional dependency. Test exported content with your spreadsheet software and the configured PDF library.

### Private conversations

The supplied private-message reader and reply endpoint now require the current employee to be sender or recipient. Numeric IDs are normalized; replies target the other participant and require CSRF. This closes a concrete cross-user read/reply flaw in those routes. Other private/group management routes remain release blockers pending complete access-control review. The legacy inbox still contains placeholder actions that need replacement before advertising a fully functioning private-message feature.

### Participation notifications

Employees see a header bell and a persistent inbox for new points and adjustments. Notices use committed nonzero ledger entries and link to the points history. They do not award points themselves. Installation suppresses old awards; generation is replay-safe and accounts for late commits. Read `notifications/README.md` for setup, batching and ownership tests.

### Operational visibility

Role 7 gets Operations → System checks: points freshness/coverage, inventory mismatches, incomplete cashup references and archive action counts. Missing tables are reported as unavailable. The page is read-only; it does not claim to check backups, mail delivery, security vulnerabilities or all application health.

## Install in staging, in order

1. Back up current application files and the database. Record current patch versions. Preserve server-only modifications; this is a focused update, not a full replacement archive.
2. Deploy `reports/src/Cashup.php`, `reports/install-reliability.php` and the new notifications directory. Keep old submission routes active until schema installation succeeds.
3. Run `php dashboard/reports/install-reliability.php`. This adds request tracking and archive audit tables; it does not alter existing balances or reports. Review existing monetary column types/ranges: DECIMAL is expected for reliable stored precision; the installer does not silently change them.
4. Pause points jobs/manual adjustments for notification installation, then run `php dashboard/notifications/install.php`. Points tables must already exist. Resume jobs after installing the baseline.
5. Deploy the new forms and submission handlers together, report access changes, private guards, header bell, and Operations module.
6. Change the points cron to `bash /absolute/site/path/dashboard/bin/sync-participation.sh`. Keep logs outside the public web directory. Run the notifications job repeatedly if a backlog exceeds 1,000 ledger entries.
7. Run `bash dashboard/bin/release-check.sh`. It lints the selected changed PHP files and runs existing environment, policy, arithmetic and read-only audit checks. This is not a full dependency or penetration test. Inspect every failure.

## Release blockers still open

| Area | Missing evidence/work |
|---|---|
| Authentication | Supply website-root `gist.php`, reset handlers and their JS. Verify unrestricted passwords, modern hash migration, login throttling, CSRF, reset-token expiry/reuse, session regeneration, secure cookies and account revocation. SHA-1 compatibility remains temporary. |
| Live database | Supply a schema-only export (no private rows). Verify types, nullability, indexes, foreign keys and InnoDB across affected modules. Backfill policy and historical anomalies need explicit review. |
| All legacy routes | Audit direct URLs, alternate/old scripts and role changes; a hidden menu is not authorization. Do not leave obsolete runnable copies in public folders. |
| Groups | Membership and management authorization is incomplete in supplied legacy code; e.g. group details exposed a member list without a membership check. Audit all send/invite/add/remove/delete endpoints before release. |
| Other business writes | Projects, employee administration, leave, legacy stock datasets and login-archive jobs still need owner/role, CSRF and transaction review. The legacy login archive job performs writes on a page visit. |
| Work reports | Exercise attachments, drafts, failed email, duplicate submit and attribution. Existing legacy handler behavior is not certified. |
| Cashup UX | Validation errors currently redirect rather than preserve every entered field/sector row. Add server-side recovery before staff rely on long manager forms. Confirm negative-entry/refund policy and supported money ranges. |
| Reports/exports | Verify scope by role/department, archive policy, filters, PDF dependency availability and money precision end to end. |
| Messaging | Test public deletion/reply races and private/group permissions. Public posts are not fully deduplicated on simultaneous double clicks. Notifications for replies/mentions are not yet implemented. |
| Deployment | Confirm TLS, secure session cookies, production error settings, upload execution restrictions, private logs, least-privilege DB accounts and dependency maintenance. |
| Recovery | Demonstrate a database AND upload-file restore into a clean environment. A backup job reporting success is not enough. |

## Required end-to-end tests

- Attempt cashup identity/department tampering, invalid money, malformed dates, missing CSRF and unauthorized role. No wrong-user report or partial sector data should be written.
- Submit the same cashup reference twice, including concurrent requests. Exactly one report. Reuse its reference with changed data: reject. Force sector-write failure: report and request roll back. Force email failure after commit: saved report remains and the user sees the correct outcome.
- Archive as role 7, repeat archive, try as another role, omit CSRF. Verify retained row and actor/reason history; check reports/export filtering.
- Try reading/replying to someone else's private message by guessing IDs. Test both sender and recipient replying. Complete this test matrix for every other messaging route before release.
- Run all enabled points sources: original activity → ledger → notification → user opens points history. Repeat jobs, reverse a contribution, apply an adjustment, test cross-user read-state manipulation and worker rollback. No duplicate notice or old-award flood.
- Run inventory concurrent-issue, stocktake, atomic CSV, duplicate request and reconciliation tests documented in its README.
- Run smoke tests on desktop/mobile in every theme and role. Confirm keyboard operation, visible errors and no critical UI hidden behind developer detail panels.

## Feature roadmap across sectors

| Sector | Next useful feature | Prerequisite |
|---|---|---|
| Notifications | Reply/mention notices, per-category preferences, delivery outbox and optional email digest | Verified recipients, authorization and durable deduplication |
| Points | Appeals, quality/helpful-answer awards, role/team leaderboards and policy version previews | All active sources pass reconciliation tests |
| Cashups | Saved drafts, cash variance review, approval workflow and durable email retry | Verified exact-money storage, permissions and idempotent saves |
| Inventory | Purchase-order receiving, stocktake approval, filtered movement export, locations/barcodes | Existing ledger reconciles and warehouse/unit model is agreed |
| Messaging/forum | Mentions, unread conversation state, moderation/reporting and search | Participant/role enforcement for every route |
| Work reports | Draft recovery, submission receipts, review status and safe attachments | End-to-end retries and email failure behavior verified |
| Orders | Fulfilment status, receiving links and supplier history | Order/inventory ownership and transaction boundaries defined |
| People/leave | Audited profile changes, leave approvals and clear availability balances | Access/privacy matrix and approved leave calculation rules |
| Operations | Scheduled-job alerts, mail outbox monitoring and restore-test records | Reliable monitoring destination and tested backup process |

Do not introduce cash rewards or payroll consequences from participation scores until scoring correctness, fairness and appeals are proven. The practical next step is to supply the missing authentication code and schema, run the staging gates, and resolve the blockers above before labeling this release production-ready.
