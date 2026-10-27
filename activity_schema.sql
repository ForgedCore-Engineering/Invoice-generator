-- Additive footprint storage. Existing records keep their original values.
CREATE TABLE IF NOT EXISTS app_activity (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 entity VARCHAR(80) NOT NULL,
 record_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(20) NOT NULL,
 actor_email VARCHAR(254) NULL,
 before_json LONGTEXT NULL,
 after_json LONGTEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_activity_record (entity,record_id,id),
 INDEX idx_activity_actor (actor_email,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
