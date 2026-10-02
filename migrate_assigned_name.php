<?php
/**
 * Migration: Add assigned_name free-text column to assets table
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
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'assigned_name'");
$exists = $stmt->fetch();

if ($exists) {
    echo "Column 'assigned_name' already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE assets ADD COLUMN assigned_name VARCHAR(100) DEFAULT NULL AFTER assigned_to");
    echo "Added column 'assigned_name' to assets table.\n";
}

// Verify
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'assigned_name'");
$col = $stmt->fetch();
print_r($col);

