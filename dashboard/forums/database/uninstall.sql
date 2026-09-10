-- Optional permanent removal. Export any forum content you want to keep before running.
-- This script affects only the three forum-owned tables. It is never run by the application.
DROP TABLE IF EXISTS portal_forum_members;
DROP TABLE IF EXISTS portal_forum_posts;
DROP TABLE IF EXISTS portal_forum_topics;
