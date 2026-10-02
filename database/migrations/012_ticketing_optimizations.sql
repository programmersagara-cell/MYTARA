-- ============================================================
-- Migration 012: Ticketing Module Optimizations
--  - Add department column to ticket_sequences (for per-dept daily counters)
--  - Add performance indexes to ticketing tables
-- ============================================================

-- Add department to ticket_sequences
ALTER TABLE ticket_sequences
    ADD COLUMN department VARCHAR(20) NULL DEFAULT 'TICKET' AFTER date_key;

-- Migrate existing sequences to department column (default TICKET)
UPDATE ticket_sequences SET department = 'TICKET' WHERE department IS NULL OR department = '';

-- Rebuild unique key to include department (date_key + department)
ALTER TABLE ticket_sequences DROP PRIMARY KEY;
ALTER TABLE ticket_sequences ADD PRIMARY KEY (date_key, department);

-- ---- Performance indexes ----

-- concerns: composite index for common filters
ALTER TABLE concerns
    ADD INDEX idx_concerns_status_dept (status, department),
    ADD INDEX idx_concerns_user_status (user_id, status),
    ADD INDEX idx_concerns_repair_date (repaired_date);

-- trouble_notifications: composite unread lookup
ALTER TABLE trouble_notifications
    ADD INDEX idx_tn_user_read (user_id, read_at);

-- ---- Record migration ----
INSERT INTO _migrations (migration, batch)
SELECT '012_ticketing_optimizations', 1
WHERE NOT EXISTS (
    SELECT 1 FROM _migrations WHERE migration = '012_ticketing_optimizations'
);

