<?php $layout = 'layouts/main'; ?>
<?php
$extraScripts = ['asset-create.js'];
$departments = \App\Models\Department::getOptions();
$nextTag = $nextTag ?? \App\Models\Asset::generateAssetTag();
$users = \App\Models\User::all('full_name', 'ASC');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add Asset</h1>
        <p class="page-subtitle">Register a new IT asset in the system</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/assets') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Ledger
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/assets') ?>" class="form" enctype="multipart/form-data">
            <?= \App\Helpers\Security::csrfField() ?>

            <!-- Type and Tag -->
            <div class="form-section">
                <h3>Identification</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label">Asset Type *</label>
                        <select name="type" class="form-input" id="assetType" required>
                            <?php foreach (DEVICE_TYPES as $t): ?>
                            <option value="<?= $t ?>" <?= ($_GET['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Asset Tag *</label>
                        <div style="display:flex; gap:0.4rem; align-items:center;">
                            <input type="text" name="asset_tag" id="assetTag" class="form-input" value="<?= htmlspecialchars($nextTag) ?>" required style="flex:1; min-width:0;">
                            <button type="button" id="generateTagBtn" class="btn btn-secondary" title="Generate a new tag (ORG-YY-DEPT-NNNN)">&#8635; Generate</button>
                        </div>
                        <small class="text-muted">Format: ORG-YY-DEPT-NNNN — auto-refreshes with department/purchase date; edit freely for existing vendor tags.</small>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Hostname</label>
                        <input type="text" name="hostname" class="form-input" placeholder="e.g., PC-IT-001">
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-input" placeholder="e.g., SN-12345">
                    </div>
                </div>
            </div>

            <!-- Network Info -->
            <div class="form-section">
                <h3>Network Information</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-input" placeholder="192.168.1.100" pattern="\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" name="mac_address" class="form-input" placeholder="AA:BB:CC:DD:EE:FF" pattern="([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Operating System</label>
                        <input type="text" name="os" class="form-input" placeholder="e.g., Windows 11 Pro">
                    </div>
                </div>
            </div>

            <!-- Hardware -->
            <div class="form-section">
                <h3>Hardware</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor" class="form-input" placeholder="e.g., Dell, HP, Lenovo">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-input" placeholder="e.g., OptiPlex 7090">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">CPU</label>
                        <input type="text" name="cpu" class="form-input" placeholder="e.g., Intel Core i7-11700">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">RAM (GB)</label>
                        <input type="number" name="ram_gb" class="form-input" min="0" max="4096" placeholder="e.g., 16">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Storage (GB)</label>
                        <input type="number" name="storage_gb" class="form-input" min="0" max="65536" placeholder="e.g., 512">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <?php foreach (ASSET_STATUSES as $s): ?>
                            <option value="<?= $s ?>" <?= $s === 'active' ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="form-section">
                <h3>Assignment & Location</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="departmentId" class="form-input">
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-input" placeholder="e.g., IT Support">
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Assigned To</label>
                        <input type="text" name="assigned_to" class="form-input" list="assignedUsers" placeholder="Type user name...">
                        <datalist id="assignedUsers">
                            <?php foreach ($users as $u): ?>
                            <option value="<?= htmlspecialchars($u['full_name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" placeholder="e.g., Main Office">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Building</label>
                        <select name="floor" class="form-input">
                            <option value="">— Select Building —</option>
                            <option value="Building 1">Building 1</option>
                            <option value="Building 2">Building 2</option>
                            <option value="Building 3">Building 3</option>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchaseDate" class="form-input">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Warranty End</label>
                        <input type="date" name="warranty_end" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-section">
                <h3>Additional Information</h3>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Any additional notes about this asset..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= url('/assets') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Asset
                </button>
            </div>
        </form>
    </div>
</div>
