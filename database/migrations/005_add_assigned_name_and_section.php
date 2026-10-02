<?php
/**
 * Migration: Add section and assigned_name columns to assets table
 * 
 * Run: php database/migrations/005_add_assigned_name_and_section.php
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=itassets;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Running migration: Add section and assigned_name columns...\n";

// Check if section column exists
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'section'");
$sectionExists = $stmt->fetch();

if (!$sectionExists) {
    $pdo->exec("ALTER TABLE assets ADD COLUMN section VARCHAR(100) DEFAULT NULL AFTER department_id");
    echo "  ✓ Added 'section' column.\n";
} else {
    echo "  - 'section' column already exists.\n";
}

// Check if assigned_name column exists
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'assigned_name'");
$nameExists = $stmt->fetch();

if (!$nameExists) {
    $pdo->exec("ALTER TABLE assets ADD COLUMN assigned_name VARCHAR(100) DEFAULT NULL AFTER section");
    echo "  ✓ Added 'assigned_name' column.\n";
} else {
    echo "  - 'assigned_name' column already exists.\n";
}

// Migrate existing assigned_to data to assigned_name
$stmt = $pdo->query("SELECT a.id, a.assigned_to, u.full_name FROM assets a LEFT JOIN users u ON a.assigned_to = u.id WHERE a.assigned_to IS NOT NULL AND (a.assigned_name IS NULL OR a.assigned_name = '')");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($rows as $row) {
    if ($row['full_name']) {
        $update = $pdo->prepare("UPDATE assets SET assigned_name = ? WHERE id = ?");
        $update->execute([$row['full_name'], $row['id']]);
        $count++;
    }
}

if ($count > 0) {
    echo "  ✓ Migrated {$count} existing assigned_to names to assigned_name.\n";
} else {
    echo "  - No existing assigned_to data to migrate.\n";
}

echo "\nMigration completed successfully.\n";
