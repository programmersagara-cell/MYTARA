-- ============================================================
-- Migration 020: Manual (free-text) license assignments
-- Allows an operator to type a user name or asset tag that does
-- not exist in the users / assets tables and still record the
-- assignment on a license.
-- ============================================================

ALTER TABLE license_assignments
    ADD COLUMN IF NOT EXISTS assigned_name VARCHAR(100) DEFAULT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS assigned_tag VARCHAR(100) DEFAULT NULL AFTER assigned_name;
