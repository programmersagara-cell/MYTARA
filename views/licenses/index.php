<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Software Licenses</h1>
        <p class="page-subtitle">Manage software licenses and assignments</p>
    </div>
    <div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <a href="<?= url('/licenses/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add License
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
<?php endif; ?>
<?php if (isset($flash['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash['error']) ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-soft">
            <i class="fas fa-file-contract"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatTotal"><?= number_format($stats['total'] ?? 0) ?></span>
            <span class="stat-label">Total Licenses</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatActive"><?= number_format($stats['active'] ?? 0) ?></span>
            <span class="stat-label">Active</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatExpiring"><?= number_format($stats['expiring_soon'] ?? 0) ?></span>
            <span class="stat-label">Expiring Soon</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-danger-soft">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatExpired"><?= number_format($stats['expired'] ?? 0) ?></span>
            <span class="stat-label">Expired</span>
        </div>
    </div>
</div>

<!-- License Utilization -->
<div class="stats-grid mt-2">
    <div class="stat-card">
        <div class="stat-icon bg-info-soft">
            <i class="fas fa-chair"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatPurchased"><?= number_format($stats['purchased_seats'] ?? 0) ?></span>
            <span class="stat-label">Purchased Seats</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-secondary-soft">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatUsed"><?= number_format($stats['used_seats'] ?? 0) ?></span>
            <span class="stat-label">Used Seats</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-soft">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatAvailable"><?= number_format($stats['available_seats'] ?? 0) ?></span>
            <span class="stat-label">Available Seats</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-soft">
            <i class="fas fa-tachometer-alt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="licenseStatUtilization"><?= number_format($stats['utilization'] ?? 0) ?>%</span>
            <span class="stat-label">Utilization</span>
        </div>
    </div>
</div>

<!-- License Table -->
<div class="card mt-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Licenses</h3>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= url('/licenses') ?>" class="row mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-input" placeholder="Search licenses..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <?php foreach (LICENSE_STATUSES as $status): ?>
                        <option value="<?= $status ?>" <?= ($selectedStatus ?? '') === $status ? 'selected' : '' ?>>
                            <?= ucwords(str_replace('_', ' ', $status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Software</th>
                        <th>License Key</th>
                        <th>Type</th>
                        <th>Seats</th>
                        <th>Expiration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No licenses found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($licenses as $license): ?>
                    <tr data-license-id="<?= (int)$license['id'] ?>">
                        <td>
                            <strong><?= htmlspecialchars($license['software_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($license['vendor'] ?? '') ?></small>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($license['license_key'] ?? '—') ?></code>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $license['license_type'] ?? 'other')) ?></span>
                        </td>
                        <td>
                            <?php
                            $purchased = (int)($license['purchased_seats'] ?? 0);
                            $used = (int)($license['used_seats'] ?? 0);
                            $remaining = max(0, $purchased - $used);
                            ?>
                            <?php if ($purchased > 0): ?>
                                <span class="badge badge-info"><?= $used ?> / <?= $purchased ?> seats</span><br>
                                <?php if ($remaining > 0): ?>
                                    <span class="badge badge-success mt-1"><i class="fas fa-chair"></i> <?= $remaining ?> remaining</span>
                                <?php else: ?>
                                    <span class="badge badge-danger mt-1"><i class="fas fa-exclamation-triangle"></i> Full</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-secondary">Unlimited</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $now = time(); $exp = strtotime($license['expiration_date'] ?? ''); ?>
                            <?php if ($license['expiration_date'] && $exp < $now): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif ($license['expiration_date'] && $exp < strtotime('+30 days')): ?>
                                <span class="badge badge-warning">Expiring</span>
                            <?php elseif ($license['expiration_date']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">No Expiry</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'active' => 'success',
                                'expiring_soon' => 'warning',
                                'expired' => 'danger',
                                'suspended' => 'secondary',
                                'retired' => 'dark',
                            ];
                            $color = $statusColors[$license['status']] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $color ?>"><?= ucwords(str_replace('_', ' ', $license['status'])) ?></span>
                        </td>
                        <td class="text-right">
                            <a href="<?= url('/licenses/' . $license['id']) ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                            <a href="<?= url('/licenses/' . $license['id'] . '/edit') ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?= $license['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
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

<script>
(function() {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showAlert(message, type) {
        let box = document.getElementById('licenseAjaxAlert');
        if (!box) {
            box = document.createElement('div');
            box.id = 'licenseAjaxAlert';
            box.className = 'alert alert-dismissible';
            const anchor = document.querySelector('.page-content') || document.body;
            anchor.prepend(box);
        }
        box.className = 'alert alert-' + type + ' alert-dismissible';
        box.innerHTML = '<span></span>';
        box.querySelector('span').textContent = message;
    }

    function showEmptyRow(tbody) {
        if (tbody.querySelector('tr[data-license-id]')) return;
        const colspan = tbody.querySelector('tr')?.children.length || 7;
        const tr = document.createElement('tr');
        tr.innerHTML = '<td class="text-center text-muted py-4">No licenses found</td>';
        tr.firstChild.colSpan = colspan;
        tbody.appendChild(tr);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!confirm('Delete this license?')) return;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;

        const url = (window.BASE_PATH || '') + '/licenses/' + id + '/delete';

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-Token': csrfToken,
                // Required so the controller returns JSON instead of a redirect;
                // fetch() follows redirects silently and the row would linger.
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function(res) {
                return res.json()
                    .catch(function() { return { success: false, message: 'Unexpected server response (HTTP ' + res.status + ').' }; });
            })
            .then(function(data) {
                if (!data.success) {
                    showAlert(data.message || 'Failed to delete the license.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    return;
                }

                // Remove the row immediately so the table reflects the delete
                // without waiting for a full page refresh.
                const row = btn.closest('tr');
                const tbody = row ? row.parentElement : null;
                if (row) row.remove();
                if (tbody) showEmptyRow(tbody);

                if (data.stats) {
                    const map = {
                        total: 'licenseStatTotal',
                        active: 'licenseStatActive',
                        expiring_soon: 'licenseStatExpiring',
                        expired: 'licenseStatExpired',
                        purchased_seats: 'licenseStatPurchased',
                        used_seats: 'licenseStatUsed',
                        available_seats: 'licenseStatAvailable',
                        utilization: 'licenseStatUtilization'
                    };
                    Object.keys(map).forEach(function(key) {
                        const el = document.getElementById(map[key]);
                        if (el && typeof data.stats[key] === 'number') {
                            el.textContent = data.stats[key].toLocaleString();
                        }
                    });
                }

                showAlert(data.message || 'License deleted successfully.', 'success');
            })
            .catch(function() {
                showAlert('Network error — the license could not be deleted.', 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
    });
})();
</script>