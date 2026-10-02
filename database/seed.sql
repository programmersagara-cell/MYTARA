-- =============================================
-- IT Asset Management System - Seed Data
-- =============================================

-- Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`) VALUES
('admin', 'admin@itassets.local', '$2y$10$zl1ElgHFSP5HoiG5MrFxgOtu7X9MkElRpG1LUA4G/4BFuHdBOMtGq', 'System Administrator', 'admin', 1, NOW()),
('user', 'user@itassets.local', '$2y$10$zl1ElgHFSP5HoiG5MrFxgOtu7X9MkElRpG1LUA4G/4BFuHdBOMtGq', 'IT User', 'user', 1, NOW()),
('viewer', 'viewer@itassets.local', '$2y$10$zl1ElgHFSP5HoiG5MrFxgOtu7X9MkElRpG1LUA4G/4BFuHdBOMtGq', 'Read Only User', 'viewer', 1, NOW());

-- Default Departments
INSERT INTO `departments` (`name`, `code`, `description`, `created_at`, `updated_at`) VALUES
('Information Technology', 'IT', 'Information Technology Department', NOW(), NOW()),
('Human Resources', 'HR', 'Human Resources Department', NOW(), NOW()),
('Finance', 'FIN', 'Finance and Accounting Department', NOW(), NOW()),
('Engineering', 'ENG', 'Engineering Department', NOW(), NOW()),
('Marketing', 'MKT', 'Marketing Department', NOW(), NOW()),
('Operations', 'OPS', 'Operations Department', NOW(), NOW());

-- Sample Assets
INSERT INTO `assets` (`asset_tag`, `type`, `hostname`, `ip_address`, `mac_address`, `vendor`, `model`, `serial_number`, `os`, `cpu`, `ram_gb`, `storage_gb`, `status`, `location`, `floor`, `department_id`, `assigned_to`, `notes`, `created_by`, `pos_x`, `pos_y`, `created_at`) VALUES
('PC-001', 'pc', 'IT-WORKSTATION-01', '192.168.1.101', 'AA:BB:CC:DD:EE:01', 'Dell', 'OptiPlex 7090', 'SN-DELL-001', 'Windows 11 Pro', 'Intel Core i7-11700', 32, 512, 'active', 'Main Office', '2nd Floor', 1, 1, 'Primary development workstation', 1, 200, 300, NOW()),
('PC-002', 'pc', 'IT-WORKSTATION-02', '192.168.1.102', 'AA:BB:CC:DD:EE:02', 'HP', 'EliteDesk 800', 'SN-HP-002', 'Windows 10 Pro', 'Intel Core i5-10500', 16, 256, 'active', 'Main Office', '2nd Floor', 1, 2, 'Secondary IT workstation', 1, 400, 300, NOW()),
('LAP-001', 'laptop', 'HR-LAPTOP-01', '192.168.1.201', 'AA:BB:CC:DD:EE:03', 'Lenovo', 'ThinkPad X1 Carbon', 'SN-LEN-003', 'Windows 11 Pro', 'Intel Core i7-1165G7', 16, 512, 'active', 'HR Office', '1st Floor', 2, NULL, 'HR Manager laptop', 1, 300, 200, NOW()),
('LAP-002', 'laptop', 'ENG-LAPTOP-01', '192.168.1.202', 'AA:BB:CC:DD:EE:04', 'Apple', 'MacBook Pro 14"', 'SN-APP-004', 'macOS Ventura', 'Apple M2 Pro', 16, 512, 'active', 'Engineering', '3rd Floor', 4, NULL, 'Senior engineer laptop', 1, 500, 200, NOW()),
('SW-001', 'switch', 'CORE-SWITCH-01', '192.168.1.1', 'AA:BB:CC:DD:EE:05', 'Cisco', 'Catalyst 9200', 'SN-CISCO-005', 'Cisco IOS', NULL, NULL, NULL, 'active', 'Server Room', 'Ground Floor', 1, NULL, 'Core network switch - 48 ports', 1, 350, 450, NOW()),
('SW-002', 'switch', 'FLOOR-SWITCH-01', '192.168.1.2', 'AA:BB:CC:DD:EE:06', 'Netgear', 'GS324TP', 'SN-NET-006', 'Netgear OS', NULL, NULL, NULL, 'active', 'Main Office', '2nd Floor', 1, NULL, 'Floor switch - 24 ports PoE+', 1, 150, 150, NOW()),
('SRV-001', 'server', 'DC-PRIMARY-01', '192.168.1.10', 'AA:BB:CC:DD:EE:07', 'Dell', 'PowerEdge R740', 'SN-SRV-007', 'Windows Server 2022', 'Intel Xeon Gold 6248', 128, 4096, 'active', 'Server Room', 'Ground Floor', 1, NULL, 'Primary domain controller', 1, 450, 550, NOW()),
('PRN-001', 'printer', 'FLOOR-PRINTER-01', '192.168.1.150', 'AA:BB:CC:DD:EE:08', 'HP', 'LaserJet Pro M404', 'SN-PRN-008', 'HP Firmware', NULL, NULL, NULL, 'active', 'Main Office', '2nd Floor', 1, NULL, 'Network printer - 2nd floor', 1, 250, 100, NOW());

-- Network Links (Topology Connections)
INSERT INTO `network_links` (`source_id`, `target_id`, `link_type`, `port_source`, `port_target`) VALUES
(5, 1, 'ethernet', 'Gi1/0/1', 'eth0'),
(5, 2, 'ethernet', 'Gi1/0/2', 'eth0'),
(5, 6, 'ethernet', 'Gi1/0/10', 'Gi1/0/1'),
(6, 3, 'ethernet', 'Gi1/0/5', 'eth0'),
(5, 7, 'ethernet', 'Gi1/0/24', 'eth0'),
(6, 8, 'ethernet', 'Gi1/0/10', 'eth0'),
(5, 4, 'ethernet', 'Gi1/0/3', 'eth0');

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app.name', 'IT Asset Management System'),
('app.timezone', 'Asia/Manila'),
('company.name', 'ACME Corporation'),
('notify.expiry_days', '30'),
('backup.enabled', 'true'),
('backup.interval', 'daily');
