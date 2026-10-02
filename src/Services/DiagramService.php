<?php
/**
 * Network Diagram Service
 * Handles topology layout and calculations
 */

namespace App\Services;

use App\Models\Asset;
use App\Models\NetworkLink;
use App\Models\Concern;

class DiagramService
{
    /**
     * Get all topology data (nodes + links)
     */
    public function getTopologyData(): array
    {
        $assets = Asset::getTopologyAssets();
        $links = NetworkLink::getAllWithDevices();
        $labels = $this->getLabels();

        // Map of IP -> active concern count (for red highlight on topology nodes)
        $activeConcernCounts = Concern::activeCountByIp();

        // Format nodes
        $nodes = array_map(function ($asset) use ($activeConcernCounts) {
            $ip = $asset['ip_address'] ?: '';
            return [
                'id' => (int) $asset['id'],
                'label' => $asset['hostname'] ?: $asset['asset_tag'],
                'asset_tag' => $asset['asset_tag'] ?: '',
                'type' => $asset['type'],
                'ip' => $ip,
                'mac' => $asset['mac_address'] ?: '',
                'status' => $asset['status'],
                'user' => $asset['assigned_user_name'] ?: ($asset['assigned_name'] ?? ''),
                'x' => (float) ($asset['pos_x'] ?: $this->getDefaultPosition($asset['type'])['x']),
                'y' => (float) ($asset['pos_y'] ?: $this->getDefaultPosition($asset['type'])['y']),
                'size' => (float) ($asset['pos_size'] ?? 1.0),
                'concernCount' => ($ip !== '' && isset($activeConcernCounts[$ip])) ? (int) $activeConcernCounts[$ip] : 0,
            ];
        }, $assets);

        // Format edges
        $edges = array_map(function ($link) {
            return [
                'id' => (int) $link['id'],
                'source' => (int) $link['source_id'],
                'target' => (int) $link['target_id'],
                'type' => $link['link_type'],
                'sourcePort' => $link['port_source'] ?: '',
                'targetPort' => $link['port_target'] ?: '',
                'tapId' => $link['tap_id'] ? (int) $link['tap_id'] : null,
                'tapSide' => $link['tap_side'] ?: null,
            ];
        }, $links);

        // Format junctions (tap points on edges)
        $junctions = array_map(function ($j) {
            return [
                'id' => (int) $j['id'],
                'edgeId' => (int) $j['edge_id'],
                't' => (float) $j['t'],
            ];
        }, NetworkLink::getAllJunctions());

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'labels' => $labels,
            'junctions' => $junctions,
        ];
    }

    /**
     * Save a junction (tap point) on an edge
     */
    public function saveJunction(array $data): int
    {
        return NetworkLink::createJunction((int) $data['edge_id'], (float) $data['t']);
    }

    /**
     * Save node positions
     */
    public function savePositions(array $positions): void
    {
        foreach ($positions as $assetId => $pos) {
            // Skip malformed entries instead of throwing (defensive: the
            // client always sends {id: {x, y, size?}} maps).
            if (!is_array($pos) || !isset($pos['x'], $pos['y']) || !is_numeric($pos['x']) || !is_numeric($pos['y']) || !is_numeric($assetId)) {
                continue;
            }
            $data = ['pos_x' => (float) $pos['x'], 'pos_y' => (float) $pos['y']];
            if (isset($pos['size'])) {
                $data['pos_size'] = (float) $pos['size'];
            }
            \App\Core\Database::getInstance()->update(
                'assets',
                $data,
                'id = ?',
                [(int) $assetId]
            );
        }
    }

    /**
     * Get all topology labels
     */
    public function getLabels(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll("SELECT * FROM topology_labels ORDER BY created_at ASC");
    }

    /**
     * Save a topology label
     */
    public function saveLabel(array $data): int
    {
        $db = \App\Core\Database::getInstance();
        
        if (isset($data['id']) && $data['id'] > 0) {
            $db->update('topology_labels', [
                'text' => $data['text'],
                'x' => $data['x'],
                'y' => $data['y'],
                'font_size' => $data['font_size'] ?? 16,
                'color' => $data['color'] ?? '#64748B',
            ], 'id = ?', [(int) $data['id']]);
            return (int) $data['id'];
        } else {
            return (int) $db->insert('topology_labels', [
                'text' => $data['text'],
                'x' => $data['x'],
                'y' => $data['y'],
                'font_size' => $data['font_size'] ?? 16,
                'color' => $data['color'] ?? '#64748B',
            ]);
        }
    }

    /**
     * Delete a topology label
     */
    public function deleteLabel(int $id): void
    {
        $db = \App\Core\Database::getInstance();
        $db->delete('topology_labels', 'id = ?', [$id]);
    }

    /**
     * Save label positions (batch update)
     */
    public function saveLabelPositions(array $labels): void
    {
        $db = \App\Core\Database::getInstance();
        foreach ($labels as $label) {
            if (!isset($label['id']) || !isset($label['x']) || !isset($label['y'])) continue;
            $db->update('topology_labels', [
                'x' => $label['x'],
                'y' => $label['y'],
            ], 'id = ?', [(int) $label['id']]);
        }
    }

    /**
     * Get default position based on device type
     */
    private function getDefaultPosition(string $type): array
    {
        return match ($type) {
            'switch' => ['x' => 400, 'y' => 100],
            'server' => ['x' => 400, 'y' => 400],
            'pc' => ['x' => 200, 'y' => 250],
            'laptop' => ['x' => 600, 'y' => 250],
            'printer' => ['x' => 400, 'y' => 550],
            'nas' => ['x' => 300, 'y' => 500],
            'nvr' => ['x' => 600, 'y' => 500],
            default => ['x' => 300, 'y' => 300],
        };
    }

    /**
     * Get device icon SVG path (relative)
     */
    public static function getDeviceIcon(string $type): string
    {
        return match ($type) {
            'pc' => '/public/assets/img/devices/pc.svg',
            'laptop' => '/public/assets/img/devices/laptop.svg',
'switch' => '/public/assets/img/devices/switch.svg',
            'server' => '/public/assets/img/devices/server.svg',
            'printer' => '/public/assets/img/devices/printer.svg',
            'nas' => '/public/assets/img/devices/nas.svg',
            'nvr' => '/public/assets/img/devices/nvr.svg',
            'vm' => '/public/assets/img/devices/server.svg',
            default => '/public/assets/img/devices/device.svg',
        };
    }

    /**
     * Get color for device type
     */
    public static function getTypeColor(string $type): string
    {
        return match ($type) {
            'pc' => '#3B82F6', // blue
            'laptop' => '#8B5CF6', // violet
            'switch' => '#10B981', // green
            'server' => '#F59E0B', // amber
            'printer' => '#EC4899', // pink
'nas' => '#14B8A6', // teal
            'nvr' => '#EF4444', // red
'vm' => '#93C5FD', // light blue
            default => '#6B7280', // gray
        };
    }
}

