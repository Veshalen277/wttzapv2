-- READ ONLY. Select the correct database in your SQL client before running.
-- Incident totals. ID 27 applies to the supplied database, not every installation.
SELECT e.kind, SUM(l.delta) AS points
FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id
WHERE l.user_id=27 GROUP BY e.kind;
SELECT LEFT(occurred_at,7) AS activity_month, SUM(delta) AS points
FROM portal_points_ledger WHERE user_id=27 GROUP BY LEFT(occurred_at,7);
SELECT l.id,l.event_id,l.user_id,l.delta,l.occurred_at,l.recorded_at
FROM portal_points_ledger l JOIN portal_points_events e ON e.id=l.event_id
WHERE l.user_id=27 AND e.source='manual' ORDER BY l.id;

-- Event accounting must reconcile without deleting historic entries.
SELECT e.id,e.user_id,e.awarded,e.active,COALESCE(SUM(l.delta),0) AS actual
FROM portal_points_events e LEFT JOIN portal_points_ledger l ON l.event_id=e.id
GROUP BY e.id,e.user_id,e.awarded,e.active
HAVING actual<>e.awarded*e.active;

-- Source IDs only; no private message bodies, contact details or passwords.
SELECT w.id,w.employee_id FROM work_tbl w LEFT JOIN users_tbl u ON u.id=w.employee_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM reports r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM mauritius_reports r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM special_reports r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM leave_applications r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.points_user_id FROM orders r LEFT JOIN users_tbl u ON u.id=r.points_user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM suggestions r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM suggestion_replies r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT r.id,r.user_id FROM acknowledgments r LEFT JOIN users_tbl u ON u.id=r.user_id WHERE u.id IS NULL;
SELECT source,status,detail,processed,checked_at FROM portal_points_sources ORDER BY source;

-- Pending notices; do not update this baseline without reviewing the cutoff.
SELECT * FROM portal_notification_state;
SELECT COUNT(*) AS pending_notices FROM portal_points_ledger l
JOIN portal_notification_state s ON s.name='points' AND l.id>s.cursor_id
WHERE l.delta<>0 AND NOT EXISTS (
 SELECT 1 FROM portal_notifications n
 WHERE n.source='points' AND CAST(n.source_ref AS BINARY)=CAST(l.id AS BINARY) AND n.user_id=l.user_id
);

-- Inspect financial discrepancies; this does not repair them.
SELECT id,report_date,total_income,
 COALESCE(income_cash,0)+COALESCE(income_card,0)+COALESCE(income_other,0) AS component_total
FROM mauritius_reports
WHERE COALESCE(total_income,0) <> COALESCE(income_cash,0)+COALESCE(income_card,0)+COALESCE(income_other,0);
