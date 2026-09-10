# Notification collation fix

The notification reference column and CAST(ledger ID AS CHAR) can use different collations. MySQL then rejects their equality comparison with error 1267. This affects the previously supplied diagnostic query, the notification generator and the notification audit.

This patch replaces that comparison with CAST(n.source_ref AS BINARY)=CAST(l.id AS BINARY). References are canonical decimal ID strings, so exact byte equality is appropriate. The table schema, existing points, notification baseline and notification records are unchanged. No ALTER TABLE or reinstall is required.

## Install

Back up the two current PHP files. Merge this ZIP's dashboard folder into your website root, replacing:

- dashboard/notifications/Service.php
- dashboard/notifications/audit.php

This patch is additional to the earlier points-correction patch; it does not replace that patch.

Run pending-notices.sql in phpMyAdmin with the correct database selected. It is read-only. The included diagnostics.sql also replaces the earlier diagnostic file and contains the corrected comparison.

From the website root, verify the PHP files and run the read-only audit:

```bash
cd /home/bethelin/wttzap
php -l dashboard/notifications/Service.php
php -l dashboard/notifications/audit.php
php dashboard/notifications/audit.php
```

The audit should now print counts instead of failing on this comparison. A nonzero pending count still makes the audit exit with failure: that is a backlog requiring review, not this collation error.

## Historical notifications

The previously supplied database had baseline 0 and 2,285 pending notices, mostly historical awards. This fix allows the worker to process them; it does not suppress them. If the combined cron job is enabled, it may begin processing that backlog as soon as this patch is deployed. Pause that job before deployment if you have not yet decided to announce historical awards.

Do not reset the baseline blindly, because that can skip recent corrections too. Once the backlog policy is settled, normal scheduled processing can resume. No sync command is part of the read-only verification above.

## Validation and limits

All three comparisons in the worker/audit were updated, along with the diagnostic SQL. PHP/MySQL execution is unavailable in the build environment; verify on the test server before production. This addresses the reported comparison, not every possible collation conflict elsewhere in the application.

Reference: MySQL CAST documentation explains binary-string comparisons:
https://dev.mysql.com/doc/refman/8.0/en/cast-functions.html
