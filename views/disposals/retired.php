<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Retired Assets</h1>
        <p class="page-subtitle">Assets retired and available for disposal</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/disposals') ?>" class="btn btn-secondary">
            <i class="fas fa-recycle"></i> Disposals
        </a>
        <a href="<?= url('/assets') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> All Assets
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mt-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/assets/retired') ?>" class="row g-3">
            <div class="col-3">
                <input type="text" name="search" class="form-input" placeholder="Search asset tag, hostname, serial..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-2">
                <select name="type" class="form-input">
                    <option value="">All Types</option>
                    <option value="pc" <?= ($filterType ?? '') === 'pc' ? 'selected' : '' ?>>PC</option>
                    <option value="laptop" <?= ($filterType ?? '') === 'laptop' ? 'selected' : '' ?>>Laptop</option>
                    <option value="switch" <?= ($filterType ?? '') === 'switch' ? 'selected' : '' ?>>Switch</option>
                    <option value="server" <?= ($filterType ?? '') === 'server' ? 'selected' : '' ?>>Server</option>
                    <option value="printer" <?= ($filterType ?? '') === 'printer' ? 'selected' : '' ?>>Printer</option>
                    <option value="nas" <?= ($filterType ?? '') === 'nas' ? 'selected' : '' ?>>NAS</option>
                    <option value="nvr" <?= ($filterType ?? '') === 'nvr' ? 'selected' : '' ?>>NVR</option>
                    <option value="other" <?= ($filterType ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-2">
                <select name="department_id" class="form-input">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= ($filterDepartment ?? 0) == $dept['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <select name="reason" class="form-input">
                    <option value="">All Reasons</option>
                    <?php foreach (RETIREMENT_REASONS as $reason): ?>
                    <option value="<?= $reason ?>" <?= ($filterReason ?? '') === $reason ? 'selected' : '' ?>>
                        <?= ucwords(str_replace('_', ' ', $reason)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <select name="disposal_status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="pending_disposal" <?= ($filterDisposalStatus ?? '') === 'pending_disposal' ? 'selected' : '' ?>>Pending Disposal</option>
                    <option value="awaiting_approval" <?= ($filterDisposalStatus ?? '') === 'awaiting_approval' ? 'selected' : '' ?>>Awaiting Approval</option>
                    <option value="approved" <?= ($filterDisposalStatus ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="scheduled" <?= ($filterDisposalStatus ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="disposed" <?= ($filterDisposalStatus ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                </select>
            </div>
            <div class="col-1">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Retired Assets Table -->
<div class="card mt-4">
    <div class="card-header">
        <h3>Retired Assets (<?= number_format(count($retiredAssets)) ?>)</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Hostname</th>
                        <th>Type</th>
                        <th>Serial Number</th>
                        <th>Previous User</th>
                        <th>Department</th>
                        <th>Retirement Date</th>
                        <th>Retirement Reason</th>
                        <th>Disposal Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retiredAssets)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No retired assets found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($retiredAssets as $asset): ?>
                    <tr>
                        <td>
                            <a href="<?= url('/assets/' . $asset['id']) ?>">
                                <strong><?= htmlspecialchars($asset['asset_tag']) ?></strong>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($asset['hostname'] ?? '—') ?></td>
                        <td><?= \App\Helpers\Format::assetType($asset['type']) ?></td>
                        <td><?= htmlspecialchars($asset['serial_number'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($asset['assigned_user_name'] ?? ($asset['assigned_name'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars($asset['department_name'] ?? '—') ?></td>
                        <td><?= \App\Helpers\Format::date($asset['disposal_retirement_date'] ?? $asset['retired_at'] ?? $asset['updated_at']) ?></td>
                        <td>
                            <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $asset['retirement_reason'] ?? 'end_of_life')) ?></span>
                        </td>
                        <td>
                            <?php if ($asset['disposal_id']): ?>
                                <?php
                                $statusColors = [
                                    'pending_disposal' => 'warning',
                                    'awaiting_approval' => 'info',
                                    'approved' => 'success',
                                    'scheduled' => 'primary',
                                    'disposed' => 'dark',
                                    'rejected' => 'danger',
                                    'cancelled' => 'secondary',
                                ];
                                $color = $statusColors[$asset['disposal_status']] ?? 'secondary';
                                ?>
                                <span class="badge badge-<?= $color ?>"><?= ucwords(str_replace('_', ' ', $asset['disposal_status'])) ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">No Disposal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($asset['disposal_id']): ?>
                                <a href="<?= url('/disposals/' . $asset['disposal_id']) ?>" class="btn btn-sm btn-info" title="View Disposal">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (($asset['disposal_status'] ?? '') === 'disposed'): ?>
                                <a href="<?= url('/disposals/' . $asset['disposal_id'] . '/certificate') ?>" class="btn btn-sm btn-primary" title="Certificate">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                                <a href="<?= url('/disposals/create/' . $asset['id']) ?>" class="btn btn-sm btn-success" title="Create Disposal Request">
                                    <i class="fas fa-plus"></i> Create Disposal
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (isset($user) && $user && $user['role'] !== 'viewer' && (!$asset['disposal_id'] || in_array($asset['disposal_status'], ['rejected', 'cancelled']))): ?>
                            <form method="POST" action="<?= url('/assets/' . $asset['id'] . '/return-to-service') ?>" class="d-inline" onsubmit="return confirm('Return this asset to service?');">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-warning" title="Return to Service">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>