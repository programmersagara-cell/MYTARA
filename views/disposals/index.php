<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Asset Disposals</h1>
        <p class="page-subtitle">Manage asset disposal workflow</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/assets/retired') ?>" class="btn btn-primary">
            <i class="fas fa-archive"></i> Retired Assets
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['pending']) ?></span>
            <span class="stat-label">Pending Disposal</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info-soft">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['awaiting_approval']) ?></span>
            <span class="stat-label">Awaiting Approval</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['approved']) ?></span>
            <span class="stat-label">Approved</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-primary-soft">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['scheduled']) ?></span>
            <span class="stat-label">Scheduled</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-danger-soft">
            <i class="fas fa-trash-alt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['disposed']) ?></span>
            <span class="stat-label">Disposed</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-secondary-soft">
            <i class="fas fa-ban"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['cancelled']) ?></span>
            <span class="stat-label">Cancelled</span>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mt-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/disposals') ?>" class="row g-3">
            <div class="col-4">
                <input type="text" name="search" class="form-input" placeholder="Search by asset tag, hostname, serial, certificate..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-2">
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="pending_disposal" <?= ($filterStatus ?? '') === 'pending_disposal' ? 'selected' : '' ?>>Pending Disposal</option>
                    <option value="awaiting_approval" <?= ($filterStatus ?? '') === 'awaiting_approval' ? 'selected' : '' ?>>Awaiting Approval</option>
                    <option value="approved" <?= ($filterStatus ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="scheduled" <?= ($filterStatus ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="disposed" <?= ($filterStatus ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                    <option value="rejected" <?= ($filterStatus ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="cancelled" <?= ($filterStatus ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="archived" <?= ($filterStatus ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
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
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Disposals Table -->
<div class="card mt-4">
    <div class="card-header">
        <h3>Disposals (<?= number_format($total) ?>)</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Retirement Date</th>
                        <th>Reason</th>
                        <th>Disposal Status</th>
                        <th>Disposal Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($disposals)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No disposals found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($disposals as $disposal): ?>
                    <tr>
                        <td>
                            <a href="<?= url('/assets/' . $disposal['asset_id']) ?>">
                                <strong><?= htmlspecialchars($disposal['asset_tag']) ?></strong>
                            </a>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($disposal['hostname'] ?? '') ?></small>
                        </td>
                        <td><?= \App\Helpers\Format::datetime($disposal['retirement_date']) ?></td>
                        <td>
                            <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $disposal['retirement_reason'])) ?></span>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending_disposal' => 'warning',
                                'awaiting_approval' => 'info',
                                'approved' => 'success',
                                'scheduled' => 'primary',
                                'disposed' => 'dark',
                                'rejected' => 'danger',
                                'cancelled' => 'secondary',
                                'archived' => 'secondary',
                            ];
                            $color = $statusColors[$disposal['disposal_status']] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $color ?>"><?= ucwords(str_replace('_', ' ', $disposal['disposal_status'])) ?></span>
                        </td>
                        <td><?= \App\Helpers\Format::datetime($disposal['disposal_date']) ?></td>
                        <td>
                            <a href="<?= url('/disposals/' . $disposal['id']) ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($disposal['disposal_status'] === 'disposed'): ?>
                            <a href="<?= url('/disposals/' . $disposal['id'] . '/certificate') ?>" class="btn btn-sm btn-primary" title="Certificate">
                                <i class="fas fa-file-alt"></i>
                            </a>
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