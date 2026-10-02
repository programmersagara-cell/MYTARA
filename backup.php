<?php
/**
 * Database Backup Script
 * Run: php backup.php or access via browser (admin only)
 */

// ─── Bootstrap ───
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/src/Core/Session.php';
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/Request.php';
require_once __DIR__ . '/src/Core/Response.php';

use App\Core\Session;
use App\Core\Database;

// Start session and check auth
Session::getInstance();

if (!Session::getInstance()->isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Unauthorized';
    exit;
}

$user = Session::getInstance()->getUser();
if ($user['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo 'Admin access required';
    exit;
}

// ─── Backup Configuration ───
$dbConfig = require CONFIG_PATH . '/database.php';
$backupDir = __DIR__ . '/storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

// ─── Generate Backup ───
$timestamp = date('Y-m-d_H-i-s');
$filename = "itassets_backup_{$timestamp}.sql";
$filepath = "{$backupDir}/{$filename}";

// Get database connection details
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

// Use mysqldump if available
$mysqldumpPath = trim(shell_exec('where mysqldump 2>nul') ?: '');
$backupSuccess = false;

if ($mysqldumpPath) {
    $command = sprintf(
        '"%s" --host=%s --port=%s --user=%s --password=%s %s --routines --triggers --single-transaction --add-drop-table > "%s" 2>nul',
        $mysqldumpPath,
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($database),
        escapeshellarg($filepath)
    );

    exec($command, $output, $returnCode);
    $backupSuccess = ($returnCode === 0);
}

// Fallback: PHP-based backup
if (!$backupSuccess) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $sql = "-- IT Asset Management System Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Drop table
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n\n";

            // Create table
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $sql .= $createStmt['Create Table'] . ";\n\n";

            // Insert data
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = implode('`, `', array_keys($rows[0]));
                $sql .= "INSERT INTO `{$table}` (`{$columns}`) VALUES\n";

                $values = [];
                foreach ($rows as $row) {
                    $escaped = array_map(function($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->quote($val);
                    }, $row);
                    $values[] = "(" . implode(', ', $escaped) . ")";
                }

                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
        $sql .= "-- Backup completed successfully\n";

        file_put_contents($filepath, $sql);
        $backupSuccess = true;
    } catch (\Exception $e) {
        $backupSuccess = false;
    }
}

// ─── Cleanup old backups ───
$appConfig = require CONFIG_PATH . '/app.php';
$retentionDays = $appConfig['backup']['retention_days'] ?? 30;
$files = glob("{$backupDir}/itassets_backup_*.sql");
foreach ($files as $file) {
    if (filemtime($file) < time() - ($retentionDays * 86400)) {
        unlink($file);
    }
}

// ─── Response ───
if ($backupSuccess) {
    // Log the backup to audit_log (asset_history requires a valid asset_id and
    // only supports asset-action enums; use audit_log instead)
    try {
        $db = Database::getInstance();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'backup',
            'description' => "Database backup created: {$filename}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $e) {
        // Logging must never break the download
        error_log('Failed to log backup action: ' . $e->getMessage());
    }

    // Serve the file for download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit;
} else {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Backup failed. Please check database permissions and storage directory.';
    exit;
}
