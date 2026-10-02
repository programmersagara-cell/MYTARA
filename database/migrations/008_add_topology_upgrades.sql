-- Migration 008: Topology Upgrades (T-Junctions + Device Types)
-- Adds:
--  1. topology_junctions table (tap points on existing links)
--  2. tap_id / tap_side columns on network_links (branch links)
--  3. Device type enum: remove 'monitor', add 'nas', 'nvr'

-- =============================================
-- 1. Topology Junctions (T-Junction / Tap Points)
-- =============================================
CREATE TABLE IF NOT EXISTS topology_junctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    edge_id INT UNSIGNED NOT NULL,
    t FLOAT NOT NULL DEFAULT 0.5 COMMENT 'Position along edge (0.0 to 1.0)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edge_id) REFERENCES network_links(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_junctions_edge (edge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. Branch Link Tap Columns
-- =============================================
ALTER TABLE network_links
    ADD COLUMN IF NOT EXISTS `tap_id` INT UNSIGNED NULL DEFAULT NULL AFTER `port_target`,
    ADD COLUMN IF NOT EXISTS `tap_side` VARCHAR(10) NULL DEFAULT NULL AFTER `tap_id`;

-- =============================================
-- 3. Device Type Enum Update
--    Remove 'monitor', add 'nas' and 'nvr'
--    (existing 'monitor' rows become 'other')
-- =============================================
UPDATE assets SET type = 'other' WHERE type = 'monitor';
ALTER TABLE assets
    MODIFY COLUMN `type` ENUM('pc','laptop','switch','server','printer','nas','nvr','other') NOT NULL;

-- Record this migration
INSERT INTO `_migrations` (`migration`, `batch`)
SELECT '008_add_topology_upgrades', COALESCE(MAX(batch),0)+1 FROM `_migrations`;
