<?php
/**
 * Asset History / Audit Log Model
 */

namespace App\Models;

use App\Core\Model;
use App\Core\Request;

class AssetHistory extends Model
{
    protected static string $table = 'asset_history';
    protected static array $fillable = [
        'asset_id', 'user_id', 'action', 'field_changed', 
        'old_value', 'new_value', 'ip_address'
    ];
    protected static bool $timestamps = false;

    /**
     * Log an action on an asset
     *
     * $userId is nullable: the `user_id` column accepts NULL (FK ON DELETE SET
     * NULL) and writing 0 for "no session user" violates the foreign key.
     */
    public static function log(int $assetId, ?int $userId, string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null): int
    {
        return self::create([
            'asset_id' => $assetId,
            'user_id' => $userId,
            'action' => $action,
            'field_changed' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => (new Request())->ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get history with related asset and user info
     */
    public static function getWithRelations(int $limit = 50, int $offset = 0): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT h.*, 
                    a.asset_tag, a.hostname, a.type as asset_type,
                    u.full_name as user_name, u.username as user_username,
                    u.avatar as user_avatar
             FROM asset_history h
             LEFT JOIN assets a ON h.asset_id = a.id
             LEFT JOIN users u ON h.user_id = u.id
             ORDER BY h.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get history for a specific asset
     */
    public static function getByAsset(int $assetId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT h.*, u.full_name as user_name, u.username as user_username
             FROM asset_history h
             LEFT JOIN users u ON h.user_id = u.id
             WHERE h.asset_id = ?
             ORDER BY h.created_at DESC
             LIMIT 50",
            [$assetId]
        );
    }

    /**
     * Get recent activity for dashboard
     */
    public static function getRecent(int $limit = 10): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT h.*, 
                    a.asset_tag, a.hostname, a.type as asset_type,
                    u.full_name as user_name
             FROM asset_history h
             LEFT JOIN assets a ON h.asset_id = a.id
             LEFT JOIN users u ON h.user_id = u.id
             ORDER BY h.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get history summary counts
     */
    public static function getActionCounts(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT action, COUNT(*) as count 
             FROM asset_history 
             GROUP BY action 
             ORDER BY count DESC"
        );
    }
}

