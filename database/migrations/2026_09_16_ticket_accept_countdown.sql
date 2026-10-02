-- Ticket Acceptance & Repair Countdown
-- Adapts the existing status enum: 'active' = PENDING, 'accepted' = ACCEPTED
-- (repair in progress), 'repaired' = COMPLETED, 'canceled' = CANCELLED.
-- Existing rows keep status='active' (PENDING) — no data is modified or lost.

ALTER TABLE concerns
    MODIFY status ENUM('active','accepted','repaired','canceled') NOT NULL DEFAULT 'active',
    ADD COLUMN accepted_at TIMESTAMP NULL DEFAULT NULL AFTER submitted_at,
    ADD COLUMN accepted_by VARCHAR(100) NULL DEFAULT NULL AFTER accepted_at,
    ADD COLUMN repair_duration INT NULL DEFAULT NULL AFTER accepted_by,
    ADD COLUMN expected_completion_at TIMESTAMP NULL DEFAULT NULL AFTER repair_duration,
    ADD INDEX idx_status_completion (status, expected_completion_at);
