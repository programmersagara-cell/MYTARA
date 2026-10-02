<?php $layout = 'layouts/main'; ?>
<?php
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$dept = (int) ($_GET['department'] ?? 0);

$assets = \App\Models\Asset::searchAssets($search, $type, $status, $dept);
$departments = \App\Models\Department::getOptions();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Asset Ledger</h1>
        <p class="page-subtitle">Manage all IT assets in the organization</p>
    </div>
<div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <a href="<?= url('/assets/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Asset
        </a>
        <a href="<?= url('/assets/import') ?>" class="btn btn-secondary">
            <i class="fas fa-upload"></i> Import
        </a>
        <?php endif; ?>
        <a href="<?= url('/assets/export/csv') ?>" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export
        </a>
        <a href="<?= url('/assets/labels') ?>" class="btn btn-secondary">
            <i class="fas fa-tags"></i> Print Labels
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/assets') ?>" class="filter-form">
            <div class="form-row">
                <div class="form-group col-4">
                    <input type="text" name="search" class="form-input" placeholder="Search by tag, hostname, IP, serial..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group col-2">
                    <select name="type" class="form-input">
                        <option value="">All Types</option>
                        <?php foreach (DEVICE_TYPES as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-2">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <?php foreach (ASSET_STATUSES as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-2">
                    <select name="department" class="form-input">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $dept === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Type</th>
                        <th>Hostname</th>
                        <th>IP Address</th>
                        <th>Department</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                        <td><?= htmlspecialchars($asset['department_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($asset['assigned_name'] ?? ($asset['assigned_user_name'] ?? '—')) ?></td>
                        <td><?= \App\Helpers\Format::statusBadge($asset['status']) ?></td>
<td>
                            <div class="action-buttons">
                                <a href="<?= url('/assets/' . $asset['id']) ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                                <a href="<?= url('/assets/' . $asset['id'] . '/edit') ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?= url('/assets/' . $asset['id'] . '/delete') ?>" style="display:inline" 
                                      onsubmit="return confirm('Delete this asset?')">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No assets found. <a href="<?= url('/assets/create') ?>">Add your first asset</a></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
