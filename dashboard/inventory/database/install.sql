CREATE TABLE IF NOT EXISTS portal_inventory_requests (
 request_key CHAR(64) CHARACTER SET ascii PRIMARY KEY,
 payload_hash CHAR(64) CHARACTER SET ascii NOT NULL,
 actor_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_inventory_settings (
 item_id INT PRIMARY KEY, reorder_level INT NOT NULL DEFAULT 0, archived TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_inventory_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_key CHAR(64) CHARACTER SET ascii NOT NULL,
 item_id INT NOT NULL, actor_id INT NULL, kind VARCHAR(20) NOT NULL,
 quantity_before INT NOT NULL, delta INT NOT NULL, quantity_after INT NOT NULL,
 reason VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY request_item(request_key,item_id), KEY item_time(item_id,created_at)
) ENGINE=InnoDB;
