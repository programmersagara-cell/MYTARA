<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use App\Models\AssetHistory;
use App\Models\SoftwareLicense;
use App\Models\LicenseAssignment;
use App\Models\AssetDisposal;
use App\Services\DiagramService;

class ApiController extends Controller
{
    /**
     * Search assets - used by select2/autocomplete
     */
    public function searchAssets(): void
    {
        $query = $this->request->query('q', '');
        $type = $this->request->query('type', '');
        $status = $this->request->query('status', '');
        $deptId = (int) $this->request->query('department_id', 0);

        $assets = Asset::searchAssets($query, $type, $status, $deptId);

        $result = array_map(function ($asset) {
            return [
                'id' => $asset['id'],
                'text' => $asset['asset_tag'] . ' - ' . ($asset['hostname'] ?: 'No hostname'),
                'asset_tag' => $asset['asset_tag'],
                'hostname' => $asset['hostname'],
                'type' => $asset['type'],
                'ip_address' => $asset['ip_address'],
            ];
        }, $assets);

        $this->json(['results' => $result]);
    }

    /**
     * Search users for dropdowns
     */
    public function searchUsers(): void
    {
        $query = $this->request->query('q', '');
        $users = User::search($query);

        $result = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'text' => $user['full_name'] . ' (' . $user['username'] . ')',
            ];
        }, $users);

        $this->json(['results' => $result]);
    }

    /**
     * Get departments for dropdown
     */
    public function getDepartments(): void
    {
        $departments = Department::getOptions();
        $result = array_map(function ($dept) {
            return [
                'id' => $dept['id'],
                'text' => $dept['name'] . ' (' . $dept['code'] . ')',
            ];
        }, $departments);

        $this->json(['results' => $result]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats(): void
    {
        $stats = Asset::getDashboardStats();
        $byType = Asset::getCountByType();
        $byDepartment = Asset::getCountByDepartment();
        $recentActivity = AssetHistory::getRecent(10);
        $monthlyAdditions = Asset::getMonthlyAdditions();

        $this->json([
            'stats' => $stats,
            'by_type' => $byType,
            'by_department' => $byDepartment,
            'recent_activity' => $recentActivity,
            'monthly_additions' => $monthlyAdditions,
        ]);
    }

    /**
     * Get a single asset by ID (API)
     */
    public function getAsset(int $id): void
    {
        $asset = Asset::findWithRelations($id);
        if (!$asset) {
            $this->json(['error' => 'Asset not found'], 404);
            return;
        }
        $this->json(['data' => $asset]);
    }

    /**
     * Save topology positions (API)
     */
    public function savePositions(): void
    {
        // Topology writes are admin-only (viewers are read-only).
        if (($this->currentUser['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Insufficient permissions.'], 403);
            return;
        }
        $positions = $this->request->json();
        if (!$positions) {
            $this->json(['error' => 'Invalid data'], 400);
            return;
        }

        $diagramService = new DiagramService();
        $diagramService->savePositions($positions);
        $this->json(['success' => true]);
    }

    /**
     * Export assets as JSON
     */
    public function exportAssets(): void
    {
        $type = $this->request->query('type', '');
        $status = $this->request->query('status', '');
        $deptId = (int) $this->request->query('department_id', 0);

        $where = [];
        $params = [];

        if ($type) { $where[] = "a.type = ?"; $params[] = $type; }
        if ($status) { $where[] = "a.status = ?"; $params[] = $status; }
        if ($deptId) { $where[] = "a.department_id = ?"; $params[] = $deptId; }

        $whereClause = $where ? implode(' AND ', $where) : '1=1';
        $assets = Asset::getAllWithRelations($whereClause, $params);

        $this->json(['data' => $assets, 'exported_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * List software licenses (API)
     */
    public function apiListLicenses(): void
    {
        $licenses = SoftwareLicense::all();
        
        // Mask license keys for security
        foreach ($licenses as &$license) {
            $license['license_key'] = $license['license_key'] 
                ? 'XXXX-XXXX-XXXX-' . substr($license['license_key'], -4) 
                : '';
        }
        unset($license);

        $this->json(['data' => $licenses]);
    }

    /**
     * Get a single license (API)
     */
    public function apiGetLicense(int $id): void
    {
        $license = SoftwareLicense::find($id);
        if (!$license) {
            $this->json(['error' => 'License not found'], 404);
            return;
        }

        // Mask license key
        $license['license_key'] = $license['license_key']
            ? 'XXXX-XXXX-XXXX-' . substr($license['license_key'], -4)
            : '';

        $assignments = LicenseAssignment::getByLicense($id);
        $this->json(['data' => $license, 'assignments' => $assignments]);
    }

    /**
     * List asset disposals (API)
     */
    public function apiListDisposals(): void
    {
        $disposals = AssetDisposal::all();
        $this->json(['data' => $disposals]);
    }

    /**
     * Get a single disposal (API)
     */
    public function apiGetDisposal(int $id): void
    {
        $disposal = AssetDisposal::findWithRelations($id);
        if (!$disposal) {
            $this->json(['error' => 'Disposal not found'], 404);
            return;
        }
        $this->json(['data' => $disposal]);
    }
}
