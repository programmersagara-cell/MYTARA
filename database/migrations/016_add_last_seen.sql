-- =============================================
-- Add last_seen column to users table for online status tracking
-- =============================================

USE itassets;

ALTER TABLE users
    ADD COLUMN last_seen DATETIME DEFAULT NULL AFTER last_login,
    ADD INDEX idx_users_last_seen (last_seen);