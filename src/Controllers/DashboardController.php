<?php
/**
 * Dashboard Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\User;
use App\Models\Department;
use App\Models\SoftwareLicense;
use App\Models\AssetDisposal;

class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
public function index(): void
    {
        if (!$this->requireRole('admin', 'viewer')) {
            return;
        }
        // Get statistics
        $stats = Asset::getDashboardStats();
        $typeCounts = Asset::getCountByType();
        $statusCounts = Asset::getCountByStatus();
        $deptCounts = Asset::getCountByDepartment();
        $monthlyAdditions = Asset::getMonthlyAdditions(6);
        $recentAssets = Asset::getRecent(5);
        $recentActivity = AssetHistory::getRecent(10);
        $userStats = User::getStats();

        // Update license statuses based on expiration dates
        SoftwareLicense::updateStatuses();

        // Get license and disposal statistics
        $licenseStats = SoftwareLicense::getDashboardStats();
        $licenseExpiring90 = SoftwareLicense::getExpiringWithin(90);
        $licenseExpiring60 = SoftwareLicense::getExpiringWithin(60);
        $licenseExpiring30 = SoftwareLicense::getExpiringWithin(30);
        $licenseOverCapacity = SoftwareLicense::getOverCapacity();
        $disposalStats = AssetDisposal::getDashboardStats();
        $retiredAssets = AssetDisposal::getRetiredWithoutDisposal();

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'typeCounts' => $typeCounts,
            'statusCounts' => $statusCounts,
            'deptCounts' => $deptCounts,
            'monthlyAdditions' => $monthlyAdditions,
            'recentAssets' => $recentAssets,
            'recentActivity' => $recentActivity,
            'userStats' => $userStats,
            'licenseStats' => $licenseStats,
            'licenseExpiring90' => $licenseExpiring90,
            'licenseExpiring60' => $licenseExpiring60,
            'licenseExpiring30' => $licenseExpiring30,
            'licenseOverCapacity' => $licenseOverCapacity,
            'disposalStats' => $disposalStats,
            'retiredAssets' => $retiredAssets,
            'extraScripts' => ['dashboard.js'],
        ]);
    }
}
