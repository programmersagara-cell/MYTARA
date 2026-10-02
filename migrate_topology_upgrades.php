<?php
/**
 * Migration: Topology Upgrades
 *  - Creates topology_junctions table (T-junction/tap points on links)
 *  - Adds tap_id / tap_side columns to network_links
 *  - Rebuilds assets.type enum (remove 'monitor', add 'nas', 'nvr')
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

// ─── 1. topology_junctions table ───
$pdo->exec("CREATE TABLE IF NOT EXISTS topology_junctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    edge_id INT UNSIGNED NOT NULL,
    t FLOAT NOT NULL DEFAULT 0.5 COMMENT 'Position along edge (0.0 to 1.0)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edge_id) REFERENCES network_links(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_junctions_edge (edge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created topology_junctions table\n";

// ─── 2. tap_id / tap_side on network_links ───
$stmt = $pdo->query("SHOW COLUMNS FROM network_links LIKE 'tap_id'");
if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE network_links ADD COLUMN tap_id INT UNSIGNED NULL DEFAULT NULL AFTER port_target");
    echo "✓ Added tap_id column to network_links\n";
} else {
    echo "✓ tap_id already exists\n";
}

$stmt = $pdo->query("SHOW COLUMNS FROM network_links LIKE 'tap_side'");
if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE network_links ADD COLUMN tap_side VARCHAR(10) NULL DEFAULT NULL AFTER tap_id");
    echo "✓ Added tap_side column to network_links\n";
} else {
    echo "✓ tap_side already exists\n";
}

// ─── 3. Rebuild assets.type enum ───
// Convert existing monitor rows to 'other'
$pdo->exec("UPDATE assets SET type = 'other' WHERE type = 'monitor'");
echo "✓ Converted existing 'monitor' assets to 'other'\n";

// Read current enum values to detect MariaDB/MySQL differences
$stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'type'");
$col = $stmt->fetch();
$enumPattern = "/enum\('(.*)'\)/i";
$currentEnum = [];
if ($col && preg_match($enumPattern, $col['Type'] ?? '', $m)) {
    $currentEnum = explode("','", $m[1]);
}
echo "  Current enum: " . implode(', ', $currentEnum) . "\n";

$desiredEnum = ['pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'other'];
if ($currentEnum !== $desiredEnum) {
    $enumStr = "'" . implode("','", $desiredEnum) . "'";
    $pdo->exec("ALTER TABLE assets MODIFY COLUMN `type` ENUM({$enumStr}) NOT NULL");
    echo "✓ Updated assets.type enum: " . implode(', ', $desiredEnum) . "\n";
} else {
    echo "✓ Enum already up-to-date\n";
}

// ─── 4. Record migration ───
$pdo->exec("CREATE TABLE IF NOT EXISTS `_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$stmt = $pdo->query("SELECT COUNT(*) as c FROM _migrations WHERE migration = '008_add_topology_upgrades'");
$exists = $stmt->fetch()['c'] > 0;
if (!$exists) {
    $pdo->exec("INSERT INTO _migrations (migration, batch) SELECT '008_add_topology_upgrades', COALESCE(MAX(batch),0)+1 FROM _migrations");
    echo "✓ Recorded migration\n";
} else {
    echo "✓ Migration already recorded\n";
}

echo "Migration completed successfully.\n";

