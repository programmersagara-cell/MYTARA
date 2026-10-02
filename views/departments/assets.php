<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-building"></i> <?= htmlspecialchars($department['name']) ?></h1>
        <p class="page-subtitle">
            <?php if (!empty($department['code'])): ?>
            <code><?= htmlspecialchars($department['code']) ?></code> &middot;
            <?php endif; ?>
            <?= htmlspecialchars($department['section'] ?? '—') ?> &middot;
            <?= number_format($assetCount) ?> <?= $assetCount === 1 ? 'asset' : 'assets' ?> registered
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/departments') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Departments
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-laptop"></i> Registered Assets</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Type</th>
                        <th>Hostname</th>
                        <th>IP Address</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Model / Vendor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td>
                            <a href="<?= url('/assets/' . $asset['id']) ?>" class="asset-tag">
                                <?= htmlspecialchars($asset['asset_tag']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?= match($asset['type']) {
                                'pc' => 'info',
                                'laptop' => 'primary',
                                'switch' => 'success',
                                'server' => 'warning',
                                default => 'secondary'
                            } ?>">
                                <?= \App\Helpers\Format::assetType($asset['type']) ?>
                            </span>
                        </td>
                        <td><code><?= htmlspecialchars($asset['hostname'] ?? '—') ?></code></td>
                        <td><code><?= htmlspecialchars($asset['ip_address'] ?? '—') ?></code></td>
                        <td><?= \App\Helpers\Format::statusBadge($asset['status']) ?></td>
                        <td><?= htmlspecialchars($asset['location'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($asset['assigned_name'] ?? ($asset['assigned_user_name'] ?? '—')) ?></td>
                        <td>
                            <?= htmlspecialchars($asset['model'] ?? '—') ?>
                            <?php if (!empty($asset['vendor'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($asset['vendor']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No assets registered to this department yet</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>