Shipping Instructions
Run these one at a time, stopping if a command reports an error.

Update	Command
Points tables and activity tracking	php dashboard/points/install.php --with-actions
Inventory tables and opening balances	php dashboard/inventory/install.php
Cashup request and archive tracking	php dashboard/reports/install-reliability.php
Calculate participation points	php dashboard/points/sync.php
Notification tables and initial baseline	php dashboard/notifications/install.php

For your existing database: rerunning the notification installer preserves its existing baseline. It will not remove the historical notification backlog we identified.

Once you’re ready to process those notifications, run:

php dashboard/notifications/sync.php

For normal ongoing updates, this single command runs points first, then notifications:

bash dashboard/bin/sync-participation.sh

Schedule that combined command every five minutes. The installers above are not scheduled jobs.

Then check each system:

php dashboard/tests/passwords.php
php dashboard/points/tests/adjustment.php
php dashboard/points/tests/policy.php
php dashboard/inventory/tests.php
php dashboard/tests/cashup.php

Run the database audits:

php dashboard/points/audit.php
php dashboard/inventory/audit.php
php dashboard/notifications/audit.php

Finally:

bash dashboard/bin/release-check.sh
