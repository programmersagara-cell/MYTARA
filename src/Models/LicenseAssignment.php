<?php
/**
 * License Assignment Model
 */

namespace App\Models;

use App\Core\Model;

class LicenseAssignment extends Model
{
    protected static string $table = 'license_assignments';
    protected static array $fillable = [
        'license_id', 'asset_id', 'user_id', 'assigned_name', 'assigned_tag',
        'assigned_date', 'removed_date', 'status', 'assigned_by'
    ];

    /**
     * Assign a license to a user
     */
    public static function assignToUser(int $licenseId, int $userId, int $assignedBy): int
    {
        return self::create([
            'license_id' => $licenseId,
            'user_id' => $userId,
            'assigned_by' => $assignedBy,
            'status' => 'active',
            'assigned_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Assign a license to a manually typed name that could not be
     * resolved to a user record (e.g. an external or shared account).
     */
    public static function assignToManualName(int $licenseId, string $name, int $assignedBy): int
    {
        return self::create([
            'license_id' => $licenseId,
            'user_id' => null,
            'assigned_name' => $name,
            'assigned_by' => $assignedBy,
            'status' => 'active',
            'assigned_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Assign a license to an asset
     */
    public static function assignToAsset(int $licenseId, int $assetId, int $assignedBy): int
    {
        return self::create([
            'license_id' => $licenseId,
            'asset_id' => $assetId,
            'assigned_by' => $assignedBy,
            'status' => 'active',
            'assigned_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Assign a license to a manually typed asset tag that could not be
     * resolved to an asset record.
     */
    public static function assignToManualTag(int $licenseId, string $tag, int $assignedBy): int
    {
        return self::create([
            'license_id' => $licenseId,
            'asset_id' => null,
            'assigned_tag' => $tag,
            'assigned_by' => $assignedBy,
            'status' => 'active',
            'assigned_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove an assignment
     */
    public static function removeAssignment(int $assignmentId): int
    {
        return self::update($assignmentId, [
            'status' => 'removed',
            'removed_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all assignments for a license
     */
    public static function getByLicense(int $licenseId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT la.*, 
                    u.full_name AS user_name, u.username AS user_username,
                    a.asset_tag, a.hostname AS asset_hostname
             FROM license_assignments la
             LEFT JOIN users u ON u.id = la.user_id
             LEFT JOIN assets a ON a.id = la.asset_id
             WHERE la.license_id = ?
             ORDER BY la.created_at DESC",
            [$licenseId]
        ) ?: [];
    }

    /**
     * Count active assignments for a license
     */
    public static function countActiveForLicense(int $licenseId): int
    {
        return self::count("license_id = ? AND status = 'active'", [$licenseId]);
    }

    /**
     * Check if a license is assigned to a specific user
     */
    public static function isAssignedToUser(int $licenseId, int $userId): bool
    {
        return self::count("license_id = ? AND user_id = ? AND status = 'active'", [$licenseId, $userId]) > 0;
    }

    /**
     * Check if a license is assigned to a specific asset
     */
    public static function isAssignedToAsset(int $licenseId, int $assetId): bool
    {
        return self::count("license_id = ? AND asset_id = ? AND status = 'active'", [$licenseId, $assetId]) > 0;
    }

    /**
     * Check if a license already has an active manual (free-text) name assignment
     */
    public static function isAssignedToManualName(int $licenseId, string $name): bool
    {
        return self::count(
            "license_id = ? AND user_id IS NULL AND asset_id IS NULL
             AND LOWER(assigned_name) = LOWER(?) AND status = 'active'",
            [$licenseId, $name]
        ) > 0;
    }

    /**
     * Check if a license already has an active manual (free-text) tag assignment
     */
    public static function isAssignedToManualTag(int $licenseId, string $tag): bool
    {
        return self::count(
            "license_id = ? AND user_id IS NULL AND asset_id IS NULL
             AND LOWER(assigned_tag) = LOWER(?) AND status = 'active'",
            [$licenseId, $tag]
        ) > 0;
    }
}