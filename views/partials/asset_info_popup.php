<?php
/**
 * Asset Info Popup Template
 * Used in the Network Topology view when clicking a device node.
 * 
 * Expected variables:
 * @var int $assetId
 * @var string $assetTag
 * @var string $type
 * @var string $hostname
 * @var string $ip
 * @var string $mac
 * @var string $user
 * @var string $status
 * @var string $dept
 */
?>
<div class="popup-asset-info">
    <div class="popup-header">
        <div class="popup-device-icon">
            <?php if ($type === 'pc'): ?>
                <i class="fas fa-desktop" style="color: #3B82F6;"></i>
            <?php elseif ($type === 'laptop'): ?>
                <i class="fas fa-laptop" style="color: #8B5CF6;"></i>
            <?php elseif ($type === 'switch'): ?>
                <i class="fas fa-network-wired" style="color: #10B981;"></i>
            <?php elseif ($type === 'server'): ?>
                <i class="fas fa-server" style="color: #F59E0B;"></i>
            <?php else: ?>
                <i class="fas fa-microchip" style="color: #6B7280;"></i>
            <?php endif; ?>
        </div>
        <div class="popup-title">
            <h4><?= htmlspecialchars($hostname ?: $assetTag) ?></h4>
            <span class="badge badge-sm badge-<?= $status === 'active' ? 'success' : ($status === 'maintenance' ? 'warning' : 'secondary') ?>">
                <?= ucfirst($status) ?>
            </span>
        </div>
    </div>

    <div class="popup-details">
        <div class="popup-detail-row">
            <label>Asset Tag</label>
            <span><?= htmlspecialchars($assetTag) ?></span>
        </div>
        <div class="popup-detail-row">
            <label>Type</label>
            <span><?= ucfirst($type) ?></span>
        </div>
        <div class="popup-detail-row">
            <label>Hostname</label>
            <code><?= htmlspecialchars($hostname ?: '—') ?></code>
        </div>
        <div class="popup-detail-row">
            <label>IP Address</label>
            <code><?= htmlspecialchars($ip ?: '—') ?></code>
        </div>
        <div class="popup-detail-row">
            <label>MAC Address</label>
            <code><?= htmlspecialchars($mac ?: '—') ?></code>
        </div>
        <div class="popup-detail-row">
            <label>Assigned To</label>
            <span><?= htmlspecialchars($user ?: '—') ?></span>
        </div>
        <div class="popup-detail-row">
            <label>Department</label>
            <span><?= htmlspecialchars($dept ?: '—') ?></span>
        </div>
    </div>

    <div class="popup-actions">
        <a href="/assets/<?= $assetId ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-eye"></i> View Details
        </a>
        <a href="/assets/<?= $assetId ?>/edit" class="btn btn-sm btn-secondary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>
