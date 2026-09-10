# WTTZap layout update

This update applies the supplied reference dashboard's compact white header, pale workspace, subtle borders, restrained cards, collapsible sidebar, and account dropdown to the existing PHP portal.

## Navigation
Main sections remain across the header, following the requested workplace navigation model. Clicking a section changes its sidebar immediately. The hamburger opens or collapses the sidebar on desktop and opens an overlay drawer on mobile. Desktop collapse preference is remembered locally. Search finds pages in the current user's existing role menu. The account menu links to the existing profile, work report, and logout pages.

Submenu links make normal PHP page requests. The shared layout remains consistent, but this is not an AJAX or single-page conversion. Existing form submissions, redirects, downloads, and browser history retain their normal behavior. Active submenu state is derived from the current URL. No invented notification counters, hotel statistics, or database fields are added.

## Install
1. Back up the matching files in your live dashboard directory.
2. Extract the included dashboard folder into the application root, merging it with your existing dashboard folder. This is a patch, not a standalone installation. Do not delete the existing dashboard directory.
3. Hard-refresh the browser to reload the CSS and JavaScript.
4. Check desktop/mobile navigation and a representative work-report and cashup submission on your staging host before production rollout.

The existing menu_config.php remains the source of role-based navigation. This patch does not replace your database configuration, employee/pages/emp_profile.view.php, employee submission code, existing menu definitions, assets, or vendor libraries. It does not resolve an outstanding work_draft_updated_at schema mismatch; keep the deployed draft code and matching schema fix together. No SQL migration is required for this layout update.

The shared header assumes the application's existing /dashboard/ URL. Existing styles.css and Bootstrap assets must remain installed. Shared admin and employee headers now use the same navigation shell. Old menu-only columns are removed by the navigation script; adjacent activity/bulletin content is retained. Other existing page-specific content and scripts are preserved.

## Verification
JavaScript syntax checks passed. The included Node tests cover desktop collapse, section selection, ordinary links, modifier-clicks, responsive state, mobile dismissal, search results and empty states, search keyboard behavior, account dismissal, and unavailable browser storage:

    cd dashboard
    node tests/navigation.test.cjs

The original role-menu definitions were verified unchanged. No PHP runtime, live database integration test, or browser visual test was available/performed in this environment. The styling was implemented from the visible reference screenshots; it is not a claim of pixel-identical rendering across every existing page.

## Included files
- header.php
- navigation.php
- admin_header.php
- admin_navigation.php
- footer.php
- inc/sidebar.php
- css/navigation.css
- css/portal-theme.css
- js/navigation.js
- js/portal.js
- tests/navigation.test.cjs
