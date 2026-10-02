<?php
/**
 * CSV Export Service
 */

namespace App\Services;

use App\Models\Asset;

class ExportService
{
    /**
     * Export assets to CSV
     */
    public function exportAssetsCsv(array $filters = []): string
    {
        $assets = Asset::searchAssets(
            $filters['search'] ?? '',
            $filters['type'] ?? '',
            $filters['status'] ?? '',
            (int) ($filters['department_id'] ?? 0)
        );

        $headers = [
            'Asset Tag', 'Type', 'Hostname', 'IP Address', 'MAC Address',
            'Vendor', 'Model', 'Serial Number', 'OS', 'CPU', 'RAM (GB)', 'Storage (GB)',
            'Status', 'Location', 'Floor', 'Department', 'Section', 'Assigned To',
            'Purchase Date', 'Warranty End', 'Notes'
        ];

        $output = fopen('php://temp', 'w+');
        fputcsv($output, $headers);

        foreach ($assets as $asset) {
            fputcsv($output, [
                $asset['asset_tag'],
                $asset['type'],
                $asset['hostname'],
                $asset['ip_address'],
                $asset['mac_address'],
                $asset['vendor'],
                $asset['model'],
                $asset['serial_number'],
                $asset['os'],
                $asset['cpu'],
                $asset['ram_gb'],
                $asset['storage_gb'],
                $asset['status'],
                $asset['location'],
                $asset['floor'],
                $asset['department_name'] ?? '',
                $asset['section'] ?? '',
                $asset['assigned_name'] ?? ($asset['assigned_user_name'] ?? ''),
                $asset['purchase_date'] ?? '',
                $asset['warranty_end'] ?? '',
                $asset['notes'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate a sample CSV for import template
     */
    public function generateSampleCsv(): string
    {
        $headers = [
            'asset_tag', 'type', 'hostname', 'ip_address', 'mac_address',
            'vendor', 'model', 'serial_number', 'os', 'cpu', 'ram_gb', 'storage_gb',
            'status', 'location', 'floor', 'notes'
        ];

        $sample = [
            'PC-SAMPLE-001', 'pc', 'PC-SAMPLE-01', '192.168.1.100', 'AA:BB:CC:DD:EE:FF',
            'Dell', 'OptiPlex 7090', 'SN-SAMPLE-001', 'Windows 11 Pro', 'Intel Core i7', '32', '512',
            'active', 'Main Office', '2nd Floor', 'Sample entry'
        ];

        $output = fopen('php://temp', 'w+');
        fputcsv($output, $headers);
        fputcsv($output, $sample);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Import assets from CSV
     */
    public function importFromCsv(string $filePath, int $createdBy): array
    {
        $errors = [];
        $imported = 0;
        $skipped = 0;

        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'File not found.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Could not open file.'];
        }

        // Read header
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'message' => 'Invalid CSV format.'];
        }

        $headers = array_map('trim', $headers);
        $requiredFields = ['asset_tag', 'type'];
        $validTypes = ['pc', 'laptop', 'switch', 'server', 'printer', 'monitor', 'other'];

        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $data = array_combine($headers, $row);

            // Validate required fields
            $missing = [];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                $errors[] = "Line {$lineNumber}: Missing required fields: " . implode(', ', $missing);
                $skipped++;
                continue;
            }

            // Validate type
            if (!in_array(strtolower($data['type']), $validTypes)) {
                $errors[] = "Line {$lineNumber}: Invalid type '{$data['type']}'. Allowed: " . implode(', ', $validTypes);
                $skipped++;
                continue;
            }

            // Check duplicate asset tag
            $existing = \App\Core\Database::getInstance()->fetch(
                "SELECT id FROM assets WHERE asset_tag = ?",
                [$data['asset_tag']]
            );

            if ($existing) {
                $errors[] = "Line {$lineNumber}: Asset tag '{$data['asset_tag']}' already exists.";
                $skipped++;
                continue;
            }

            // Prepare insert data
            $insertData = [
                'asset_tag' => $data['asset_tag'],
                'type' => strtolower($data['type']),
                'hostname' => $data['hostname'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'mac_address' => $data['mac_address'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'os' => $data['os'] ?? null,
                'cpu' => $data['cpu'] ?? null,
                'ram_gb' => $data['ram_gb'] ? (int) $data['ram_gb'] : null,
                'storage_gb' => $data['storage_gb'] ? (int) $data['storage_gb'] : null,
                'status' => $data['status'] ?? 'active',
                'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
                'assigned_to' => !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
                'location' => $data['location'] ?? null,
                'floor' => $data['floor'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ];

            try {
                Asset::create($insertData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line {$lineNumber}: {$e->getMessage()}";
                $skipped++;
            }
        }

        fclose($handle);

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Imported {$imported} assets, skipped {$skipped}.",
        ];
    }
}
