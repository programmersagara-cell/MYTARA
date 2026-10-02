-- ============================================================
-- Migration 013: Add IP address to concerns for topology highlight
--  - Add ip_address column to concerns (captured at submission time)
--  - Add index for fast matching against topology node IPs
-- ============================================================

-- Add ip_address column to concerns
ALTER TABLE concerns
    ADD COLUMN ip_address VARCHAR(45) NULL DEFAULT NULL AFTER username_ref,
    ADD INDEX idx_concerns_ip_status (ip_address, status);

-- ---- Record migration ----
INSERT INTO _migrations (migration, batch)
SELECT '013_add_concern_ip', 1
WHERE NOT EXISTS (
    SELECT 1 FROM _migrations WHERE migration = '013_add_concern_ip'
);
