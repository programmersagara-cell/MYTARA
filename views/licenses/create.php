<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add New License</h1>
        <p class="page-subtitle">Create a new software license record</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/licenses') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Licenses
        </a>
    </div>
</div>

<?php if (isset($flash['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
<?php endif; ?>
<?php if (isset($flash['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash['error']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('/licenses') ?>" class="form-card">
    <?= \App\Helpers\Security::csrfField() ?>
    <div class="form-section">
        <h3 class="section-title">License Information</h3>
        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">License Name *</label>
                <input type="text" name="license_name" class="form-input" required placeholder="e.g., Microsoft Office 365 E1 License">
            </div>
            <div class="form-group col-6">
                <label class="form-label">Software Name *</label>
                <input type="text" name="software_name" class="form-input" required placeholder="e.g., Microsoft Office 365">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">License Key</label>
                <input type="text" name="license_key" class="form-input" placeholder="Enter license key (optional)">
            </div>
            <div class="form-group col-6">
                <label class="form-label">License Type *</label>
                <select name="license_type" class="form-input" required>
                    <option value="">— Select Type —</option>
                    <?php foreach (LICENSE_TYPES as $type): ?>
                        <option value="<?= $type ?>"><?= ucwords(str_replace('_', ' ', $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">Version</label>
                <input type="text" name="version" class="form-input" placeholder="e.g., 2024">
            </div>
            <div class="form-group col-6">
                <label class="form-label">Vendor</label>
                <input type="text" name="vendor" class="form-input" placeholder="e.g., Microsoft">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Purchase & Subscription Details</h3>
        <div class="form-row">
            <div class="form-group col-4">
                <label class="form-label">Purchase Date</label>
                <input type="date" name="purchase_date" class="form-input">
            </div>
            <div class="form-group col-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-input">
            </div>
            <div class="form-group col-4" id="expirationDateGroup">
                <label class="form-label">Expiration Date</label>
                <input type="date" name="expiration_date" class="form-input" id="expirationDateInput">
                <small class="text-muted" id="expirationNote" style="display:none;">Not applicable for perpetual licenses.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">Purchased Seats *</label>
                <input type="number" name="purchased_seats" class="form-input" required min="1" value="1">
            </div>
            <div class="form-group col-6">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-input">
                    <option value="PHP">PHP - Philippine Peso</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">Cost</label>
                <input type="number" name="cost" class="form-input" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group col-6">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-input">
                    <option value="">— No Department —</option>
                    <?php foreach ($departments ?? [] as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Assignment</h3>
        <div class="form-row">
            <div class="form-group col-6">
                <label class="form-label">Assigned User</label>
                <div class="autocomplete">
                    <input type="text" id="assign_user_input" name="assigned_name" class="form-input" placeholder="Type a user name (e.g., Juan Dela Cruz)" autocomplete="off">
                    <div class="autocomplete-list" id="assign_user_list"></div>
                </div>
                <input type="hidden" id="assign_user_id" name="assigned_user_id">
                <small class="form-hint" id="assign_user_hint">Start typing to search users — pick a suggestion from the list.</small>
            </div>
            <div class="form-group col-6">
                <label class="form-label">Assigned Asset</label>
                <div class="autocomplete">
                    <input type="text" id="assign_asset_input" name="assigned_asset_tag" class="form-input" placeholder="Type an asset tag or hostname (e.g., PC-001)" autocomplete="off">
                    <div class="autocomplete-list" id="assign_asset_list"></div>
                </div>
                <input type="hidden" id="assign_asset_id" name="assigned_asset_id">
                <small class="form-hint" id="assign_asset_hint">Start typing to search assets — pick a suggestion from the list.</small>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Notes</h3>
        <div class="form-group">
            <textarea name="notes" class="form-input" rows="3" placeholder="Additional notes about this license..."></textarea>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= url('/licenses') ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Create License
        </button>
    </div>
</form>

<script>
(function () {
    var typeSelect = document.querySelector('select[name="license_type"]');
    if (!typeSelect) return;

    var group = document.getElementById('expirationDateGroup');
    var input = document.getElementById('expirationDateInput');
    var note = document.getElementById('expirationNote');

    function toggleExpiration() {
        var isPerpetual = typeSelect.value === 'perpetual';
        if (group) group.style.display = isPerpetual ? 'none' : '';
        if (isPerpetual && input) input.value = ''; // never submit an expiration for perpetual
        if (note) note.style.display = 'none';
    }

    typeSelect.addEventListener('change', toggleExpiration);
    toggleExpiration();
})();
</script>

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
            hint.textContent = input.value.trim() ? '' : opts.hintDefault;
            hint.classList.remove('error');
        }

        function render() {
            list.innerHTML = '';
            if (!items.length) {
                var none = document.createElement('div');
                none.className = 'autocomplete-item none';
                none.textContent = input.value.trim() ? 'No matches found.' : opts.hintDefault;
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
            if (!input.value.trim()) {
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
        hintDefault: 'Start typing to search users — pick a suggestion from the list.',
        noun: 'user'
    });

    setupAutocomplete({
        inputId: 'assign_asset_input', listId: 'assign_asset_list',
        hiddenId: 'assign_asset_id', hintId: 'assign_asset_hint',
        endpoint: '/api/assets', subKey: 'hostname',
        hintDefault: 'Start typing to search assets — pick a suggestion from the list.',
        noun: 'asset'
    });
})();
</script>
