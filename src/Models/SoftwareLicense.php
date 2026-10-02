<?php
/**
 * Software License Model
 */

namespace App\Models;

use App\Core\Model;

class SoftwareLicense extends Model
{
    protected static string $table = 'software_licenses';
    protected static array $fillable = [
        'license_name', 'software_name', 'vendor', 'license_key', 'license_type',
        'version', 'purchase_date', 'start_date', 'expiration_date',
        'purchased_seats', 'used_seats', 'cost', 'currency',
        'department_id', 'assigned_user_id', 'assigned_name',
        'assigned_asset_id', 'assigned_asset_tag', 'status', 'notes', 'created_by'
    ];

    /**
     * Get all licenses with related department and user info
     */
    public static function getAllWithRelations(string $where = '', array $params = [], string $orderBy = 'l.created_at', string $direction = 'DESC'): array
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "SELECT l.*, 
                       d.name as department_name,
                       d.code as department_code,
                       u.full_name as assigned_user_name,
                       a.asset_tag as assigned_asset_tag_name,
                       creator.full_name as created_by_name
                FROM software_licenses l
                LEFT JOIN departments d ON l.department_id = d.id
                LEFT JOIN users u ON l.assigned_user_id = u.id
                LEFT JOIN assets a ON l.assigned_asset_id = a.id
                LEFT JOIN users creator ON l.created_by = creator.id";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $sql .= " ORDER BY {$orderBy} {$direction}";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Find license by ID with relations
     */
    public static function findWithRelations(int $id): ?array
    {
        $db = \App\Core\Database::getInstance();
        
        return $db->fetch(
            "SELECT l.*, 
                    d.name as department_name,
                    d.code as department_code,
                    u.full_name as assigned_user_name,
                    a.asset_tag as assigned_asset_tag_name,
                    creator.full_name as created_by_name
             FROM software_licenses l
             LEFT JOIN departments d ON l.department_id = d.id
             LEFT JOIN users u ON l.assigned_user_id = u.id
             LEFT JOIN assets a ON l.assigned_asset_id = a.id
             LEFT JOIN users creator ON l.created_by = creator.id
             WHERE l.id = ?",
            [$id]
        );
    }

    /**
     * Get dashboard statistics for licenses
     */
    public static function getDashboardStats(): array
    {
        $db = \App\Core\Database::getInstance();
        
        $total = self::count();
        $active = self::count("status = 'active'");
        $expiringSoon = self::count("status = 'expiring_soon'");
        $expired = self::count("status = 'expired'");
        
        $seats = $db->fetch(
            "SELECT COALESCE(SUM(purchased_seats), 0) as purchased, 
                    COALESCE(SUM(used_seats), 0) as used 
             FROM software_licenses"
        );
        
        $purchased = (int) ($seats['purchased'] ?? 0);
        $used = (int) ($seats['used'] ?? 0);
        $available = max(0, $purchased - $used);
        $utilization = $purchased > 0 ? round(($used / $purchased) * 100, 1) : 0;
        
        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
            'purchased_seats' => $purchased,
            'used_seats' => $used,
            'available_seats' => $available,
            'utilization' => $utilization,
        ];
    }

    /**
     * Get licenses expiring within a number of days
     */
    public static function getExpiringWithin(int $days): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT l.*, d.name as department_name
             FROM software_licenses l
             LEFT JOIN departments d ON l.department_id = d.id
             WHERE l.expiration_date IS NOT NULL
               AND l.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND l.status IN ('active', 'expiring_soon')
             ORDER BY l.expiration_date ASC",
            [$days]
        );
    }

    /**
     * Get licenses over capacity (used > purchased)
     */
    public static function getOverCapacity(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT l.*, d.name as department_name
             FROM software_licenses l
             LEFT JOIN departments d ON l.department_id = d.id
             WHERE l.used_seats > l.purchased_seats
             ORDER BY (l.used_seats - l.purchased_seats) DESC"
        );
    }

    /**
     * Automatically determine license status based on expiration date
     */
    public static function determineStatus(?string $expirationDate, string $currentStatus = 'active'): string
    {
        if (in_array($currentStatus, ['suspended', 'retired'])) {
            return $currentStatus;
        }
        
        if (!$expirationDate) {
            return 'active';
        }
        
        $expiry = strtotime($expirationDate);
        $now = time();
        
        if ($expiry < $now) {
            return 'expired';
        }
        
        $daysUntilExpiry = ceil(($expiry - $now) / 86400);
        
        if ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Update all license statuses based on expiration dates
     */
    public static function updateStatuses(): void
    {
        $db = \App\Core\Database::getInstance();
        $licenses = $db->fetchAll(
            "SELECT id, expiration_date, status FROM software_licenses 
             WHERE status NOT IN ('suspended', 'retired')"
        );
        
        foreach ($licenses as $license) {
            $newStatus = self::determineStatus($license['expiration_date'], $license['status']);
            if ($newStatus !== $license['status']) {
                $db->update('software_licenses', ['status' => $newStatus], 'id = ?', [$license['id']]);
            }
        }
    }

    /**
     * Search licenses
     */
    public static function searchLicenses(string $query, string $type = '', string $status = '', int $departmentId = 0): array
    {
        $db = \App\Core\Database::getInstance();
        
        $conditions = [];
        $params = [];
        
        if ($query) {
            $searchTerm = "%{$query}%";
            $conditions[] = "(l.license_name LIKE ? OR l.software_name LIKE ? OR l.vendor LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type) {
            $conditions[] = "l.license_type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $conditions[] = "l.status = ?";
            $params[] = $status;
        }
        
        if ($departmentId > 0) {
            $conditions[] = "l.department_id = ?";
            $params[] = $departmentId;
        }
        
        $where = $conditions ? implode(' AND ', $conditions) : '1=1';
        
        return self::getAllWithRelations($where, $params);
    }

    /**
     * Get active assignments for a license
     */
    public static function getActiveAssignments(int $licenseId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT la.*, 
                    a.asset_tag, a.hostname, a.type as asset_type,
                    u.full_name as user_name, u.username as user_username,
                    assigner.full_name as assigned_by_name
             FROM license_assignments la
             LEFT JOIN assets a ON la.asset_id = a.id
             LEFT JOIN users u ON la.user_id = u.id
             LEFT JOIN users assigner ON la.assigned_by = assigner.id
             WHERE la.license_id = ? AND la.status = 'active'
             ORDER BY la.assigned_date DESC",
            [$licenseId]
        );
    }

    /**
     * Get all assignments for a license (including removed)
     */
    public static function getAllAssignments(int $licenseId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT la.*, 
                    a.asset_tag, a.hostname, a.type as asset_type,
                    u.full_name as user_name, u.username as user_username,
                    assigner.full_name as assigned_by_name
             FROM license_assignments la
             LEFT JOIN assets a ON la.asset_id = a.id
             LEFT JOIN users u ON la.user_id = u.id
             LEFT JOIN users assigner ON la.assigned_by = assigner.id
             WHERE la.license_id = ?
             ORDER BY la.assigned_date DESC",
            [$licenseId]
        );
    }

    /**
     * Mask a license key for display (e.g., XXXX-XXXX-XXXX-ABCD)
     */
    public static function maskLicenseKey(?string $key): string
    {
        if (!$key) return '—';
        
        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('X', $length);
        }
        
        return 'XXXX-XXXX-XXXX-' . substr($key, -4);
    }
}