-- =============================================
-- IT Asset Management System - Database Schema
-- Database: itassets
-- =============================================

CREATE DATABASE IF NOT EXISTS itassets
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE itassets;

-- =============================================
-- Users Table
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user', 'viewer') NOT NULL DEFAULT 'viewer',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Departments Table
-- =============================================
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Assets Table
-- =============================================
CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'other') NOT NULL,
    hostname VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    mac_address VARCHAR(17) DEFAULT NULL,
    vendor VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL UNIQUE,
    os VARCHAR(100) DEFAULT NULL,
    cpu VARCHAR(100) DEFAULT NULL,
    ram_gb INT UNSIGNED DEFAULT NULL,
    storage_gb INT UNSIGNED DEFAULT NULL,
    status ENUM('active', 'inactive', 'maintenance', 'retired', 'lost', 'reserved') NOT NULL DEFAULT 'active',
    purchase_date DATE DEFAULT NULL,
    warranty_end DATE DEFAULT NULL,
    location VARCHAR(200) DEFAULT NULL,
    floor VARCHAR(50) DEFAULT NULL,
    department_id INT UNSIGNED DEFAULT NULL,
    assigned_to INT UNSIGNED DEFAULT NULL,
    assigned_name VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    qr_code VARCHAR(255) DEFAULT NULL,
    pos_x FLOAT DEFAULT NULL,
    pos_y FLOAT DEFAULT NULL,
    pos_size FLOAT NOT NULL DEFAULT 1.0,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_assets_type (type),
    INDEX idx_assets_status (status),
    INDEX idx_assets_dept (department_id),
    INDEX idx_assets_assigned (assigned_to),
    INDEX idx_assets_hostname (hostname),
    INDEX idx_assets_ip (ip_address),
    INDEX idx_assets_location (location(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Asset History / Audit Log
-- =============================================
CREATE TABLE IF NOT EXISTS asset_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    action ENUM('created', 'updated', 'deleted', 'assigned', 'transferred', 'maintenance', 'retired') NOT NULL,
    field_changed VARCHAR(50) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_history_asset (asset_id),
    INDEX idx_history_user (user_id),
    INDEX idx_history_action (action),
    INDEX idx_history_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Audit Log (for authentication actions)
-- =============================================
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Topology Junctions (T-Junction / Tap Points on links)
-- =============================================
CREATE TABLE IF NOT EXISTS topology_junctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    edge_id INT UNSIGNED NOT NULL,
    t FLOAT NOT NULL DEFAULT 0.5 COMMENT 'Position along edge (0.0 to 1.0)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edge_id) REFERENCES network_links(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_junctions_edge (edge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Network Links (Topology Connections)
-- =============================================
CREATE TABLE IF NOT EXISTS network_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id INT UNSIGNED NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    link_type ENUM('ethernet', 'fiber', 'wifi', 'virtual') NOT NULL DEFAULT 'ethernet',
    port_source VARCHAR(20) DEFAULT NULL,
    port_target VARCHAR(20) DEFAULT NULL,
    tap_id INT UNSIGNED NULL DEFAULT NULL,
    tap_side VARCHAR(10) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES assets(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (target_id) REFERENCES assets(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tap_id) REFERENCES topology_junctions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_links_source (source_id),
    INDEX idx_links_target (target_id),
    INDEX idx_links_tap (tap_id),
    UNIQUE KEY uk_link (source_id, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Topology Labels (Text annotations on the network diagram)
-- =============================================
CREATE TABLE IF NOT EXISTS topology_labels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(200) NOT NULL,
    x FLOAT NOT NULL DEFAULT 0,
    y FLOAT NOT NULL DEFAULT 0,
    font_size INT UNSIGNED NOT NULL DEFAULT 16,
    color VARCHAR(20) NOT NULL DEFAULT '#64748B',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- System Settings
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
