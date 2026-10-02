-- Migration 002: Fix Foreign Key Constraints for User Deletion
-- 
-- Problem: Cannot delete users because asset_history.user_id uses ON DELETE RESTRICT.
-- Fix: Change to ON DELETE SET NULL to preserve audit trail while allowing deletion.
--
-- Also fixes assets.created_by which has the same issue.

-- 1. Drop the existing foreign key on asset_history.user_id
ALTER TABLE `asset_history` DROP FOREIGN KEY `asset_history_ibfk_2`;

-- 2. Re-add with ON DELETE SET NULL (preserves history records, sets user_id to NULL)
ALTER TABLE `asset_history` 
    ADD CONSTRAINT `asset_history_ibfk_2` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 3. Drop the existing foreign key on assets.created_by
ALTER TABLE `assets` DROP FOREIGN KEY `assets_ibfk_3`;

-- 4. Re-add with ON DELETE SET NULL
ALTER TABLE `assets` 
    ADD CONSTRAINT `assets_ibfk_3` 
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 5. Record this migration
INSERT INTO `_migrations` (`migration`, `batch`) VALUES ('002_fix_fk_constraints', 2);

