<?php
/**
 * Migration: Add section column to departments and assets tables
 */

require_once __DIR__ . '/config/constants.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=itassets;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Add section to departments
    $pdo->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS `section` VARCHAR(100) DEFAULT NULL AFTER `code`");
    echo "✓ Added section column to departments table\n";

    // Add section to assets
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS `section` VARCHAR(100) DEFAULT NULL AFTER `department_id`");
    echo "✓ Added section column to assets table\n";

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

