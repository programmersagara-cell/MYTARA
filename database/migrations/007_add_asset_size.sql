-- Migration 007: Add pos_size column to assets table
-- Stores per-node scale/size for the network topology diagram
ALTER TABLE assets ADD COLUMN IF NOT EXISTS `pos_size` FLOAT NOT NULL DEFAULT 1.0 AFTER `pos_y`;

