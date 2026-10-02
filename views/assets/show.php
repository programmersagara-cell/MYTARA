<?php $layout = 'layouts/main'; ?>
<?php
$asset = \App\Models\Asset::findWithRelations($asset['id']);
$history = \App\Models\AssetHistory::getByAsset($asset['id']);
$links = \App\Models\NetworkLink::getByDevice($asset['id']);
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($asset['asset_tag']) ?></h1>
        <p class="page-subtitle">Asset details and information</p>
    </div>
<div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <a href="<?= url('/assets/' . $asset['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= url('/assets/labels') ?>?ids=<?= (int) $asset['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-tags"></i> Print Label
        </a>
        <a href="<?= url('/assets') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-8">
        <!-- Main Details -->
        <div class="card">
            <div class="card-header">
                <h3>Asset Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Asset Tag</label>
                        <span class="detail-value"><?= htmlspecialchars($asset['asset_tag']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Type</label>
                        <span><?= \App\Helpers\Format::assetType($asset['type']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Hostname</label>
                        <code><?= htmlspecialchars($asset['hostname'] ?? '—') ?></code>
                    </div>
                    <div class="detail-item">
                        <label>IP Address</label>
                        <code><?= htmlspecialchars($asset['ip_address'] ?? '—') ?></code>
                    </div>
                    <div class="detail-item">
                        <label>MAC Address</label>
                        <code><?= htmlspecialchars($asset['mac_address'] ?? '—') ?></code>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <?= \App\Helpers\Format::statusBadge($asset['status']) ?>
                    </div>
                    <div class="detail-item">
                        <label>Vendor</label>
                        <span><?= htmlspecialchars($asset['vendor'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Model</label>
                        <span><?= htmlspecialchars($asset['model'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Serial Number</label>
                        <code><?= htmlspecialchars($asset['serial_number'] ?? '—') ?></code>
                    </div>
                    <div class="detail-item">
                        <label>Operating System</label>
                        <span><?= htmlspecialchars($asset['os'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>CPU</label>
                        <span><?= htmlspecialchars($asset['cpu'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>RAM</label>
                        <span><?= $asset['ram_gb'] ? $asset['ram_gb'] . ' GB' : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Storage</label>
                        <span><?= $asset['storage_gb'] ? $asset['storage_gb'] . ' GB' : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Purchase Date</label>
                        <span><?= \App\Helpers\Format::date($asset['purchase_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Warranty Until</label>
                        <span><?= \App\Helpers\Format::date($asset['warranty_end']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Location & Assignment</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Location</label>
                        <span><?= htmlspecialchars($asset['location'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Floor</label>
                        <span><?= htmlspecialchars($asset['floor'] ?? '—') ?></span>
                    </div>
<div class="detail-item">
                        <label>Department</label>
                        <span><?= htmlspecialchars($asset['department_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Section</label>
                        <span><?= htmlspecialchars($asset['section'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Assigned To</label>
                        <span><?= htmlspecialchars($asset['assigned_name'] ?? ($asset['assigned_user_name'] ?? '—')) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created By</label>
                        <span><?= htmlspecialchars($asset['created_by_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created</label>
                        <span><?= \App\Helpers\Format::datetime($asset['created_at']) ?></span>
                    </div>
                </div>
                <?php if ($asset['notes']): ?>
                <div class="mt-3">
                    <label>Notes:</label>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($asset['notes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Asset Lifecycle -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Asset Lifecycle</h3>
            </div>
            <div class="card-body">
                <div class="lifecycle-timeline">
                    <div class="lifecycle-steps d-flex">
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-box"></i></div>
                            <div class="step-label">Purchased</div>
                            <small><?= \App\Helpers\Format::date($asset['purchase_date']) ?></small>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Active</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-user"></i></div>
                            <div class="step-label">Assigned</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-tools"></i></div>
                            <div class="step-label">Maintenance</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-warehouse"></i></div>
                            <div class="step-label">Retired</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-hourglass-half"></i></div>
                            <div class="step-label">Pending Disposal</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-check-double"></i></div>
                            <div class="step-label">Approved</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-trash-alt"></i></div>
                            <div class="step-label">Disposed</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($disposal) && $disposal): ?>
                <div class="mt-4">
                    <h4>Disposal Record</h4>
                    <?php if ($disposal['disposal_status'] === 'disposed'): ?>
                        <a href="<?= url('/disposals/' . $disposal['id'] . '/certificate') ?>" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> View Certificate
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/disposals/' . $disposal['id']) ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Disposal Record
                        </a>
                    <?php endif; ?>
                </div>
                <?php elseif (isset($asset) && $asset['status'] === 'retired' && isset($user) && $user['role'] !== 'viewer'): ?>
                <div class="mt-4">
                    <h4>Disposal Record</h4>
                    <p>No disposal record exists. <a href="<?= url('/disposals/create/' . $asset['id']) ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Disposal Request
                    </a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-4">
        <!-- Network Connections -->
        <div class="card">
            <div class="card-header">
                <h3>Network Connections</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($links)): ?>
                <ul class="connection-list">
                    <?php foreach ($links as $link): ?>
                    <li>
                        <i class="fas fa-<?= $link['link_type'] === 'wifi' ? 'wifi' : 'ethernet' ?>"></i>
                        <span><?= htmlspecialchars($link['connected_hostname']) ?></span>
                        <small class="text-muted">(<?= htmlspecialchars($link['connected_type']) ?>)</small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center">No network connections</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- History -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Recent History</h3>
            </div>
            <div class="card-body p-0">
                <div class="activity-timeline compact">
                    <?php foreach (array_slice($history, 0, 10) as $entry): ?>
                    <div class="activity-item">
                        <div class="activity-icon bg-<?= match($entry['action']) {
                            'created' => 'success',
                            'deleted' => 'danger',
                            default => 'info'
                        } ?>-soft">
                            <i class="fas fa-<?= match($entry['action']) {
                                'created' => 'plus',
                                'deleted' => 'trash',
                                default => 'edit'
                            } ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text small">
                                <?= ucfirst($entry['action']) ?> by <?= htmlspecialchars($entry['user_name'] ?? 'System') ?>
                            </p>
                            <span class="activity-time"><?= \App\Helpers\Format::relativeTime($entry['created_at']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
