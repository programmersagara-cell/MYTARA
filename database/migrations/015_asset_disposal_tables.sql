-- ============================================================
-- Migration 015: Asset Disposal Tables
--  - asset_disposals table
--  - disposal_attachments table
--  - Add 'disposed' to assets status enum
--  - Add retirement_reason to assets
-- ============================================================

-- ---- 1. Asset Disposals ----
CREATE TABLE IF NOT EXISTS asset_disposals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    retirement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    retirement_reason ENUM('end_of_life', 'hardware_failure', 'beyond_repair', 'obsolete', 'warranty_expired', 'replacement', 'lost', 'damaged', 'security_risk', 'upgrade', 'other') NOT NULL DEFAULT 'end_of_life',
    disposal_status ENUM('pending_disposal', 'awaiting_approval', 'approved', 'scheduled', 'disposed', 'rejected', 'cancelled', 'archived') NOT NULL DEFAULT 'pending_disposal',
    disposal_method ENUM('e_waste_recycling', 'physical_destruction', 'data_destruction', 'vendor_return', 'resale', 'donation', 'internal_transfer', 'parts_recovery', 'other') DEFAULT NULL,
    disposal_date DATETIME DEFAULT NULL,
    approved_by INT UNSIGNED DEFAULT NULL,
    approval_date DATETIME DEFAULT NULL,
    disposed_by INT UNSIGNED DEFAULT NULL,
    disposal_location VARCHAR(200) DEFAULT NULL,
    disposal_vendor VARCHAR(200) DEFAULT NULL,
    certificate_number VARCHAR(100) DEFAULT NULL,
    data_destruction_required TINYINT(1) NOT NULL DEFAULT 0,
    data_destruction_method ENUM('secure_erase', 'disk_wiping', 'cryptographic_erase', 'physical_destruction', 'factory_reset', 'not_applicable') DEFAULT NULL,
    data_destruction_date DATETIME DEFAULT NULL,
    data_destruction_by INT UNSIGNED DEFAULT NULL,
    data_destruction_verified TINYINT(1) NOT NULL DEFAULT 0,
    resale_value DECIMAL(15,2) DEFAULT NULL,
    disposal_cost DECIMAL(15,2) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (disposed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (data_destruction_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uk_disposal_asset (asset_id),
    INDEX idx_disposals_status (disposal_status),
    INDEX idx_disposals_date (retirement_date),
    INDEX idx_disposals_reason (retirement_reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- 2. Disposal Attachments ----
CREATE TABLE IF NOT EXISTS disposal_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disposal_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disposal_id) REFERENCES asset_disposals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_attachments_disposal (disposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- 3. Add 'disposed' to assets status enum ----
ALTER TABLE assets 
    MODIFY COLUMN status ENUM('active', 'inactive', 'maintenance', 'retired', 'lost', 'reserved', 'disposed') NOT NULL DEFAULT 'active';

-- ---- 4. Add retirement_reason to assets ----
ALTER TABLE assets
    ADD COLUMN retirement_reason VARCHAR(50) DEFAULT NULL AFTER status,
    ADD COLUMN retired_at DATETIME DEFAULT NULL AFTER retirement_reason;

-- ---- 5. Add license notification settings defaults ----
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
SELECT 'license_expiry_thresholds', '90,60,30,7', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'license_expiry_thresholds');

INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
SELECT 'company_name', 'IT Services and Asset Management', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'company_name');

-- ---- Record migration ----
INSERT INTO _migrations (migration, batch)
SELECT '015_asset_disposal_tables', 1
WHERE NOT EXISTS (
    SELECT 1 FROM _migrations WHERE migration = '015_asset_disposal_tables'
);