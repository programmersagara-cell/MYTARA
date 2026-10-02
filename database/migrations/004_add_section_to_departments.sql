-- Migration 004: Add section column to departments table
ALTER TABLE departments ADD COLUMN IF NOT EXISTS `section` VARCHAR(100) DEFAULT NULL AFTER `code`;
ALTER TABLE assets ADD COLUMN IF NOT EXISTS `section` VARCHAR(100) DEFAULT NULL AFTER `department_id`;
