-- Repaired By / Assigned Admin linkage
-- accepted_by_id / repaired_by_id store the admin's unique user ID so the
-- name can always be resolved from the users table (rename-safe).

ALTER TABLE concerns
    ADD COLUMN accepted_by_id INT UNSIGNED NULL DEFAULT NULL AFTER accepted_by,
    ADD COLUMN repaired_by_id INT UNSIGNED NULL DEFAULT NULL AFTER repaired_by;
