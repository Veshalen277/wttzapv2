#!/usr/bin/env bash
# Run on staging. Read-only; never invokes installers or the ledger mutation test.
set -u
root="$(cd -- "$(dirname -- "$0")/.." && pwd)"
if ! command -v php >/dev/null 2>&1; then echo 'BLOCKED: PHP CLI is unavailable.'; exit 1; fi
failed=0
for file in points/adjust.php points/index.php points/src/Adjustment.php points/audit.php points/tests/adjustment.php app/Passwords.php reports/src/Cashup.php reports/submit-cashup.php reports/process_report.php reports/process_special_report.php reports/report_form.php reports/special_daily_report.php reports/view_income_reports.php reports/reports_dashboard.php reports/export_report_csv.php reports/export_report_pdf.php reports/get_dashboard_data.php modules/operations/index.php notifications/Service.php notifications/install.php notifications/sync.php notifications/index.php notifications/audit.php messages/private_guard.php messages/view_private_message.php messages/send_private_reply.php views/navigation.php; do
  php -l "$root/$file" || failed=1
done
for file in bin/check-environment.php tests/cashup.php tests/passwords.php points/tests/adjustment.php points/tests/policy.php inventory/tests.php points/audit.php inventory/audit.php notifications/audit.php; do
  php "$root/$file" || failed=1
done
if [ "$failed" -ne 0 ]; then echo 'BLOCKED: at least one automated check failed.'; else echo 'Automated checks passed. Manual security, integration, restore and login gates remain mandatory.'; fi
exit "$failed"
