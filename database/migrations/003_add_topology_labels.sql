-- Migration 003: Add Topology Labels Table
-- Adds text annotation support for the network topology diagram

CREATE TABLE IF NOT EXISTS topology_labels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(200) NOT NULL,
    x FLOAT NOT NULL DEFAULT 0,
    y FLOAT NOT NULL DEFAULT 0,
    font_size INT UNSIGNED NOT NULL DEFAULT 16,
    color VARCHAR(20) NOT NULL DEFAULT '#64748B',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record this migration
INSERT INTO `_migrations` (`migration`, `batch`) VALUES ('003_add_topology_labels', 3);
