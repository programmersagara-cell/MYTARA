-- Switch repair timer from preset-duration countdown to elapsed stopwatch:
-- the timer runs from accepted_at and stops when the ticket is marked as
-- repaired (repaired_date). Actual repair time = repaired_date - accepted_at.
-- The preset-duration columns are no longer used.

ALTER TABLE concerns
    DROP INDEX idx_status_completion,
    DROP COLUMN repair_duration,
    DROP COLUMN expected_completion_at;
