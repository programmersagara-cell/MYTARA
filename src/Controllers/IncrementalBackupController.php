<?php
/**
 * IncrementalBackupController
 * Incremental SQL backup of ticketing concerns + image preservation + viewer.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Concern;
use App\Services\AuditService;

class IncrementalBackupController extends Controller
{
    private ?AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        try {
            $this->auditService = new AuditService();
        } catch (\Throwable $e) {
            $this->auditService = null;
        }
    }

    /**
     * Show backup viewer (admin).
     */
    public function index(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $backups = $this->listBackups();

        $this->render('tickets/backup', [
            'title' => 'Incremental Backup Viewer',
            'backups' => $backups,
        ]);
    }

    /**
     * Trigger an incremental backup (AJAX + page).
     */
    public function run(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $result = $this->createIncrementalBackup();

        if ($this->request->isAjax()) {
            $this->json($result);
        }

        if ($result['success']) {
            $this->redirectWith('/tickets/backup', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets/backup', $result['message'], 'danger');
        }
    }

    /**
     * Download a backup SQL file.
     */
    public function download(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $file = (string) $this->request->query('file', '');
        $safeFile = basename($file);
        $base = $this->backupBaseDir();
        $full = realpath($base . '/' . $safeFile);

        if (!$full || strpos($full, realpath($base)) !== 0 || !is_file($full)) {
            $this->redirectWith('/tickets/backup', 'Invalid backup file.', 'danger');
            return;
        }

        $this->response->download($full, $safeFile);
    }

    /**
     * List incremental backups grouped by date.
     */
    private function listBackups(): array
    {
        $base = $this->backupBaseDir();
        $groups = [];

        if (!is_dir($base)) {
            return $groups;
        }

        $dirs = glob($base . '/*', GLOB_ONLYDIR);
        sort($dirs);
        foreach ($dirs as $dir) {
            $date = basename($dir);
            $files = glob($dir . '/*.sql');
            $items = [];
            if ($files) {
                sort($files);
                foreach ($files as $f) {
                    $items[] = [
                        'name' => basename($f),
                        'path' => $f,
                        'size' => filesize($f),
                        'modified' => date('Y-m-d H:i:s', filemtime($f)),
                        'records' => $this->countRecords($f),
                    ];
                }
            }
            if ($items) {
                $groups[$date] = $items;
            }
        }

        return $groups;
    }

    /**
     * Count concern INSERT records in a backup SQL file.
     */
    private function countRecords(string $filePath): int
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return 0;
        }
        return substr_count($content, 'INSERT INTO');
    }

    /**
     * Create an incremental backup of concerns changed since last backup.
     */
    private function createIncrementalBackup(): array
    {
        $db = Database::getInstance();
        $dir = $this->backupBaseDir() . '/' . date('Y-m-d');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stamp = date('His');
        $filePath = $dir . '/incremental_backup_' . date('Ymd') . '_' . $stamp . '.sql';

        // Select concerns changed since last backup OR never backed up
        $rows = $db->fetchAll(
            "SELECT * FROM concerns
             WHERE backed_up_at IS NULL
                OR backed_up_status != status
                OR IFNULL(backed_up_remarks_hash, '') != IFNULL(MD5(IFNULL(remarks, '')), '')
                OR IFNULL(backed_up_canceled_reason_hash, '') != IFNULL(MD5(IFNULL(canceled_reason, '')), '')
                OR IFNULL(backed_up_repaired_by_hash, '') != IFNULL(MD5(IFNULL(repaired_by, '')), '')"
        );

        $count = 0;
        $imageCount = 0;

        if (!empty($rows)) {
            $lines = [];
            $lines[] = '-- Incremental backup generated ' . date('Y-m-d H:i:s');
            $lines[] = 'USE `itassets`;';
            $lines[] = '';

            foreach ($rows as $row) {
                $cols = [];
                foreach ($row as $key => $val) {
                    if ($key === 'id') {
                        continue; // preserve auto-increment
                    }
                    if ($val === null) {
                        $cols[] = 'NULL';
                    } else {
                        $cols[] = "'" . $this->escapeSql($val) . "'";
                    }
                }
                $lines[] = 'INSERT INTO concerns (' . implode(', ', array_map(fn($k) => '`' . $k . '`', array_keys($row))) . ') VALUES (' . implode(', ', $cols) . ');';
                $count++;

                // Preserve image
                if (!empty($row['image_path'])) {
                    $this->preserveImage($row['image_path'], $dir);
                    $imageCount++;
                }
            }

            file_put_contents($filePath, implode("\n", $lines) . "\n");

            // Mark as backed up
            $stmt = $db->getConnection()->prepare(
                "UPDATE concerns SET
                    backed_up_at = NOW(),
                    backed_up_status = status,
                    backed_up_remarks_hash = MD5(IFNULL(remarks, '')),
                    backed_up_canceled_reason_hash = MD5(IFNULL(canceled_reason, '')),
                    backed_up_repaired_by_hash = MD5(IFNULL(repaired_by, ''))
                 WHERE id = ?"
            );
            foreach ($rows as $row) {
                $stmt->execute([$row['id']]);
            }
        }

        $this->audit('ticket_backup', null, "Incremental backup created ({$count} records, {$imageCount} images)");

        if ($count === 0) {
            return ['success' => true, 'message' => 'No changes to back up.', 'count' => 0, 'image_count' => 0];
        }

        return [
            'success' => true,
            'message' => "Backup created: {$count} records, {$imageCount} images.",
            'count' => $count,
            'image_count' => $imageCount,
            'file' => basename($filePath),
        ];
    }

    /**
     * Escape a value for SQL literal.
     */
    private function escapeSql(mixed $value): string
    {
        return str_replace(["\\", "'", "\""], ["\\\\", "\\'", "\\\""], (string) $value);
    }

    /**
     * Copy an uploaded ticket image into the backup/day/images folder.
     */
    private function preserveImage(string $imagePath, string $backupDayDir): void
    {
        $src = PUBLIC_PATH . '/uploads/' . ltrim($imagePath, '/');
        if (!is_file($src)) {
            return;
        }
        $imgDir = $backupDayDir . '/images';
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0775, true);
        }
        $dest = $imgDir . '/' . basename($imagePath);
        if (!file_exists($dest)) {
            @copy($src, $dest);
        }
    }

    /**
     * Base directory for incremental backups.
     */
    private function backupBaseDir(): string
    {
        $base = STORAGE_PATH . '/backups/incremental';
        if (!is_dir($base)) {
            mkdir($base, 0775, true);
        }
        return $base;
    }

    /**
     * Record a ticket action in the audit log (best-effort).
     */
    private function audit(string $action, ?string $reference, string $description): void
    {
        if (!$this->auditService) {
            return;
        }
        try {
            $this->auditService->logAction($action, $reference, $description);
        } catch (\Throwable $e) {
            // Swallow audit errors — the primary action already succeeded
        }
    }
}
