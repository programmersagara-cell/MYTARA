-- Manual assignment fields for licenses
ALTER TABLE software_licenses
    ADD COLUMN assigned_name VARCHAR(100) DEFAULT NULL AFTER assigned_user_id,
    ADD COLUMN assigned_asset_id INT UNSIGNED DEFAULT NULL AFTER assigned_name,
    ADD COLUMN assigned_asset_tag VARCHAR(50) DEFAULT NULL AFTER assigned_asset_id;

ALTER TABLE software_licenses
    ADD FOREIGN KEY (assigned_asset_id) REFERENCES assets(id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD INDEX idx_licenses_asset (assigned_asset_id);
