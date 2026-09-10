# Dashboard navigation update

## Behavior
- Main sections run across the fixed header.
- Selecting a section with children immediately replaces the left submenu without leaving the current form or page.
- Selecting a submenu loads its existing PHP page in the main workspace. These are normal full-page requests, not AJAX replacements, so existing form submissions, page scripts, redirects, downloads, and browser history continue to work.
- The destination URL selects the matching main section and submenu on arrival. Direct links work without stored browser state.
- On narrow screens, the section menu opens as a drawer with an accessible toggle and Escape-to-close behavior.
- Existing role menus remain the source of truth in menu_config.php. Navigation visibility does not replace endpoint authorization.
- Legacy menu-only columns are reclaimed; adjacent activity cards and other content are retained. Employee bulletins remain in the News column.

## Other improvements
- Shared employee/admin header and navigation implementation.
- CSS and JavaScript moved into dedicated shared files with dashboard-root asset paths.
- Removed the footer input filter that silently deleted password punctuation and international text.
- Guarded session initialization, reliable include paths, and corrected dashboard entry redirect targets.
- Optional TinyMCE initialization and non-overlapping online-user polling, paused while hidden.
- Removed the obsolete commented duplicate menu configuration.

## Installation
Back up the current dashboard directory. Extract this archive over the matching dashboard directory on the existing PHP/MariaDB host. Deploy the PHP, CSS and JavaScript changes together, then hard-refresh the browser. No database migration is included or needed. Existing configuration, uploads, vendor libraries and assets are retained. The existing application is assumed to run at /dashboard/.

## Validation
Passed JavaScript syntax checks and the included Node interaction tests:

    node tests/navigation.test.cjs

Checks cover initial selection, switching sidebar sections, ordinary direct links, modifier-click behavior, mobile drawer state, and Escape/focus handling. Role menu definitions were compared to the original archive and are unchanged. New shared assets and entry redirect destinations exist.

PHP execution, database integration, email delivery, and visual browser testing were not performed. The environment does not provide a PHP runtime or the running application database. Before deployment to production, verify sign-in and each role, work reports, order/cashup submissions, and mobile layout on your staging host. Existing routes outside this archive and existing page-specific behavior still depend on the original deployment.

## Changed files
- admin_header.php
- admin_navigation.php
- employee/pages/emp_profile.view.php
- footer.php
- header.php
- inc/sidebar.php
- index.php
- js/scripts.js
- menu_config.php
- navigation.php
- css/navigation.css
- js/navigation.js
- js/portal.js
- tests/navigation.test.cjs
- MODERNIZATION.md
