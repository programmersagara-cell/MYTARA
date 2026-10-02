<?php
/**
 * Asset Disposal Model
 */

namespace App\Models;

use App\Core\Model;

class AssetDisposal extends Model
{
    protected static string $table = 'asset_disposals';
    protected static array $fillable = [
        'asset_id', 'retirement_date', 'retirement_reason', 'disposal_status',
        'disposal_method', 'disposal_date', 'approved_by', 'approval_date',
        'disposed_by', 'disposal_location', 'disposal_vendor', 'certificate_number',
        'data_destruction_required', 'data_destruction_method', 'data_destruction_date',
        'data_destruction_by', 'data_destruction_verified', 'resale_value',
        'disposal_cost', 'notes', 'created_by'
    ];

    /**
     * Get all disposals with related asset and user info
     */
    public static function getAllWithRelations(string $where = '', array $params = [], string $orderBy = 'd.created_at', string $direction = 'DESC'): array
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "SELECT d.*, 
                       a.asset_tag, a.hostname, a.type as asset_type, a.serial_number,
                       a.department_id, a.assigned_name,
                       dept.name as department_name,
                       u.full_name as assigned_user_name,
                       approver.full_name as approved_by_name,
                       disposer.full_name as disposed_by_name,
                       dd.full_name as data_destruction_by_name,
                       creator.full_name as created_by_name
                FROM asset_disposals d
                LEFT JOIN assets a ON d.asset_id = a.id
                LEFT JOIN departments dept ON a.department_id = dept.id
                LEFT JOIN users u ON a.assigned_to = u.id
                LEFT JOIN users approver ON d.approved_by = approver.id
                LEFT JOIN users disposer ON d.disposed_by = disposer.id
                LEFT JOIN users dd ON d.data_destruction_by = dd.id
                LEFT JOIN users creator ON d.created_by = creator.id";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $sql .= " ORDER BY {$orderBy} {$direction}";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Find disposal by ID with relations
     */
    public static function findWithRelations(int $id): ?array
    {
        $db = \App\Core\Database::getInstance();
        
        return $db->fetch(
            "SELECT d.*, 
                    a.asset_tag, a.hostname, a.type as asset_type, a.serial_number,
                    a.department_id, a.assigned_name, a.vendor, a.model,
                    a.purchase_date, a.notes as asset_notes,
                    dept.name as department_name,
                    u.full_name as assigned_user_name,
                    approver.full_name as approved_by_name,
                    disposer.full_name as disposed_by_name,
                    dd.full_name as data_destruction_by_name,
                    creator.full_name as created_by_name
             FROM asset_disposals d
             LEFT JOIN assets a ON d.asset_id = a.id
             LEFT JOIN departments dept ON a.department_id = dept.id
             LEFT JOIN users u ON a.assigned_to = u.id
             LEFT JOIN users approver ON d.approved_by = approver.id
             LEFT JOIN users disposer ON d.disposed_by = disposer.id
             LEFT JOIN users dd ON d.data_destruction_by = dd.id
             LEFT JOIN users creator ON d.created_by = creator.id
             WHERE d.id = ?",
            [$id]
        );
    }

    /**
     * Find disposal by asset ID
     */
    public static function findByAssetId(int $assetId): ?array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetch(
            "SELECT * FROM asset_disposals WHERE asset_id = ?",
            [$assetId]
        );
    }

    /**
     * Get dashboard statistics for disposals
     */
    public static function getDashboardStats(): array
    {
        $db = \App\Core\Database::getInstance();
        
        $pending = self::count("disposal_status = 'pending_disposal'");
        $awaiting = self::count("disposal_status = 'awaiting_approval'");
        $approved = self::count("disposal_status = 'approved'");
        $scheduled = self::count("disposal_status = 'scheduled'");
        $disposed = self::count("disposal_status = 'disposed'");
        $cancelled = self::count("disposal_status = 'cancelled'");
        
        return [
            'pending' => $pending,
            'awaiting_approval' => $awaiting,
            'approved' => $approved,
            'scheduled' => $scheduled,
            'disposed' => $disposed,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Get retired assets that don't have a disposal record yet
     */
    public static function getRetiredWithoutDisposal(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT a.*, d.name as department_name,
                    u.full_name as assigned_user_name
             FROM assets a
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN users u ON a.assigned_to = u.id
             LEFT JOIN asset_disposals ad ON a.id = ad.asset_id
             WHERE a.status = 'retired' AND ad.id IS NULL
             ORDER BY a.updated_at DESC"
        );
    }

    /**
     * Get all retired assets (with or without disposal records)
     */
    public static function getRetiredAssets(string $where = '', array $params = []): array
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "SELECT a.*, 
                       d.name as department_name,
                       u.full_name as assigned_user_name,
                       ad.id as disposal_id,
                       ad.disposal_status,
                       ad.disposal_date,
                       ad.retirement_reason,
                       ad.retirement_date as disposal_retirement_date
                FROM assets a
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN users u ON a.assigned_to = u.id
                LEFT JOIN asset_disposals ad ON a.id = ad.asset_id
                WHERE a.status = 'retired'";
        
        if ($where) {
            $sql .= " AND {$where}";
        }
        
        $sql .= " ORDER BY a.updated_at DESC";
        
        return $db->fetchAll($sql, $params);
    }

    /**
     * Get disposal status counts for dashboard
     */
    public static function getStatusCounts(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT disposal_status, COUNT(*) as count 
             FROM asset_disposals 
             GROUP BY disposal_status"
        );
    }

    /**
     * Get disposal attachments
     */
    public static function getAttachments(int $disposalId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT da.*, u.full_name as uploaded_by_name
             FROM disposal_attachments da
             LEFT JOIN users u ON da.uploaded_by = u.id
             WHERE da.disposal_id = ?
             ORDER BY da.created_at DESC",
            [$disposalId]
        );
    }

    /**
     * Add attachment to disposal
     */
    public static function addAttachment(int $disposalId, array $fileData, int $uploadedBy): int
    {
        $db = \App\Core\Database::getInstance();
        return $db->insert('disposal_attachments', [
            'disposal_id' => $disposalId,
            'filename' => $fileData['filename'],
            'original_name' => $fileData['original_name'],
            'file_type' => $fileData['file_type'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
            'uploaded_by' => $uploadedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if asset has an active disposal record
     */
    public static function hasActiveDisposal(int $assetId): bool
    {
        return self::count(
            "asset_id = ? AND disposal_status NOT IN ('disposed', 'cancelled', 'rejected', 'archived')",
            [$assetId]
        ) > 0;
    }

    /**
     * Get disposal history for an asset
     */
    public static function getHistoryForAsset(int $assetId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT d.*, 
                    approver.full_name as approved_by_name,
                    disposer.full_name as disposed_by_name
             FROM asset_disposals d
             LEFT JOIN users approver ON d.approved_by = approver.id
             LEFT JOIN users disposer ON d.disposed_by = disposer.id
             WHERE d.asset_id = ?
             ORDER BY d.created_at DESC",
            [$assetId]
        );
    }
}