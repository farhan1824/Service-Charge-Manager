-- Service Charge Manager - Flats/Units Table
-- Version: 1.0.0

CREATE TABLE IF NOT EXISTS {WPDB_PREFIX}scm_flats (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    apartment_id BIGINT(20) UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    floor_number VARCHAR(50) DEFAULT NULL,
    holder_id BIGINT(20) UNSIGNED DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT(20) UNSIGNED NOT NULL,
    updated_by BIGINT(20) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_apartment_id (apartment_id),
    KEY idx_holder_id (holder_id),
    KEY idx_created_at (created_at),
    KEY idx_created_by (created_by),
    KEY idx_updated_by (updated_by),
    CONSTRAINT fk_scm_flats_apartment FOREIGN KEY (apartment_id) REFERENCES {WPDB_PREFIX}scm_apartments(id) ON DELETE CASCADE,
    CONSTRAINT fk_scm_flats_created_by FOREIGN KEY (created_by) REFERENCES {WPDB_PREFIX}users(ID) ON DELETE RESTRICT,
    CONSTRAINT fk_scm_flats_updated_by FOREIGN KEY (updated_by) REFERENCES {WPDB_PREFIX}users(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
