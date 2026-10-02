<?php
/**
 * Asset Model
 */

namespace App\Models;

use App\Core\Model;

class Asset extends Model
{
    protected static string $table = 'assets';
    protected static array $fillable = [
        'asset_tag', 'type', 'hostname', 'ip_address', 'mac_address',
        'vendor', 'model', 'serial_number', 'os', 'cpu', 'ram_gb', 'storage_gb',
        'status', 'purchase_date', 'warranty_end', 'location', 'floor',
        'department_id', 'section', 'assigned_to', 'assigned_name', 'notes', 'qr_code', 'pos_x', 'pos_y', 'pos_size',
        'created_by'
    ];

    /**
     * Get assets with related department and assigned user
     */
    public static function getAllWithRelations(string $where = '', array $params = [], string $orderBy = 'a.created_at', string $direction = 'DESC'): array
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "SELECT a.*, 
                       d.name as department_name, 
                       d.code as department_code,
                       u.full_name as assigned_user_name,
                       u.username as assigned_user_username,
                       creator.full_name as created_by_name
                FROM assets a
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN users u ON a.assigned_to = u.id
                LEFT JOIN users creator ON a.created_by = creator.id";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $sql .= " ORDER BY {$orderBy} {$direction}";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Find asset by ID with relations
     */
    public static function findWithRelations(int $id): ?array
    {
        $db = \App\Core\Database::getInstance();
        
        return $db->fetch(
            "SELECT a.*, 
                    d.name as department_name, 
                    d.code as department_code,
                    u.full_name as assigned_user_name,
                    u.username as assigned_user_username,
                    u.email as assigned_user_email,
                    creator.full_name as created_by_name
             FROM assets a
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN users u ON a.assigned_to = u.id
             LEFT JOIN users creator ON a.created_by = creator.id
             WHERE a.id = ?",
            [$id]
        );
    }

    /**
     * Get assets grouped by type for dashboard
     */
    public static function getCountByType(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT type, COUNT(*) as count 
             FROM assets 
             GROUP BY type 
             ORDER BY count DESC"
        );
    }

    /**
     * Get assets grouped by status
     */
    public static function getCountByStatus(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM assets 
             GROUP BY status 
             ORDER BY count DESC"
        );
    }

    /**
     * Get assets grouped by department
     */
    public static function getCountByDepartment(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT d.name as department, COUNT(a.id) as count 
             FROM assets a 
             LEFT JOIN departments d ON a.department_id = d.id 
             GROUP BY d.id, d.name 
             ORDER BY count DESC"
        );
    }

    /**
     * Get monthly asset additions for charts
     */
    public static function getMonthlyAdditions(int $months = 12): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                    COUNT(*) as count 
             FROM assets 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) 
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
             ORDER BY month ASC",
            [$months]
        );
    }

    /**
     * Get recent assets for dashboard
     */
    public static function getRecent(int $limit = 10): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT a.*, u.full_name as assigned_user_name
             FROM assets a
             LEFT JOIN users u ON a.assigned_to = u.id
             ORDER BY a.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get assets for network topology
     */
    public static function getTopologyAssets(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT a.id, a.asset_tag, a.type, a.hostname, a.ip_address, 
                    a.pos_x, a.pos_y, a.pos_size, a.status, a.mac_address,
                    a.assigned_name,
                    u.full_name as assigned_user_name
             FROM assets a
             LEFT JOIN users u ON a.assigned_to = u.id
WHERE a.type IN ('pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'vm')
             AND a.status != 'retired'
             ORDER BY a.type, a.hostname"
        );
    }

    /**
     * Get dashboard statistics
     */
    public static function getDashboardStats(): array
    {
        $db = \App\Core\Database::getInstance();
        
        $total = self::count();
        $active = self::count("status = 'active'");
        $maintenance = self::count("status = 'maintenance'");
        $retired = self::count("status = 'retired'");
        
        return [
            'total' => $total,
            'active' => $active,
            'maintenance' => $maintenance,
            'retired' => $retired,
        ];
    }

    /**
     * Search assets by various fields
     */
    public static function searchAssets(string $query, string $type = '', string $status = '', int $departmentId = 0): array
    {
        $db = \App\Core\Database::getInstance();
        
        $conditions = [];
        $params = [];
        
        if ($query) {
            $searchTerm = "%{$query}%";
            $conditions[] = "(a.asset_tag LIKE ? OR a.hostname LIKE ? OR a.ip_address LIKE ? 
                             OR a.serial_number LIKE ? OR a.vendor LIKE ? OR a.model LIKE ? 
                             OR u.full_name LIKE ? OR a.assigned_name LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type) {
            $conditions[] = "a.type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $conditions[] = "a.status = ?";
            $params[] = $status;
        }
        
        if ($departmentId > 0) {
            $conditions[] = "a.department_id = ?";
            $params[] = $departmentId;
        }
        
        $where = $conditions ? implode(' AND ', $conditions) : '1=1';
        
        return self::getAllWithRelations($where, $params);
    }

    /**
     * Generate the next asset tag in the org standard format `{ORG}-{YY}-{DEPT}-{NNNN}`
     * (e.g. `O-26-IT-4821`) — the year segment uses the LAST TWO digits of the
     * acquisition year.
     *
     *  - ORG : `asset.tag.org` setting (default 'O'), editable on the Settings page.
     *  - YY  : last two digits of the acquisition year ($year accepts a full
     *          4-digit year; empty/invalid falls back to the current year).
     *  - DEPT: departments.code of $departmentId, or 'GEN' when no department is set.
     *  - NNNN: 4 random digits 1000-9999 via random_int().
     *
     * Uniqueness is checked against `assets.asset_tag` for up to 30 random attempts;
     * if all collide, the highest existing sequence for the prefix +1 is used so
     * generation can never fail. A final race (two admins on the create form) is
     * caught by the UNIQUE-constraint retry in AssetController::store().
     */
    public static function generateAssetTag(?int $departmentId = null, ?string $year = null): string
    {
        $db = \App\Core\Database::getInstance();

        $org = strtoupper(trim(Setting::get('asset.tag.org', 'O') ?? 'O'));
        if ($org === '') {
            $org = 'O';
        }

        $code = 'GEN';
        if ($departmentId) {
            $department = Department::find($departmentId);
            if ($department && trim((string) ($department['code'] ?? '')) !== '') {
                $code = strtoupper(trim($department['code']));
            }
        }

        // Accept a full 4-digit year (from purchase_date / the create form) and
        // keep only the last two digits; fall back to the current year.
        $y = (is_string($year) && preg_match('/^(19|20)\d{2}$/', $year)) ? substr($year, -2) : date('y');

        // Up to 30 random 4-digit attempts, each verified against the UNIQUE column.
        for ($i = 0; $i < 30; $i++) {
            $tag = sprintf('%s-%s-%s-%04d', $org, $y, $code, random_int(1000, 9999));
            $taken = $db->fetch("SELECT 1 FROM assets WHERE asset_tag = ?", [$tag]);
            if (!$taken) {
                return $tag;
            }
        }

        // Extremely unlikely: every random slot collided. Fall back to the highest
        // existing sequence for this prefix +1 so generation can never fail.
        $prefix = sprintf('%s-%s-%s-', $org, $y, $code);
        $row = $db->fetch(
            "SELECT MAX(CAST(SUBSTRING(asset_tag, ?) AS UNSIGNED)) AS max_seq
             FROM assets
             WHERE asset_tag LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        );
        $seq = ((int) ($row['max_seq'] ?? 0)) + 1;
        if ($seq < 1000) {
            $seq = 1000;
        }

        return sprintf('%s%04d', $prefix, $seq);
    }

    /**
     * @deprecated Use generateAssetTag() — the org standard format
     *             ({ORG}-{YYYY}-{DEPT}-{NNNN}) no longer depends on asset type.
     *             Kept as a thin wrapper so old call sites keep working.
     */
    public static function nextAssetTag(string $type): string
    {
        return self::generateAssetTag();
    }
}

