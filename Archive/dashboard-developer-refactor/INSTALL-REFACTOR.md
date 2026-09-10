# WTTZap developer refactor — installation

This is a cumulative patch for the supplied dashboard, including the latest shared navigation/layout. Merge its dashboard/ folder into the existing dashboard directory. Do not replace the entire installation with this patch. It intentionally excludes live credentials, database dumps, uploads, vendor libraries, menu_config.php, and employee report/view/submit files.

## What is ready
- Shared application bootstrap, named user/session adapter, role guards and reusable connection adapter.
- Isolated navigation/submenu/flash templates and versioned CSS/JavaScript URLs.
- Safe flash rendering: session messages are escaped rather than inserted into JavaScript.
- Discoverable feature modules and a CLI module generator.
- A protected draft API/repository and an opt-in JavaScript client with ordered requests.
- Read-only environment/schema and route diagnostics, plus core/interaction tests.
- DEVELOPMENT.md with feature examples and a prioritized plan for the remaining legacy code.

## Deploy together
Back up each existing file listed below and deploy all patch files together. app/, views/ and header.php depend on one another. The patch retains your current config.php as the database/SMTP source and your current menu_config.php as the original role menu source. It assumes /dashboard/ and root /index.php sign-in paths.

PHP 8.1+ is required. No Composer install, database migration, framework conversion, or bundler is introduced. Existing numeric session values are adapted to named fields; no session schema change is required. User ID must be positive, and role must be a non-negative integer. Error details default to server logs; PORTAL_DEBUG=1 enables development error display.

The newer employee draft implementation on your server was not supplied. This patch does not overwrite it or claim to resolve its work_draft_updated_at dependency. The new draft API uses the confirmed work_draft column and is opt-in; connect it only after removing the old autosave handler. Existing report submission and email sending are unchanged.

## Develop a feature

    cd dashboard
    php bin/create-module.php equipment

Edit modules/equipment/module.php and index.php, then enable the module. Its permitted roles automatically receive the header/sidebar/search entries. Follow docs/DEVELOPMENT.md for new pages and POST handlers.

## Verify before production
JavaScript syntax checks, navigation tests and draft-client tests passed in the editing environment. PHP execution/lint, live DB integration and visual browser testing were unavailable. Run these checks on the development/staging PHP host:

    php bin/check-environment.php
    php bin/check-routes.php
    php tests/core.php
    node tests/navigation.test.cjs
    node tests/drafts.test.mjs

Lint the changed PHP files with php -l. Check sign-in/logout, role-specific navigation, module authorization, flash messages, existing work/cashup forms, and draft API responses. The route checker may reveal links already missing from the original archive. It changes nothing. The new CSRF helpers protect the starter and new API, not every legacy form.

## Rollback
Restore the backed-up shared files, index.php, logout.php and messages.php. Remove newly added app/, views/, api/work-draft.php and other added files only if they did not exist before deployment. The patch itself makes no database schema change. Any drafts saved later through the API are ordinary work_draft changes.

## Files
- header.php
- navigation.php
- admin_header.php
- admin_navigation.php
- footer.php
- inc/sidebar.php
- index.php
- logout.php
- messages.php
- css/navigation.css
- css/portal-theme.css
- js/navigation.js
- js/portal.js
- tests/navigation.test.cjs
- tests/core.php
- tests/drafts.test.mjs
- app/Modules.php
- app/Auth.php
- app/User.php
- app/Flash.php
- app/Menu.php
- app/Connection.php
- app/autoload.php
- app/bootstrap.php
- app/Html.php
- app/Csrf.php
- app/Repositories/WorkDraft.php
- app/Http/Response.php
- views/navigation.php
- views/flash.php
- views/submenu.php
- api/work-draft.php
- scaffolds/module/index.php
- scaffolds/module/module.php
- bin/check-environment.php
- bin/create-module.php
- bin/check-routes.php
- docs/DEVELOPMENT.md
- js/api/drafts.mjs
