-- Run once in the same database used by dashboard/config.php. No existing tables are changed.
CREATE TABLE IF NOT EXISTS portal_forum_topics (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 author_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 body TEXT NOT NULL,
 visibility ENUM('open','private') NOT NULL DEFAULT 'open',
 is_locked TINYINT(1) NOT NULL DEFAULT 0,
 is_archived TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY forum_feed(is_archived, updated_at, id), KEY forum_author(author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_forum_posts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 topic_id BIGINT UNSIGNED NOT NULL,
 author_id INT NOT NULL,
 body TEXT NOT NULL,
 is_deleted TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY forum_replies(topic_id,is_deleted,id),
 CONSTRAINT portal_forum_posts_topic FOREIGN KEY(topic_id) REFERENCES portal_forum_topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_forum_members (
 topic_id BIGINT UNSIGNED NOT NULL,
 user_id INT NOT NULL,
 added_by INT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(topic_id,user_id), KEY forum_member_user(user_id),
 CONSTRAINT portal_forum_members_topic FOREIGN KEY(topic_id) REFERENCES portal_forum_topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
