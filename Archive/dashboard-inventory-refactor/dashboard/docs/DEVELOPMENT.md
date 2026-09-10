# Developing the WTTZap portal

The goal is to add a feature in one module, register its pages and roles there, and reuse the existing layout. This is an incremental PHP refactor, not a framework migration. It targets PHP 8.1+ and the application's existing /dashboard/ deployment.

## Create a feature

From the dashboard directory on a development machine:

```sh
php bin/create-module.php equipment
```

This creates `modules/equipment/module.php` and `modules/equipment/index.php`. The feature starts disabled. In its module.php:

```php
return [
    'title' => 'Equipment',
    'enabled' => true,
    'roles' => [2, 7],
    'pages' => [
        ['title' => 'Overview', 'file' => 'index.php'],
        ['title' => 'Requests', 'file' => 'requests.php'],
    ],
];
```

Create requests.php before enabling this definition. Copy the generated index.php pattern for each new endpoint, including `Modules::guard(basename(__DIR__))`. The role check must run on the endpoint, not just in navigation. The generator never overwrites an existing module. You can also copy scaffolds/module manually if shell access is unavailable.

Once enabled, authorized users see Equipment in the header, its pages in the sidebar, and its pages in menu search. No header, sidebar, search, or original menu_config.php edits are needed. Existing roles and menus remain in menu_config.php; new modules extend them. Module roles are integer IDs, not strings.

A module's registered pages currently share one role list. If pages need different permissions, split the modules or add explicit endpoint checks. Module files are trusted application code, not user-uploaded configuration. Remove a module or set enabled=false to hide its menu and block guarded endpoints.

## Page/request order

1. Load app/autoload.php and check module access.
2. Load app/bootstrap.php for the authenticated user and database connection.
3. Validate input, check CSRF, and process a POST before rendering.
4. Redirect after a successful POST, or prepare the data for rendering.
5. Set $pageTitle and include header.php, your content, then footer.php.

The generated page demonstrates a CSRF-protected form and a post/redirect/get flow. Its starter action changes no database data. Existing legacy pages can continue including header.php directly; moving their POST handlers before rendering is a gradual migration.

## Shared services

| Component | Purpose |
| --- | --- |
| app/bootstrap.php | Session guard, timezone, DB setup, and legacy variable aliases |
| app/User.php | Named id, role, name, department and tasks from the existing numeric session array |
| app/Auth.php | Authentication and explicit role checks |
| app/Connection.php | Reuse the deployed config.php connection without replacing credentials |
| app/Html.php | Escaped output, isolated templates, versioned asset URLs |
| app/Csrf.php | Session token, form field and token verification |
| app/Flash.php | One-time messages compatible with existing session message keys |
| app/Menu.php | Full-path menu matching and nested section landing links |
| app/Modules.php | Module discovery, definition validation and role-filtered menus |
| app/Http/Response.php | JSON responses and local redirects |
| app/Repositories/WorkDraft.php | Prepared draft reads/writes against confirmed columns |
| views/ | Shared templates that do not leak local variables into callers |

New namespaced classes load automatically from app/. Existing Composer dependencies and composer.lock are unchanged. Keep queries in a repository/service and pass prepared data into a template. Do not read $_POST or run SQL from a new template. Escape output with Portal\Html::escape; do not strip punctuation from user input.

Connection::get reuses config.php and mysqli. Bootstrap exports $con, $db, $conn, $role, $data, $fullName, $current_month and $current_year for legacy pages. $portalUser provides named properties for new pages. The legacy u_data session structure remains unchanged. User ID must be positive and role must be a non-negative integer. The existing root sign-in URL is /index.php.

## Draft API: opt-in migration

The new /dashboard/api/work-draft.php endpoint is not wired into the existing employee form. Your deployed employee code differs from the supplied archive; inspect that version before replacing its autosave implementation. Do not run the old and new autosave writers simultaneously.

| Request | Result |
| --- | --- |
| GET | Current user's draft and CSRF token |
| POST `{ "action": "save", "text": "..." }` | Save the current user's draft |
| POST `{ "action": "clear" }` | Clear the current user's draft |

POST requires application/json and the X-CSRF-Token header. The user ID always comes from the session. It cannot be selected by the caller. Drafts are limited to 100000 UTF-8 bytes. The API returns 401 for unauthenticated access, 403 for invalid CSRF, 405 for unsupported methods, and validation errors as JSON. It uses only users_tbl.id and work_draft, not work_draft_updated_at. It does not modify the existing report-submit/email workflow or add optimistic concurrency across browser tabs.

A no-build JavaScript client is provided:

```html
<script type="module">
import { createDraftClient } from '/dashboard/js/api/drafts.mjs';
const drafts = createDraftClient();
// Call these from your form handlers and display success/failure in the UI:
// const { draft } = await drafts.load();
// await drafts.save(textarea.value);
// await drafts.clear();
</script>
```

The client serializes requests within that client instance so earlier saves cannot arrive after a later save/clear. Failures are surfaced to the caller; it does not silently retry or overwrite user text. Use one instance per editor. Cross-tab edit conflicts need a version column and a migration as a separate feature.

## Checks

```sh
php bin/check-environment.php
php bin/check-routes.php
php tests/core.php
node tests/navigation.test.cjs
node tests/drafts.test.mjs
```

The environment checker inspects extensions and the active DB schema without changing it. The route checker reports existing menu destinations missing from the deployment; it does not invent replacement routes. Some original archive links may already be missing. Neither checker emails anyone or writes application data.

For PHP syntax, lint the changed PHP files with php -l before deployment. JavaScript tests ran successfully in the editing environment. PHP execution/lint and live database/browser integration were unavailable there; the PHP tests are supplied for your development/staging host, not reported as already passed. Smoke-test sign-in, roles 0–7, menu navigation, flash messages, work report and cashup submission after merging.

PORTAL_DEBUG=1 enables PHP error display in development. Error display defaults off in the shared bootstrap; check PHP error logs for details. This is not a whole-application exception handler, and legacy files may still override their own error settings.

## Recommended next improvements, in order

1. **Versioned database migrations.** Record required columns/indexes alongside the feature. The work_draft_updated_at failure shows why deploying code and schema independently is brittle. Add a schema-version table and migration runner only after inspecting the complete live schema.
2. **Refactor report submission into a service.** In the supplied submit handler, report insertion, attachment moves, duplicate checking and email delivery are intertwined. It stores an attachment path and then deletes the attachment during cleanup. Separate retained attachments from temporary email files, define retention rules, and validate upload types/size before moving them.
3. **Make one-report-per-day a database invariant.** The supplied SELECT-then-INSERT check can race. Confirm duplicate data and intended employee/date semantics, then introduce a unique key through a reviewed migration. Avoid using email delivery as the success condition for a committed report.
4. **Add a notification outbox.** Commit the report and an outbox entry together, send mail in a worker/cron task with retries, and record status. This needs new tables and hosting scheduler configuration, so it is not silently added here.
5. **Move remaining settings out of application files.** The archive contains embedded database/SMTP settings in multiple places. Centralize environment-based settings on the host, update the legacy consumers, and remove committed secrets. This patch deliberately does not replace the live config.php.
6. **Migrate legacy writes to endpoint authorization and CSRF checks.** The new module starter/API provide this pattern. They do not retroactively protect every old PHP endpoint. Start with user administration, uploads, cashup and report submission.
7. **Add integration tests and automated deployment checks.** Use a disposable database to test authentication, role access, report submissions, draft saves, schema migrations and attachments. Run PHP lint/core tests and Node tests on every change. Keep production data out of test fixtures.
8. **Consolidate page CSS gradually.** The shared layout has a clear theme layer, but legacy pages still contain inline styles and broad Bootstrap overrides. Move each feature's styling alongside its module when that feature is next edited.

These are recommendations grounded in the supplied code, not claims that all legacy endpoints have already been audited or rewritten. The new foundation supports that migration without forcing a new framework, database, bundler, or URL scheme.
