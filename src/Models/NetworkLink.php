<?php
/**
 * Network Link Model
 * Represents connections between devices in the network topology
 */

namespace App\Models;

use App\Core\Model;

class NetworkLink extends Model
{
    protected static string $table = 'network_links';
    protected static array $fillable = [
        'source_id', 'target_id', 'link_type', 'port_source', 'port_target', 'tap_id', 'tap_side'
    ];
    protected static bool $timestamps = false;

    /**
     * Get all links with device info
     */
    public static function getAllWithDevices(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT l.*,
                    s.asset_tag as source_tag, s.hostname as source_hostname, s.type as source_type, s.ip_address as source_ip,
                    t.asset_tag as target_tag, t.hostname as target_hostname, t.type as target_type, t.ip_address as target_ip
             FROM network_links l
             LEFT JOIN assets s ON l.source_id = s.id
             LEFT JOIN assets t ON l.target_id = t.id
             ORDER BY l.created_at ASC"
        );
    }

    /**
     * Get links connected to a specific device
     */
    public static function getByDevice(int $assetId): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT l.*,
                    CASE WHEN l.source_id = ? THEN t.id ELSE s.id END as connected_id,
                    CASE WHEN l.source_id = ? THEN t.hostname ELSE s.hostname END as connected_hostname,
                    CASE WHEN l.source_id = ? THEN t.type ELSE s.type END as connected_type,
                    CASE WHEN l.source_id = ? THEN t.ip_address ELSE s.ip_address END as connected_ip,
                    CASE WHEN l.source_id = ? THEN l.port_source ELSE l.port_target END as connected_port
             FROM network_links l
             LEFT JOIN assets s ON l.source_id = s.id
             LEFT JOIN assets t ON l.target_id = t.id
             WHERE l.source_id = ? OR l.target_id = ?
             ORDER BY l.created_at ASC",
            [$assetId, $assetId, $assetId, $assetId, $assetId, $assetId, $assetId]
        );
    }

    /**
     * Delete links for a specific asset
     */
    public static function deleteByDevice(int $assetId): int
    {
        $db = \App\Core\Database::getInstance();
        return $db->delete(
            'network_links',
            'source_id = ? OR target_id = ?',
            [$assetId, $assetId]
        );
    }

    /**
     * Check if a link already exists between two devices
     */
    public static function linkExists(int $sourceId, int $targetId): bool
    {
        $db = \App\Core\Database::getInstance();
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM network_links 
             WHERE (source_id = ? AND target_id = ?) 
                OR (source_id = ? AND target_id = ?)",
            [$sourceId, $targetId, $targetId, $sourceId]
        );
        return $result['count'] > 0;
    }

    /**
     * Create a junction (tap point) on an existing edge
     */
    public static function createJunction(int $edgeId, float $t): int
    {
        $db = \App\Core\Database::getInstance();
        return (int) $db->insert('topology_junctions', [
            'edge_id' => $edgeId,
            't' => max(0.0, min(1.0, $t)),
        ]);
    }

    /**
     * Get all junctions with their host edge info
     */
    public static function getAllJunctions(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT j.*, l.link_type
             FROM topology_junctions j
             LEFT JOIN network_links l ON j.edge_id = l.id
             ORDER BY j.created_at ASC"
        );
    }

    /**
     * Get a junction by id
     */
    public static function findJunction(int $id): ?array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetch(
            "SELECT * FROM topology_junctions WHERE id = ?",
            [$id]
        );
    }
}

