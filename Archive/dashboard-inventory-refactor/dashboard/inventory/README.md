# Inventory module

This module replaces the active **Stock** section backed by `sc_stock_items` and `sc_stock_categories`. It preserves those rows, IDs and balances. It does not merge the separate legacy `stock_items` or `fs_stock_items` datasets. Confirm their ownership before planning a separate migration.

## Installation

1. Back up the database and all matching application files. Use staging first. Requires the shared `app/` core from the developer refactor.
2. Put the new `inventory/` directory on the server. During a stock maintenance window run `php dashboard/inventory/install.php` from the website root. It requires both existing stock tables to be InnoDB and rejects null/negative balances. It creates three new tables and records opening balances; it does not alter stock quantities or reconstruct historical movements.
3. Only after installation succeeds, deploy the included `stock/` compatibility routes and `menu_config.php`. Merge any newer custom menu changes. Legacy POST forms now return 409 instead of applying old balance overwrites; reload the inventory screen.
4. Open `/dashboard/inventory/index.php`. Roles 2, 3, 4, 5 and 7 have access, matching the previous Stock menus. Role permissions are currently shared for catalog changes, imports and movements; introduce a separate approval role before delegating stocktake authority more broadly.
5. Run `php dashboard/inventory/tests.php` and `php dashboard/inventory/audit.php`. PHP/MySQL were unavailable in the build environment; these PHP checks have **not** been run here.

## Daily workflow

- **Create item:** choose category, prices, supplier and reorder threshold. New items start at zero. Receive initial stock with a reference.
- **Receive:** adds a positive number of units. Enter a delivery/reference in the reason.
- **Usage/sale:** removes a positive number of units and rejects insufficient stock.
- **Stocktake:** records the actual counted quantity, including zero. The movement shows the difference. Stocktake counts are authoritative and should be performed while the counted item is not moving physically.
- **Archive:** only zero-balance items can be archived. Unarchive before receiving again. There is no destructive delete in the new workflow.
- **CSV:** UTF-8 header exactly `item_id,quantity,reason`, at most 200 data rows, one row per existing item, file smaller than 256 KB. Select receive/usage/stocktake, preview, then apply. All rows commit together or all roll back. Balances are checked at commit, not frozen at preview.
- **History:** latest 100 rows, with actor, reason, before, delta and after. Correct mistakes with a new movement; do not edit ledger rows.

Example CSV:

```csv
item_id,quantity,reason
12,8,Delivery INV-104
18,3,Delivery INV-104
```

The catalog shows at most 500 matching items; search by name to narrow results. Costs are current unit-cost fields, not a FIFO/weighted-average valuation engine. Old `order_qty`/`received_qty` columns are retained for historical compatibility, not maintained as a purchasing system. This module does not yet implement purchase orders, warehouses, transfers, batches, serial numbers, barcodes or fractional units.

## File responsibilities

| File | Responsibility |
|---|---|
| `index.php` | Role/CSRF checks, request dispatch, CSV preview, queries and screen rendering |
| `src/Inventory.php` | Prepared queries, validation, row locks, balance arithmetic, atomic catalog/movement writes |
| `database/install.sql` | Request keys, item settings and movement ledger tables |
| `install.php` | CLI prerequisites and one-time opening snapshots; safe reruns skip items already represented in history |
| `audit.php` | Read-only reconciliation of live quantity against ledger deltas; nonzero exit on mismatch |
| `tests.php` | CLI arithmetic and validation checks; not a substitute for database tests |
| `assets/inventory.css` | Responsive screen styles using the portal's theme variables |
| `../stock/sc_*.php`, `../stock/upload.php`, `../stock/process_stock.php` | Compatibility routes: GET redirects, obsolete POST returns 409 |
| `../menu_config.php` | Stock submenu links and role visibility |

## Transactions and duplicate handling

Movement requests carry a random request key. The server stores a hash of the actor, operation and normalized rows. Replaying the same key and data does not move stock twice; changing the data with a used key is rejected. Items are locked in ID order inside one InnoDB transaction. The request row, balance updates and movement rows commit together. Catalog/category forms use ordinary POST/redirect/GET and name checks; they do not use the movement replay keys.

No-op stocktakes are logged with delta zero for audit, but the existing points trigger awards nothing for unchanged tracked fields. Inventory writes use the authenticated connection, so the existing `stock_change` points adapter continues to work if installed. No additional movement-based scoring source is added, preventing double scoring. Current stock points remain capped by the points policy and trigger's per-user/item/day identity; they are not a point for every ledger row. Reorder-only settings and category changes do not earn stock points.

## Troubleshooting

| Symptom | Check / action |
|---|---|
| Setup incomplete | Run installer on staging; check error log for missing stock columns/tables. Never repeatedly import SQL blindly. |
| InnoDB prerequisite fails | Have the database administrator review and convert the legacy table with a backup; the installer does not perform implicit engine conversion. |
| HTTP 403 | Check session role against `[2,3,4,5,7]`; menu visibility alone is not authorization. |
| HTTP 409 after old form | Reload the new inventory URL and re-enter the operation. Do not replay the obsolete payload. |
| Insufficient stock | Refresh quantity; another employee may have issued stock first. Do not bypass validation. |
| CSV fails | Check exact header, unique existing item IDs, whole numbers, reasons, size, and current balances. A failed commit changes no rows. |
| SQL deadlock | Transaction rolls back. Refresh and retry after checking history; retain the original request key when retrying the exact same submission. |
| Balance mismatch | Stop writes to that item; check for SQL edits, imports or separate legacy writers. Restore/reconcile from backups and evidence. A normal stocktake preserves an existing ledger offset; it is not a tool for silently repairing an out-of-band corruption. |
| Points absent | Check `points/coverage.php`, action triggers, authenticated actor and sync schedule. See the main README. |
| Theme looks wrong | Deploy latest `css/color-modes.css`, check asset requests and hard-refresh. Inventory CSS uses fallback light colors without that theme layer. |

## Required staging tests

Receive, issue, stocktake to zero, archive/unarchive, duplicate submit, insufficient stock, two simultaneous issues against one remaining unit, CSV with one invalid row (none saved), import replay, missing category, missing item, SQL rollback and points replay. Run the audit after each sequence. Test all themes and mobile sizes. No production deployment or live database testing has been performed here.

## Rollback

If no new movements have been recorded, restore backed-up PHP routes and menus. Once live stock has moved, coordinate code rollback with database reconciliation: do not drop the ledger or restore an old quantity snapshot independently. New ledger and settings tables contain audit evidence. Keep them even if the UI is rolled back.
