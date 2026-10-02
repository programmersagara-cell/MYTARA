-- ============================================================
-- Migration 010: Add Ticketing (IT Concern) Module to itassets
-- Merges TicketingSystem2's login_system schema into itassets.
-- Preserves all data; normalizes relationships.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Add 'department' column to users (for ticketing department mapping)
-- ------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN department VARCHAR(100) NULL AFTER role,
    ADD KEY idx_users_department (department);

-- ------------------------------------------------------------
-- 2. concerns table (IT tickets)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS concerns (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL COMMENT 'Unified user id (maps from username)',
    `username_ref` varchar(30) DEFAULT NULL COMMENT 'Original username for audit',
    `ticket_number` varchar(30) DEFAULT NULL,
    `sender_name` varchar(100) NOT NULL,
    `department` varchar(50) NOT NULL COMMENT 'Department display name',
    `department_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to departments (nullable, best-effort match)',
    `description` text NOT NULL,
    `image_path` varchar(255) DEFAULT NULL,
    `status` enum('active','repaired','canceled') DEFAULT 'active',
    `priority` enum('Critical','High','Medium','Low') DEFAULT 'Medium',
    `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `repaired_date` timestamp NULL DEFAULT NULL,
    `repaired_by` varchar(100) DEFAULT NULL,
    `canceled_reason` text DEFAULT NULL,
    `canceled_date` timestamp NULL DEFAULT NULL,
    `canceled_by` varchar(100) DEFAULT NULL,
    `remarks` text DEFAULT NULL,
    `backed_up_at` timestamp NULL DEFAULT NULL,
    `backed_up_status` varchar(20) DEFAULT NULL,
    `backed_up_remarks_hash` varchar(32) DEFAULT NULL,
    `backed_up_canceled_reason_hash` varchar(32) DEFAULT NULL,
    `backed_up_repaired_by_hash` varchar(32) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_concerns_ticket_number` (`ticket_number`),
    KEY `idx_concerns_status` (`status`),
    KEY `idx_concerns_user` (`user_id`),
    KEY `idx_concerns_department` (`department_id`),
    KEY `idx_concerns_submitted` (`submitted_at`),
    CONSTRAINT `fk_concerns_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_concerns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. instructions table (admin key notes)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS instructions (
    `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
    `tittle` varchar(100) DEFAULT NULL,
    `instruction_text` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_instructions_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. ticket_sequences table (ticket number generation)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_sequences (
    `date_key` varchar(8) NOT NULL,
    `next_seq` int(11) NOT NULL,
    PRIMARY KEY (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. notifications table (internal ticketing notifications)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trouble_notifications (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL COMMENT 'Unified user id',
    `username_ref` varchar(30) DEFAULT NULL,
    `concern_id` int(11) unsigned DEFAULT NULL,
    `ticket_number` varchar(30) DEFAULT NULL,
    `department` varchar(50) DEFAULT NULL,
    `type` varchar(30) NOT NULL DEFAULT 'email',
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `read_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tn_user` (`user_id`),
    KEY `idx_tn_concern` (`concern_id`),
    KEY `idx_tn_read` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. concern_submissions (idempotency guard)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS concern_submissions (
    `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL,
    `username_ref` varchar(30) DEFAULT NULL,
    `request_key` varchar(64) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `concern_id` int(11) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_request` (`user_id`,`request_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. concern_submission_tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS concern_submission_tokens (
    `token` varchar(64) NOT NULL,
    `user_id` int(11) unsigned DEFAULT NULL,
    `username_ref` varchar(30) DEFAULT NULL,
    `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`token`),
    UNIQUE KEY `uniq_user_token` (`user_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. email_queue (SMTP queue)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_queue (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `to_email` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `body` text NOT NULL,
    `status` enum('queued','sending','sent','failed') DEFAULT 'queued',
    `attempts` int(11) NOT NULL DEFAULT 0,
    `max_attempts` int(11) NOT NULL DEFAULT 5,
    `available_at` datetime NOT NULL DEFAULT current_timestamp(),
    `sent_at` datetime DEFAULT NULL,
    `last_error` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_email_queue_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. meta table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meta (
    `name` varchar(100) NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record migration
INSERT INTO _migrations (migration, batch) VALUES ('010_add_ticketing_module', 1);

