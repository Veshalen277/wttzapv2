-- Read-only: select your portal database before running.
SELECT COUNT(*) AS pending_notices
FROM portal_points_ledger l
JOIN portal_notification_state s
  ON s.name = 'points' AND l.id > s.cursor_id
WHERE l.delta <> 0
  AND NOT EXISTS (
    SELECT 1
    FROM portal_notifications n
    WHERE n.source = 'points'
      AND n.user_id = l.user_id
      AND CAST(n.source_ref AS BINARY) = CAST(l.id AS BINARY)
  );
