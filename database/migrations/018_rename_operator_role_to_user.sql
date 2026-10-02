-- Migration 018: Rename the "operator" role to "user" (displayed as "Users")
-- 1. Widen the role ENUM to include both values.
-- 2. Migrate existing operator accounts to 'user'.
-- 3. Shrink the ENUM to the final set of roles.

ALTER TABLE `users`
    MODIFY `role` ENUM('admin','user','operator','viewer') NOT NULL DEFAULT 'viewer';

UPDATE `users` SET `role` = 'user' WHERE `role` = 'operator';

ALTER TABLE `users`
    MODIFY `role` ENUM('admin','user','viewer') NOT NULL DEFAULT 'viewer';