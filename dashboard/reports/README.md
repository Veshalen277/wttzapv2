# Cashups — user and maintainer guide

## For employees

1. Open Daily cashup (or Manager cashup if available to your role).
2. Check the date and department. Enter money received and money paid out in the matching fields.
3. Add notes to explain anything your reviewer should know.
4. Check **Your totals**, shown above the entry sections and updated as you type.
5. Choose **Review cashup**, check the confirmation, then submit. The review step has not been bypassed.

The manager form keeps its sector breakdown and staff expense fields. Check the sector-total option before submitting; it affects which entries the form uses. Help and figure explanations are expandable beneath the form.

Cashup history lets you find and print submitted reports. Cashup overview keeps its date/department filters and existing charts. This is a presentation update: it does not introduce new financial totals or reinterpret existing values.

## Installation

Back up matching files and merge the included dashboard folder into the current installation. Requires the shared Portal core. No SQL migration or changes to payment/report data are included. Merge any server-only changes to these forms before replacement.

## Files

| File | Responsibility |
|---|---|
| report_form.php | Daily cashup fields, live totals and submission confirmation |
| special_daily_report.php | Manager cashup, sectors, staff fields and confirmation |
| view_income_reports.php | Existing report search/filter, print and management screen |
| reports_dashboard.php | Existing summary and chart screen |
| assets/cashup.css | Shared theme-aware cashup presentation, summary placement and responsive styling |
| process_report.php | Existing standard submission handler; not changed |
| process_special_report.php | Existing manager submission handler; not changed |
| get_dashboard_data.php | Existing dashboard data endpoint; not changed |
| export_report_csv.php / export_report_pdf.php | Existing exports; not changed |

## Verification and boundaries

Existing inline JavaScript blocks, input IDs/names and form actions were compared before/after and remained unchanged. The stock of financial rules, database writes, authentication and points attribution remains in the existing handlers. This patch is not an audit or certification of those financial/security rules. In particular, management/delete actions on history pages retain their old backend behavior and still need a separate authorization/CSRF review.

PHP/MySQL and browser visual testing were unavailable. Before production, verify both forms with your normal test figures, including manager sector-total selection, zero values, invalid fields, confirmation/cancel, and successful persistence. Check report filters, print/export, mobile layout and each appearance palette. Charts retain a light plotting surface to preserve readability of their unchanged labels and data colors.

## Troubleshooting

- Blank form: inspect PHP logs and confirm the matching file deployed; retain the existing shared core and Composer dependency for the manager page.
- Missing styling: check reports/assets/cashup.css loads; hard refresh after uploading.
- Unexpected total: inspect the form's existing calculateTotals function and the matching server handler. Do not change only the browser formula.
- Submit error: check process_report.php or process_special_report.php logs and database schema; do not repeatedly submit before checking saved reports.
- Missing points: check the points source corresponding to reports/special_reports, its timestamp/owner and the latest sync.
- Private fields or destructive controls visible unexpectedly: inspect the server-side role checks in the legacy report screen before deployment.

Restore backed-up PHP/CSS to roll back presentation. No database rollback is needed for this UI patch.
