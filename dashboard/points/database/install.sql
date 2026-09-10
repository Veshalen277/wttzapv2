CREATE TABLE IF NOT EXISTS portal_points_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 finished_at DATETIME NULL, status VARCHAR(20) NOT NULL DEFAULT 'running'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS portal_points_sources (
 source VARCHAR(64) PRIMARY KEY, run_id BIGINT UNSIGNED NOT NULL, status VARCHAR(24) NOT NULL,
 detail VARCHAR(255) NOT NULL, processed INT NOT NULL DEFAULT 0, checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS portal_points_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_key CHAR(64) CHARACTER SET ascii NOT NULL,
 source VARCHAR(64) NOT NULL, source_ref VARCHAR(255) NOT NULL, user_id INT NOT NULL, kind VARCHAR(64) NOT NULL,
 occurred_at DATETIME NULL, awarded INT NOT NULL, active TINYINT NOT NULL DEFAULT 1,
 revision INT NOT NULL DEFAULT 1, rule_version INT NOT NULL, reason VARCHAR(255) NOT NULL,
 seen_run BIGINT UNSIGNED NOT NULL, UNIQUE KEY event_identity(event_key),
 KEY daily_budget(user_id,kind,occurred_at), KEY source_scan(source,seen_run,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS portal_points_ledger (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_id BIGINT UNSIGNED NOT NULL, revision INT NOT NULL,
 user_id INT NOT NULL, delta INT NOT NULL, occurred_at DATETIME NULL, recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reason VARCHAR(255) NOT NULL, UNIQUE KEY event_revision(event_id,revision),
 KEY user_period(user_id,occurred_at), FOREIGN KEY(event_id) REFERENCES portal_points_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS portal_points_actions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, kind VARCHAR(64) NOT NULL,
 source_ref VARCHAR(180) NOT NULL, activity_day DATE NOT NULL, occurred_at DATETIME NOT NULL,
 UNIQUE KEY action_once(user_id,kind,source_ref,activity_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
