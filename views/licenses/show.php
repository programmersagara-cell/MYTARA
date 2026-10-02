<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($license['license_name']) ?></h1>
        <p class="page-subtitle">License details and assignments</p>
    </div>
    <div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <a href="<?= url('/licenses/' . $license['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= url('/licenses') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-8">
        <!-- License Details -->
        <div class="card">
            <div class="card-header">
                <h3>License Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>License Name</label>
                        <span class="detail-value"><?= htmlspecialchars($license['license_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Software</label>
                        <span><?= htmlspecialchars($license['software_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Vendor</label>
                        <span><?= htmlspecialchars($license['vendor'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>License Key</label>
                        <code><?= htmlspecialchars(\App\Models\SoftwareLicense::maskLicenseKey($license['license_key'])) ?></code>
                    </div>
                    <div class="detail-item">
                        <label>Type</label>
                        <span><?= ucwords(str_replace('_', ' ', $license['license_type'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Version</label>
                        <span><?= htmlspecialchars($license['version'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
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
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <span><?= htmlspecialchars($license['department_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Assigned User</label>
                        <span><?= htmlspecialchars($license['assigned_user_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Purchase Date</label>
                        <span><?= \App\Helpers\Format::date($license['purchase_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Start Date</label>
                        <span><?= \App\Helpers\Format::date($license['start_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Expiration Date</label>
                        <span><?= \App\Helpers\Format::date($license['expiration_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Purchased Seats</label>
                        <span><?= number_format($license['purchased_seats']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Used Seats</label>
                        <span><?= number_format($license['used_seats']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Available Seats</label>
                        <span><?= number_format(max(0, $license['purchased_seats'] - $license['used_seats'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Cost</label>
                        <span><?= $license['cost'] ? number_format($license['cost'], 2) . ' ' . ($license['currency'] ?? '') : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created By</label>
                        <span><?= htmlspecialchars($license['created_by_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created</label>
                        <span><?= \App\Helpers\Format::datetime($license['created_at']) ?></span>
                    </div>
                </div>
                <?php if ($license['notes']): ?>
                <div class="mt-3">
                    <label>Notes:</label>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($license['notes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Assignments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Assigned To</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                                <th>Assigned By</th>
                                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No assignments yet</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    <?php if ($assignment['user_id']): ?>
                                        <span class="badge badge-info">User</span>
                                    <?php elseif ($assignment['asset_id']): ?>
                                        <span class="badge badge-primary">Asset</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Manual</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($assignment['user_id']): ?>
                                        <?= htmlspecialchars($assignment['user_name'] ?? '—') ?>
                                    <?php elseif ($assignment['asset_id']): ?>
                                        <?= htmlspecialchars($assignment['asset_tag'] ?? '—') ?>
                                        <?php if ($assignment['hostname']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($assignment['hostname']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($assignment['assigned_name'] ?? $assignment['assigned_tag'] ?? '—') ?>
                                        <br><small class="text-muted">Manually entered</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Helpers\Format::datetime($assignment['assigned_date']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $assignment['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($assignment['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($assignment['assigned_by_name'] ?? '—') ?></td>
                                <?php if (isset($user) && $user && $user['role'] !== 'viewer' && $assignment['status'] === 'active'): ?>
                                <td>
                                    <form method="POST" action="<?= url('/licenses/' . $license['id'] . '/assignments/' . $assignment['id'] . '/remove') ?>" class="d-inline" onsubmit="return confirm('Remove this assignment?');">
                                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Remove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4">
        <!-- Assign License -->
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Assign License</h3>
            </div>
            <div class="card-body">
                <?php if ($license['used_seats'] >= $license['purchased_seats']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> License is at full capacity.
                </div>
                <?php else: ?>
                <form method="POST" action="<?= url('/licenses/' . $license['id'] . '/assign/user') ?>" class="form" id="assignUserForm">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                    <input type="hidden" name="user_id" id="assign_user_id" value="">
                    <div class="form-group">
                        <label class="form-label">Assign to User</label>
                        <div class="autocomplete" id="assign-user-ac">
                            <input type="text" id="assign_user_input" name="user_name" class="form-input" placeholder="Type a name or username&hellip;" autocomplete="off" required>
                            <div class="autocomplete-list" id="assign_user_list"></div>
                        </div>
                        <small class="form-hint" id="assign_user_hint">Search and pick a user, or just type a name to assign manually.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Assign to User
                    </button>
                </form>

                <hr>

                <form method="POST" action="<?= url('/licenses/' . $license['id'] . '/assign/asset') ?>" class="form" id="assignAssetForm">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                    <input type="hidden" name="asset_id" id="assign_asset_id" value="">
                    <div class="form-group">
                        <label class="form-label">Assign to Asset</label>
                        <div class="autocomplete" id="assign-asset-ac">
                            <input type="text" id="assign_asset_input" name="asset_tag" class="form-input" placeholder="Type an asset tag or hostname&hellip;" autocomplete="off" required>
                            <div class="autocomplete-list" id="assign_asset_list"></div>
                        </div>
                        <small class="form-hint" id="assign_asset_hint">Search and pick an asset, or just type a tag to assign manually.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-desktop"></i> Assign to Asset
                    </button>
                </form>

                <style>
                    .autocomplete { position: relative; }
                    .autocomplete-list {
                        position: absolute; z-index: 50; left: 0; right: 0; top: 100%;
                        background: var(--card-bg, #fff); border: 1px solid var(--border-color, #d1d9e6);
                        border-radius: 8px; margin-top: 4px; max-height: 220px; overflow-y: auto;
                        box-shadow: 0 8px 24px rgba(15, 23, 42, .12); display: none;
                    }
                    .autocomplete-list.open { display: block; }
                    .autocomplete-item {
                        padding: 8px 12px; cursor: pointer; font-size: 0.9rem;
                        color: var(--text-color, #1e293b);
                    }
                    .autocomplete-item:hover, .autocomplete-item.active { background: var(--primary-soft, #e8f1fd); }
                    .autocomplete-item .muted { color: var(--text-muted, #64748b); font-size: 0.8rem; }
                    .autocomplete-item.none { cursor: default; color: var(--text-muted, #64748b); }
                    .form-hint { font-size: 0.78rem; color: var(--text-muted, #64748b); margin-top: 4px; display: block; }
                    .form-hint.error { color: var(--danger, #dc2626); }
                </style>
                <script>
                (function() {
                    'use strict';
                    var BASE = window.BASE_PATH || '';

                    function setupAutocomplete(opts) {
                        var input = document.getElementById(opts.inputId);
                        var list = document.getElementById(opts.listId);
                        var hidden = document.getElementById(opts.hiddenId);
                        var hint = document.getElementById(opts.hintId);
                        var form = input.closest('form');
                        var items = [];
                        var activeIndex = -1;
                        var debounceTimer = null;

                        function close() { list.classList.remove('open'); activeIndex = -1; }

                        function escapeHtml(s) {
                            var d = document.createElement('div');
                            d.textContent = s == null ? '' : String(s);
                            return d.innerHTML;
                        }

                        function select(i) {
                            var item = items[i];
                            if (!item) return;
                            hidden.value = item.id;
                            input.value = item.labelText;
                            hint.textContent = 'Selected: ' + item.labelText;
                            hint.classList.remove('error');
                            close();
                        }

                        function clearSelection() {
                            if (hidden.value) { hidden.value = ''; }
                            hint.textContent = opts.hintDefault;
                            hint.classList.remove('error');
                        }

                        function render() {
                            list.innerHTML = '';
                            if (!items.length) {
                                var none = document.createElement('div');
                                none.className = 'autocomplete-item none';
                                none.textContent = input.value.trim() ? 'No matches found.' : 'Start typing to search…';
                                list.appendChild(none);
                            } else {
                                items.forEach(function(item, i) {
                                    var div = document.createElement('div');
                                    div.className = 'autocomplete-item' + (i === activeIndex ? ' active' : '');
                                    div.innerHTML = '<div>' + item.label + '</div>' +
                                        (item.sub ? '<div class="muted">' + item.sub + '</div>' : '');
                                    div.addEventListener('mousedown', function(e) {
                                        e.preventDefault();
                                        select(i);
                                    });
                                    list.appendChild(div);
                                });
                            }
                            list.classList.add('open');
                        }

                        function search() {
                            var q = input.value.trim();
                            if (!q) { items = []; clearSelection(); close(); return; }
                            fetch(BASE + opts.endpoint + '?q=' + encodeURIComponent(q), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                var rows = (data && data.results) ? data.results : [];
                                items = rows.map(function(row) {
                                    return {
                                        id: row.id,
                                        label: escapeHtml(row.text),
                                        labelText: row.text,
                                        sub: opts.subKey && row[opts.subKey] ? escapeHtml(row[opts.subKey]) : ''
                                    };
                                });
                                activeIndex = items.length ? 0 : -1;
                                clearSelection();
                                render();
                            }).catch(function() {
                                items = [];
                                clearSelection();
                                render();
                            });
                        }

                        input.addEventListener('input', function() {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(search, 200);
                        });

                        input.addEventListener('keydown', function(e) {
                            if (!list.classList.contains('open')) return;
                            if (e.key === 'ArrowDown') {
                                e.preventDefault();
                                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                                render();
                            } else if (e.key === 'ArrowUp') {
                                e.preventDefault();
                                activeIndex = Math.max(activeIndex - 1, 0);
                                render();
                            } else if (e.key === 'Enter') {
                                if (activeIndex >= 0 && items[activeIndex]) {
                                    e.preventDefault();
                                    select(activeIndex);
                                }
                            } else if (e.key === 'Escape') {
                                close();
                            }
                        });

                        input.addEventListener('blur', function() { setTimeout(close, 150); });

                        form.addEventListener('submit', function(e) {
                            // Picking a suggestion is optional — typed text is submitted
                            // as a manual assignment when nothing was selected.
                            if (!input.value.trim() && !hidden.value) {
                                e.preventDefault();
                                hint.textContent = 'Please enter a ' + opts.noun + ' name or tag.';
                                hint.classList.add('error');
                                input.focus();
                            }
                        });
                    }

                    setupAutocomplete({
                        inputId: 'assign_user_input', listId: 'assign_user_list',
                        hiddenId: 'assign_user_id', hintId: 'assign_user_hint',
                        endpoint: '/api/users', subKey: 'text',
                        hintDefault: 'Search and pick a user, or just type a name to assign manually.',
                        noun: 'user'
                    });

                    setupAutocomplete({
                        inputId: 'assign_asset_input', listId: 'assign_asset_list',
                        hiddenId: 'assign_asset_id', hintId: 'assign_asset_hint',
                        endpoint: '/api/assets', subKey: 'hostname',
                        hintDefault: 'Search and pick an asset, or just type a tag to assign manually.',
                        noun: 'asset'
                    });
                })();
                </script>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Utilization -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Utilization</h3>
            </div>
            <div class="card-body">
                <?php
                $utilization = $license['purchased_seats'] > 0 ? round(($license['used_seats'] / $license['purchased_seats']) * 100, 1) : 0;
                ?>
                <div class="text-center mb-3">
                    <span class="stat-value"><?= $utilization ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar <?= $utilization >= 100 ? 'bg-danger' : ($utilization >= 80 ? 'bg-warning' : '') ?>" style="width: <?= min(100, $utilization) ?>%"></div>
                </div>
                <div class="row mt-3 text-center">
                    <div class="col-4">
                        <div class="detail-item">
                            <label>Used</label>
                            <span class="detail-value"><?= $license['used_seats'] ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="detail-item">
                            <label>Available</label>
                            <span class="detail-value"><?= max(0, $license['purchased_seats'] - $license['used_seats']) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="detail-item">
                            <label>Total</label>
                            <span class="detail-value"><?= $license['purchased_seats'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>