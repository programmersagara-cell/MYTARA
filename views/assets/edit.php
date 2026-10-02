<?php $layout = 'layouts/main'; ?>
<?php
$departments = \App\Models\Department::getOptions();
$users = \App\Models\User::all('full_name', 'ASC');
$assignedUserName = $asset['assigned_name'] ?? '';
if (!$assignedUserName && $asset['assigned_to']) {
    $assignedUser = \App\Models\User::find($asset['assigned_to']);
    $assignedUserName = $assignedUser ? $assignedUser['full_name'] : '';
}

// $asset is passed from controller
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Asset: <?= htmlspecialchars($asset['asset_tag']) ?></h1>
        <p class="page-subtitle">Update asset information</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/assets/' . $asset['id']) ?>" class="btn btn-info">
            <i class="fas fa-eye"></i> View
        </a>
        <a href="<?= url('/assets') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/assets/' . $asset['id'] . '/update') ?>" class="form">
            <?= \App\Helpers\Security::csrfField() ?>

            <div class="form-section">
                <h3>Identification</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label">Asset Type *</label>
                        <select name="type" class="form-input" required>
                            <?php foreach (DEVICE_TYPES as $t): ?>
                            <option value="<?= $t ?>" <?= $asset['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Asset Tag *</label>
                        <input type="text" name="asset_tag" class="form-input" value="<?= htmlspecialchars($asset['asset_tag']) ?>" required>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Hostname</label>
                        <input type="text" name="hostname" class="form-input" value="<?= htmlspecialchars($asset['hostname'] ?? '') ?>">
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-input" value="<?= htmlspecialchars($asset['serial_number'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Network Information</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-input" value="<?= htmlspecialchars($asset['ip_address'] ?? '') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" name="mac_address" class="form-input" value="<?= htmlspecialchars($asset['mac_address'] ?? '') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">OS</label>
                        <input type="text" name="os" class="form-input" value="<?= htmlspecialchars($asset['os'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Hardware</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor" class="form-input" value="<?= htmlspecialchars($asset['vendor'] ?? '') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-input" value="<?= htmlspecialchars($asset['model'] ?? '') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">CPU</label>
                        <input type="text" name="cpu" class="form-input" value="<?= htmlspecialchars($asset['cpu'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">RAM (GB)</label>
                        <input type="number" name="ram_gb" class="form-input" value="<?= $asset['ram_gb'] ?>" min="0">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Storage (GB)</label>
                        <input type="number" name="storage_gb" class="form-input" value="<?= $asset['storage_gb'] ?>" min="0">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <?php foreach (ASSET_STATUSES as $s): ?>
                            <option value="<?= $s ?>" <?= $asset['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Assignment & Location</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-input">
                            <option value="">— Select —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($asset['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-input" value="<?= htmlspecialchars($asset['section'] ?? '') ?>" placeholder="e.g., IT Support">
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Assigned To</label>
                        <input type="text" name="assigned_to" class="form-input" list="assignedUsers" value="<?= htmlspecialchars($assignedUserName) ?>" placeholder="Type user name...">
                        <datalist id="assignedUsers">
                            <?php foreach ($users as $u): ?>
                            <option value="<?= htmlspecialchars($u['full_name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group col-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" value="<?= htmlspecialchars($asset['location'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Floor</label>
                        <select name="floor" class="form-input">
                            <option value="">— Select Floor —</option>
                            <option value="Building 1" <?= ($asset['floor'] ?? '') === 'Building 1' ? 'selected' : '' ?>>Building 1</option>
                            <option value="Building 2" <?= ($asset['floor'] ?? '') === 'Building 2' ? 'selected' : '' ?>>Building 2</option>
                            <option value="Building 3" <?= ($asset['floor'] ?? '') === 'Building 3' ? 'selected' : '' ?>>Building 3</option>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-input" value="<?= $asset['purchase_date'] ?? '' ?>">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Warranty End</label>
                        <input type="date" name="warranty_end" class="form-input" value="<?= $asset['warranty_end'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Additional Information</h3>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3"><?= htmlspecialchars($asset['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= url('/assets') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Asset
                </button>
            </div>
        </form>
    </div>
</div>
