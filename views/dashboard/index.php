<?php $layout = 'layouts/main'; ?>
<?php
// Use controller-passed data if available, otherwise fetch directly
$stats = $stats ?? \App\Models\Asset::getDashboardStats();
$byType = $typeCounts ?? \App\Models\Asset::getCountByType();
$byStatus = $statusCounts ?? \App\Models\Asset::getCountByStatus();
$byDept = $deptCounts ?? \App\Models\Asset::getCountByDepartment();
$monthly = $monthlyAdditions ?? \App\Models\Asset::getMonthlyAdditions(6);
$activity = $recentActivity ?? \App\Models\AssetHistory::getRecent(10);
$recent = $recentAssets ?? \App\Models\Asset::getRecent(5);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of your IT asset inventory</p>
    </div>
<div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <a href="<?= url('/assets/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Asset
        </a>
        <?php endif; ?>
        <a href="<?= url('/topology') ?>" class="btn btn-secondary">
            <i class="fas fa-project-diagram"></i> View Topology
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-soft">
            <i class="fas fa-server"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['total']) ?></span>
            <span class="stat-label">Total Assets</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['active']) ?></span>
            <span class="stat-label">Active</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['maintenance']) ?></span>
            <span class="stat-label">In Maintenance</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-danger-soft">
            <i class="fas fa-archive"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['retired']) ?></span>
            <span class="stat-label">Retired</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-4">
        <div class="card">
            <div class="card-header">
                <h3>Assets by Type</h3>
            </div>
            <div class="card-body">
                <canvas id="typeChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-header">
                <h3>Assets by Status</h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-header">
                <h3>Assets by Department</h3>
            </div>
            <div class="card-body">
                <canvas id="deptChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-7">
        <div class="card">
            <div class="card-header">
                <h3>Monthly Additions</h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-5">
        <div class="card">
            <div class="card-header">
                <h3>Recent Activity</h3>
            </div>
            <div class="card-body p-0">
                <div class="activity-timeline">
                    <?php foreach ($activity as $entry): ?>
                    <div class="activity-item">
                        <div class="activity-icon bg-<?= match($entry['action']) {
                            'created' => 'success',
                            'deleted' => 'danger',
                            'updated' => 'info',
                            default => 'secondary'
                        } ?>-soft">
                            <i class="fas fa-<?= match($entry['action']) {
                                'created' => 'plus',
                                'deleted' => 'trash',
                                'updated' => 'edit',
                                default => 'circle'
                            } ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">
                                <strong><?= htmlspecialchars($entry['user_name'] ?? 'System') ?></strong>
                                <?= $entry['action'] ?> 
                                <strong><?= htmlspecialchars($entry['asset_tag'] ?? '') ?></strong>
                            </p>
                            <span class="activity-time"><?= \App\Helpers\Format::relativeTime($entry['created_at']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($activity)): ?>
                    <div class="text-center text-muted p-3">No recent activity</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- License Statistics -->
<?php if (isset($licenseStats)): ?>
<div class="page-header mt-5">
    <div>
        <h2 class="page-title">Software License Overview</h2>
        <p class="page-subtitle">License utilization and expiration monitoring</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/licenses') ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-key"></i> Manage Licenses
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-soft">
            <i class="fas fa-file-contract"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['total'] ?? 0) ?></span>
            <span class="stat-label">Total Licenses</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['active'] ?? 0) ?></span>
            <span class="stat-label">Active</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['expiring_soon'] ?? 0) ?></span>
            <span class="stat-label">Expiring Soon</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-danger-soft">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['expired'] ?? 0) ?></span>
            <span class="stat-label">Expired</span>
        </div>
    </div>
</div>

<div class="stats-grid mt-2">
    <div class="stat-card">
        <div class="stat-icon bg-info-soft">
            <i class="fas fa-chair"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['purchased_seats'] ?? 0) ?></span>
            <span class="stat-label">Available Seats</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-secondary-soft">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['used_seats'] ?? 0) ?></span>
            <span class="stat-label">Used Seats</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-tachometer-alt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($licenseStats['available_seats'] ?? 0) ?></span>
            <span class="stat-label">Usage</span>
        </div>
    </div>
</div>

<?php if (!empty($licenseExpiring30)): ?>
<div class="alert alert-warning mt-3">
    <i class="fas fa-exclamation-triangle"></i>
    <strong><?= count($licenseExpiring30) ?> license(s) expire within 30 days!</strong>
</div>
<?php endif; ?>
<?php if (!empty($licenseOverCapacity)): ?>
<div class="alert alert-danger mt-3">
    <i class="fas fa-exclamation-circle"></i>
    <strong><?= count($licenseOverCapacity) ?> license(s) are over capacity!</strong>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Disposal Statistics -->
<?php if (isset($disposalStats)): ?>
<div class="page-header mt-5">
    <div>
        <h2 class="page-title">Disposal Overview</h2>
        <p class="page-subtitle">Asset disposal and retirement tracking</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/disposals') ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-recycle"></i> View Disposals
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($disposalStats['pending'] ?? 0) ?></span>
            <span class="stat-label">Pending Disposal</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info-soft">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($disposalStats['awaiting_approval'] ?? 0) ?></span>
            <span class="stat-label">Awaiting Approval</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-primary-soft">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($disposalStats['scheduled'] ?? 0) ?></span>
            <span class="stat-label">Scheduled</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($disposalStats['disposed'] ?? 0) ?></span>
            <span class="stat-label">Disposed</span>
        </div>
    </div>
</div>

<?php if (!empty($retiredAssets)): ?>
<div class="alert alert-danger mt-3">
    <i class="fas fa-exclamation-triangle"></i>
    <strong><?= count($retiredAssets) ?> asset(s) pending disposal</strong> - 
    <a href="<?= url('/assets/retired') ?>" class="alert-link">Click here to review</a>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
// Pass data to dashboard.js
window.chartData = {
    byType: <?= json_encode($byType) ?>,
    byStatus: <?= json_encode($byStatus) ?>,
    byDept: <?= json_encode($byDept) ?>,
    monthly: <?= json_encode($monthly) ?>
};
</script>
