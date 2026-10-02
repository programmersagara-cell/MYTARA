-- Migration 001: Initial Database Setup
-- This migration creates the complete database schema for IT Asset Management System

-- Check if migration already ran
CREATE TABLE IF NOT EXISTS `_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run the full schema (idempotent - uses IF NOT EXISTS)
SOURCE ../schema.sql;

-- Record this migration
INSERT INTO `_migrations` (`migration`, `batch`) VALUES ('001_initial', 1);

