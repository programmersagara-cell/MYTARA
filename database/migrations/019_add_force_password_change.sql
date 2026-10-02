-- ==========================================================
-- Migration 019: Add force_password_change column to users table
-- ==========================================================

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `force_password_change` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Set flag for users who have never logged in
UPDATE `users` SET `force_password_change` = 1 WHERE `last_login` IS NULL;
