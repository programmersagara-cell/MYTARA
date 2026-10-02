<?php
/**
 * Migration: Add 'vm' device type
 * Adds 'vm' to the assets.type enum so virtual machines can be distinguished
 * from physical PCs in the network topology.
 *
 * Run: php migrate_vm_type.php
 */

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

echo "Running migration: Add 'vm' device type...\n";

// Read current enum values
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'type'");
$col = $stmt->fetch();
$enumPattern = "/enum\('(.*)'\)/i";
$currentEnum = [];
if ($col && preg_match($enumPattern, $col['Type'] ?? '', $m)) {
    $currentEnum = explode("','", $m[1]);
}
echo "  Current enum: " . implode(', ', $currentEnum) . "\n";

$desiredEnum = ['pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'vm', 'other'];
if (!in_array('vm', $currentEnum, true)) {
    $enumStr = "'" . implode("','", $desiredEnum) . "'";
    $pdo->exec("ALTER TABLE assets MODIFY COLUMN `type` ENUM({$enumStr}) NOT NULL");
    echo "✓ Updated assets.type enum: " . implode(', ', $desiredEnum) . "\n";
} else {
    echo "✓ 'vm' already in enum, no change needed\n";
}

// Record migration
$pdo->exec("CREATE TABLE IF NOT EXISTS `_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$stmt = $pdo->query("SELECT COUNT(*) as c FROM _migrations WHERE migration = '009_add_vm_type'");
$exists = $stmt->fetch()['c'] > 0;
if (!$exists) {
    $pdo->exec("INSERT INTO _migrations (migration, batch) SELECT '009_add_vm_type', COALESCE(MAX(batch),0)+1 FROM _migrations");
    echo "✓ Recorded migration\n";
} else {
    echo "✓ Migration already recorded\n";
}

echo "\nMigration completed successfully.\n";
