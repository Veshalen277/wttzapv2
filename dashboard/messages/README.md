# Messages workspace

## Everyday use

- Write a team update in the composer and choose Post message.
- Search the board to find earlier discussions.
- Open a conversation to read it in full and add a reply.
- Private inbox and group shortcuts open the existing separate messaging pages.
- Useful guidance is beside the feed; visibility and participation details expand when needed.
- More options & message details contains record information and removal. Only the author or role 7 can remove a conversation; confirmation is required, and replies are removed with it.

## Installation and scope

Back up matching files, then merge this patch into dashboard/. Requires the existing shared app/ core and messages/replies tables. No new migration or synthetic activity is included. The redesigned screens are the shared messageboard, composer and public conversation pages. Private-message and group implementations are not refactored by this patch.

PHP/MySQL and browser rendering were unavailable for live validation. Check on staging before production. The removal operation requires InnoDB on messages, replies and notifications (if present). Existing foreign keys not represented in the supplied code may reject deletion; the application logs the error instead of claiming success.

## Files

| File | Responsibility |
|---|---|
| workspace.php | Authenticated controller, CSRF checks, prepared writes, read queries and page rendering |
| assets/workspace.css | Responsive presentation using shared theme variables |
| message_board.php | Main entrypoint into workspace.php |
| view_message.php | Existing conversation link compatibility using message_id |
| message_form.php | Redirect to the inline composer |
| message_actions.php / message_logic.php | Obsolete POST handlers return 409 so older forms cannot bypass new checks |
| message_queries.php / partials/ | Old view code retained but no longer loaded by the new entrypoints |
| view_private_messages.php and related send/reply files | Existing private-message workflow; unchanged |
| view_groups.php and group files | Existing groups workflow; unchanged |

## Data and points

Posts write messages(user_id,message_text,posted_at); replies write replies(message_id,user_id,reply_text,replied_at). The actor always comes from the authenticated session. Existing points sources continue to reconcile those records. Their length thresholds/caps still apply; submitting a short message does not guarantee points. Deleting an eligible conversation/reply is reflected on the next successful points sync.

The previous self-only notification insert is not reproduced: it could fail after the message was already saved and did not notify teammates. This patch does not claim to add push notifications, delivery/read receipts or unread counts. Add a dedicated notification outbox if those are required.

## Troubleshooting and acceptance

- HTTP 409 on an old form: reload message_board.php and use the new composer.
- Missing content: verify messages/replies columns and read the PHP log entry prefixed Messages read.
- Save error: inspect the conversation before retrying; check Messages workspace entries in the PHP log. POST/redirect/GET prevents browser-refresh replay after success; simultaneous double submissions are not fully deduplicated.
- Removal blocked: check author/role and database engines. Do not bypass permission checks or use raw DELETE statements to resolve a UI error.
- No points: inspect Points Coverage, last sync, text minimum and daily cap.
- Styles incorrect: check workspace.css response and installed color-modes.css.

Test post/reply, preserved text on validation failure, apostrophes/Unicode, HTML escaping, search, empty feed, pagination at the last page, missing conversation, expired CSRF, unauthorized removal and author removal with replies. Feed pages contain 12 conversations; replies are paged in batches of 30. Test light/dark/morning/afternoon and mobile.

Rollback by restoring the backed-up message PHP/CSS files. Do not restore stale database content over newer conversations.
