<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Disposal Request</h1>
        <p class="page-subtitle">Create a disposal record for asset <?= htmlspecialchars($asset['asset_tag']) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/assets/retired') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Disposal Information</h3>
    </div>
    <div class="card-body">
        <!-- Asset Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="detail-item">
                    <label>Asset</label>
                    <span class="detail-value"><?= htmlspecialchars($asset['asset_tag']) ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-item">
                    <label>Hostname</label>
                    <span class="detail-value"><?= htmlspecialchars($asset['hostname'] ?? '—') ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-item">
                    <label>Type</label>
                    <span class="detail-value"><?= \App\Helpers\Format::assetType($asset['type']) ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-item">
                    <label>Serial</label>
                    <span class="detail-value"><?= htmlspecialchars($asset['serial_number'] ?? '—') ?></span>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= url('/disposals/store/' . $asset['id']) ?>" class="form">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Retirement Reason *</label>
                        <select name="retirement_reason" class="form-input" required>
                            <?php foreach (RETIREMENT_REASONS as $reason): ?>
                            <option value="<?= $reason ?>"><?= ucwords(str_replace('_', ' ', $reason)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Disposal Method</label>
                        <select name="disposal_method" class="form-input">
                            <option value="">— Select Method —</option>
                            <?php foreach (DISPOSAL_METHODS as $method): ?>
                            <option value="<?= $method ?>"><?= ucwords(str_replace('_', ' ', $method)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Disposal Location</label>
                        <input type="text" name="disposal_location" class="form-input" placeholder="e.g., Warehouse 2">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Disposal Vendor</label>
                        <input type="text" name="disposal_vendor" class="form-input" placeholder="e.g., Example Recycling Corp.">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="data_destruction_required" value="1" id="dataDestructionRequired" class="form-check-input">
                            <label class="form-check-label" for="dataDestructionRequired">
                                Data Destruction Required
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Data Destruction Method</label>
                        <select name="data_destruction_method" class="form-input" id="dataDestructionMethod">
                            <option value="">— Select Method —</option>
                            <?php foreach (DATA_DESTRUCTION_METHODS as $method): ?>
                            <option value="<?= $method ?>"><?= ucwords(str_replace('_', ' ', $method)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Resale Value</label>
                        <input type="number" name="resale_value" class="form-input" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Disposal Cost</label>
                        <input type="number" name="disposal_cost" class="form-input" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3" placeholder="Additional notes about this disposal"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Disposal Request
                </button>
                <a href="<?= url('/assets/retired') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('dataDestructionRequired')?.addEventListener('change', function() {
        document.getElementById('dataDestructionMethod').disabled = !this.checked;
    });
</script>