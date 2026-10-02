<?php
/**
 * Migration: Add pos_size column to assets table
 * Stores per-node scale/size for the network topology diagram
 */

// Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/config/constants.php';

$db = \App\Core\Database::getInstance();
$pdo = $db->getConnection();

// Check if column already exists
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'pos_size'");
$exists = $stmt->fetch();

if ($exists) {
    echo "Column 'pos_size' already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE assets ADD COLUMN pos_size FLOAT NOT NULL DEFAULT 1.0 AFTER pos_y");
    echo "Added column 'pos_size' to assets table.\n";
}

// Verify
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'pos_size'");
$col = $stmt->fetch();
print_r($col);

