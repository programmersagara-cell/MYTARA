-- ============================================================
-- Migration 021: Unify the audit trail on `audit_log`
-- ------------------------------------------------------------
-- `audit_log` becomes the SINGLE source of truth for the audit UI.
-- `asset_history` is kept (and keeps being written) so per-asset detail
-- pages (`/history/asset/{id}`) continue to work, but it is no longer the
-- data source of the `HistoryController@index` UI.
--
-- Idempotent: safe to run repeatedly (IF NOT EXISTS + NOT EXISTS guards).
-- Target: MariaDB/MySQL via XAMPP (MariaDB 10.4 supports IF NOT EXISTS).
-- ============================================================

ALTER TABLE `audit_log`
    ADD COLUMN IF NOT EXISTS `entity_type` VARCHAR(30) DEFAULT NULL COMMENT 'asset|ticket|license|user|department|setting|topology|message|disposal|backup|auth',
    ADD COLUMN IF NOT EXISTS `entity_id` INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `entity_ref` VARCHAR(80) DEFAULT NULL COMMENT 'human ref: asset_tag, ticket_number, License #4',
    ADD COLUMN IF NOT EXISTS `field_changed` VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `old_value` TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `new_value` TEXT DEFAULT NULL,
    ADD INDEX IF NOT EXISTS `idx_audit_entity` (`entity_type`, `entity_id`);

-- `entity_type` is a free VARCHAR(30): the comment above lists the core
-- values; AuditService also records 'mail' (email_queue) and 'instruction'.

-- ------------------------------------------------------------
-- Backfill: every existing per-asset history row becomes an audit_log row.
-- The NOT EXISTS guard (NULL-safe on user_id) makes this re-runnable.
-- ------------------------------------------------------------
INSERT INTO audit_log (user_id, action, description, ip_address, created_at, entity_type,
    entity_id, entity_ref, field_changed, old_value, new_value)
SELECT h.user_id, h.action, CONCAT('Asset ', COALESCE(a.asset_tag, CONCAT('#', h.asset_id))),
    h.ip_address, h.created_at, 'asset', h.asset_id, a.asset_tag, h.field_changed,
    h.old_value, h.new_value
FROM asset_history h LEFT JOIN assets a ON a.id = h.asset_id
WHERE NOT EXISTS (SELECT 1 FROM audit_log x WHERE x.entity_type='asset' AND x.entity_id=h.asset_id
    AND x.action=h.action AND x.created_at=h.created_at AND x.user_id <=> h.user_id);
