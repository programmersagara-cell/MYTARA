-- Migration 009: Add 'vm' device type
-- Adds 'vm' to the assets.type enum so virtual machines can be distinguished
-- from physical PCs in the network topology.

ALTER TABLE assets
    MODIFY COLUMN `type` ENUM('pc','laptop','switch','server','printer','nas','nvr','vm','other') NOT NULL;

-- Record this migration
INSERT INTO `_migrations` (`migration`, `batch`)
SELECT '009_add_vm_type', COALESCE(MAX(batch),0)+1 FROM `_migrations`;
