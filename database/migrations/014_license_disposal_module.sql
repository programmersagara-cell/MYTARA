-- ============================================================
-- Migration 014: Software License Management & Asset Disposal
--  - software_licenses table
--  - license_assignments table
--  - asset_disposals table
--  - disposal_attachments table
--  - Add 'disposed' to assets status enum
--  - Add retirement_reason to assets
-- ============================================================

-- ---- 1. Software Licenses ----
CREATE TABLE IF NOT EXISTS software_licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_name VARCHAR(200) NOT NULL,
    software_name VARCHAR(200) NOT NULL,
    vendor VARCHAR(200) DEFAULT NULL,
    license_key VARCHAR(500) DEFAULT NULL,
    license_type ENUM('per_user', 'per_device', 'volume', 'subscription', 'perpetual', 'oem', 'trial', 'other') NOT NULL DEFAULT 'perpetual',
    version VARCHAR(50) DEFAULT NULL,
    purchase_date DATE DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    expiration_date DATE DEFAULT NULL,
    purchased_seats INT UNSIGNED NOT NULL DEFAULT 1,
    used_seats INT UNSIGNED NOT NULL DEFAULT 0,
    cost DECIMAL(15,2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'PHP',
    department_id INT UNSIGNED DEFAULT NULL,
    assigned_user_id INT UNSIGNED DEFAULT NULL,
    status ENUM('active', 'expiring_soon', 'expired', 'suspended', 'retired') NOT NULL DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_licenses_status (status),
    INDEX idx_licenses_type (license_type),
    INDEX idx_licenses_dept (department_id),
    INDEX idx_licenses_expiration (expiration_date),
    INDEX idx_licenses_software (software_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- 2. License Assignments ----
CREATE TABLE IF NOT EXISTS license_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    asset_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    assigned_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removed_date DATETIME DEFAULT NULL,
    status ENUM('active', 'removed') NOT NULL DEFAULT 'active',
    assigned_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES software_licenses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_assignments_license (license_id),
    INDEX idx_assignments_asset (asset_id),
    INDEX idx_assignments_user (user_id),
    INDEX idx_assignments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
