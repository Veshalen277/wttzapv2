# Independent workplace forum

The supplied dashboard linked to /dashboard/forums/ but did not include that directory. The remaining root add_user_to_topic.php used an undefined PDO connection and the wrong users_tbl name column. This package replaces those broken entry points with a self-contained forum inspired by the message board's card-feed structure.

## Install

1. Install the previously supplied dashboard-developer-refactor.zip first. This module uses its shared Portal authentication, connection, CSRF, layout and flash helpers. PHP 8.1+, mysqli with mysqlnd (get_result), and mbstring are required.
2. Back up the current navigation.php and add_user_to_topic.php. Merge this package's dashboard folder into the existing dashboard directory. Keep config.php, menu_config.php, and all existing application files.
3. In phpMyAdmin, select the database used by the live dashboard/config.php and import forums/database/install.sql. It creates only portal_forum_topics, portal_forum_posts and portal_forum_members. It does not alter users, messages, points, work reports or drafts. DDL is never run during a page request.
4. Open /dashboard/forums/view_topics.php with an allowed account. Check topic creation, replies, private membership and moderation before production rollout.

Without the tables, the forum shows a setup message rather than attempting its business queries. The installer is for this new dedicated schema; it is not a migration of an unknown older forum. No existing forum tables or data were included in the supplied archive. If your server has an older forum with data, export and inspect it before planning a separate import.

## Structure

- config.php: enable/disable switch, allowed roles, moderator roles and page sizes.
- bootstrap.php: small adapter to shared portal authentication/database/layout infrastructure.
- controller.php: request handling, CSRF validation, input validation and redirects before rendering.
- src/Repository.php: forum-only SQL, access filtering and transactional mutations.
- src/Policy.php and Input.php: access rules and validation.
- views/: list, compose and thread templates, with escaped text.
- assets/forum.css: styles scoped to .forum-module.
- database/: explicit install and optional removal scripts.
- tests/: policy and disposable-database integration checks.

There is no dependency on message-board handlers, replies tables, points logic, emails or notifications. The only host data read is users_tbl.id/fullname/user_role. No user records are written. Author IDs intentionally have no foreign keys to users_tbl; removing the forum cannot cascade into user data, and deleted users display as “Former user.” All internal foreign keys stay within forum-owned tables.

The small navigation.php update adds an optional canonical navigation path. Thread and member pages highlight the Forum/Topics context. It is a generic navigation feature and can remain after the forum is removed. The root add_user_to_topic.php is only a compatibility wrapper into the forum.

## Behavior and access

Allowed forum roles default to 2 and 7, matching the existing forum menu. Moderator role defaults to 7. To widen access, update both forums/config.php and the host menu_config.php. Access checks run on endpoints, not only in menus. Added private-topic members must already have a permitted forum role.

Open topics are visible to all allowed forum users. Private topics are visible only to their owner, explicit members and moderators. Privacy applies to search/feed queries and direct thread requests. Owners and moderators can edit the original post, lock/unlock, archive/restore, and manage private membership. Ordinary users can remove their own replies; moderators can remove any reply. Topic owners do not automatically get permission to remove someone else's reply.

Locked or archived topics reject replies on the server. Topic mutations lock the topic row and re-check visibility and permissions inside the transaction so membership changes, locking and posting serialize consistently. Archiving and reply removal are soft operations; retained rows are available to an administrator for recovery. The interface offers restoration for topics, but not deleted replies.

Titles are limited to 180 characters and bodies/replies to 10000 characters. Content is plain text, preserving punctuation and Unicode and escaping it on output. Visibility stays fixed after topic creation. There are no uploads, rich HTML, voting, likes or points awards in this version.

Discussion lists and replies are paginated. Search matches titles and initial discussion text, not reply bodies. Failed writes preserve entered text where the user still has access and the topic remains open. Successful writes use POST/redirect/GET. Removing someone's private access immediately prevents their subsequent authorized reads/writes; an already rendered page cannot be recalled from their browser.

## Verification

Static checks confirmed the missing legacy routes now exist, the schema is isolated, and no message-board/points handlers are referenced. Existing navigation JavaScript regression tests passed. PHP lint/runtime, live database integration and visual browser checks were unavailable in the editing environment. Run these before deploying broadly:

    php forums/tests/policy.php

For repository integration, create a disposable database whose name ends in _test or _testing. Import tests/users-schema.sql and database/install.sql into that disposable database, set FORUM_TEST_HOST, FORUM_TEST_USER, FORUM_TEST_PASSWORD and FORUM_TEST_DATABASE, then run:

    php forums/tests/integration.php

The integration test inserts forum fixtures, checks privacy, membership removal, replies, locks and archive/restore, then deletes its test topic. It does not use config.php or the production DB by default. Also lint the forum PHP files with php -l. On staging, verify role denial, invalid CSRF rejection, a private topic from another user's session, pagination and simultaneous lock/reply actions.

## Disable or remove

To disable immediately, set enabled=false in forums/config.php. To fully remove the feature:

1. Remove the Forum entries from the host menu_config.php.
2. Delete dashboard/forums/ and the root dashboard/add_user_to_topic.php compatibility wrapper.
3. Keep all shared app/, navigation, message-board and points files.
4. Keep the dedicated tables if you may restore the forum later. For permanent data deletion, export anything you need and explicitly run database/uninstall.sql before deleting the directory.

No uninstall operation is performed by this package. Removing only the directory will leave broken links until the menu entries are removed.
