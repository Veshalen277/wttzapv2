# Inventory side-panel editor

## What changes

Edit details, Add item and Update stock open in a side panel instead of scrolling through the stock table to a form at the bottom of the page. On small screens the panel occupies the viewport width.

- Keep the current search and list position while editing.
- Save through the existing PHP inventory service and refresh the list, summary and recent activity without navigating away.
- Retain the selected row's screen position after refresh where that row still matches the search. If a renamed item no longer matches, preserve the available scroll position and focus the search field.
- Keep validation messages and submitted values in the editor.
- Prevent repeated clicks while saving. Stock movements retain their existing request key and service-level duplicate protection.
- Cancel or Escape returns focus to the originating control. Unsaved changes require confirmation before closing.
- A failed refresh after a confirmed save is reported separately: do not repeat a successful stock update simply because the list could not refresh.
- Browsers without dialog support, disabled JavaScript, and new-tab links use a dedicated PHP editor page. The fallback preserves search when returning; it does not promise exact scroll restoration across full page loads.

The old bottom-of-list item and movement forms are removed. Category management and CSV import remain in the main inventory page.

## Installation

Back up the existing inventory files, then merge the ZIP's `dashboard` folder into the website root. Deploy all four files together:

| File | Responsibility |
| --- | --- |
| `dashboard/inventory/index.php` | Editor links, row identities, simplified main inventory page. |
| `dashboard/inventory/editor.php` | Authenticated editor, CSRF checks, existing service calls, validation and dedicated-page fallback. |
| `dashboard/inventory/assets/editor.js` | Accessible dialog, form requests, list refresh and focus/position restoration. |
| `dashboard/inventory/assets/editor.css` | Theme-aware drawer and mobile layout. |

No SQL import, migration or installer is required. The existing `src/Inventory.php`, inventory ledger and database schema remain in use. This is an incremental patch for the previously updated portal, not a standalone application.

From the current website root:

```bash
cd /home/bethelin/wttzap
php -l dashboard/inventory/index.php
php -l dashboard/inventory/editor.php
php dashboard/inventory/audit.php
```

Use the actual website folder on another domain. Reload the inventory page after deploying.

## Staging checklist

1. Search for an item near the bottom of the list. Edit it: the background must stay in place.
2. Change its supplier and save. Confirm the row refreshes, the search remains and the stock quantity is unchanged.
3. Rename an item so it still matches the search; confirm the row stays near its prior screen position. Rename it out of the search and confirm it disappears from the filtered results.
4. Submit invalid item data and confirm the editor stays open with the submitted values and error.
5. Receive stock, then issue stock on a designated test item. Verify the movement history and audit. Try an insufficient-stock issue and confirm no balance changes.
6. Double-click Save: only one request should be sent. Replaying the same movement request must not apply it twice.
7. Cancel and Escape: verify focus returns to the original link and unsaved edits prompt before closing.
8. Test mobile widths and all portal themes.
9. Open Edit details in a new tab or disable JavaScript: the dedicated editor must appear immediately without a long inventory list above it.
10. Simulate a network failure. If a save cannot be confirmed, check history before retrying. Item creation does not have the movement service's idempotency key; do not blindly repeat it.
11. Verify expired sessions and unauthorized roles cannot save. A session failure may require reopening the editor or signing in again.
12. Verify category management and CSV preview/commit still work.

## Validation performed

JavaScript syntax checking passed. The routes and form handling were inspected for preserved role checks, CSRF checks and use of the existing inventory service. ZIP contents were verified.

PHP/MySQL were unavailable locally. A browser smoke test was prepared but could not run because the Chromium executable was unavailable. Browser behavior, PHP syntax and database integration therefore require the staging checks above; they are not claimed as passed.

## Troubleshooting and rollback

If the side panel does not open, verify both new asset requests and `editor.php` return successfully. Use Open full editor when the panel offers it. The ordinary link remains a fallback.

If the application confirms a save but cannot refresh the list, reload the page to view the result. Do not submit the same operation again solely to refresh the display.

To roll back this UI, restore the previous `inventory/index.php`. The extra editor files can remain unused until removed during normal cleanup. Do not roll back the database or erase movements made while this UI was deployed.
