<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Import Assets</h1>
        <p class="page-subtitle">Bulk import assets from a CSV file</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/assets') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Ledger
        </a>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-upload"></i> Upload CSV</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/assets/import') ?>" enctype="multipart/form-data">
                    <?= \App\Helpers\Security::csrfField() ?>
                    <div class="form-group">
                        <label class="form-label">Select CSV File</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="csv_file" class="form-input" accept=".csv" required>
                        </div>
                        <small class="text-muted">Maximum file size: 5MB</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CSV Format</label>
                        <p class="text-muted">The CSV file should have the following columns in order:</p>
                        <code class="csv-format">asset_tag, type, hostname, ip_address, mac_address, vendor, model, serial_number, os, cpu, ram_gb, storage_gb, status, location, floor, notes</code>
                    </div>
                    <div class="form-actions">
                        <a href="<?= url('/assets/import/download-sample') ?>" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Download Sample CSV
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Assets
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Import Guidelines</h3>
            </div>
            <div class="card-body">
                <ul class="guidelines">
                    <li><strong>Required fields:</strong> <code>asset_tag</code>, <code>type</code></li>
                    <li><strong>Valid types:</strong> pc, laptop, switch, server, printer, nas, nvr, other</li>
                    <li><strong>Valid statuses:</strong> active, inactive, maintenance, retired, lost, reserved</li>
                    <li>Asset tags must be unique across the system</li>
                    <li>Duplicate asset tags will be skipped</li>
                    <li>Invalid rows will be skipped and reported</li>
                </ul>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Import Summary</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Upload a CSV file to begin the import process. The system will validate each row and report any errors found.</p>
            </div>
        </div>
    </div>
</div>

<style>
.file-input-wrapper {
    position: relative;
}
.file-input-wrapper input[type="file"] {
    padding: 0.75rem;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    width: 100%;
    cursor: pointer;
    background: var(--bg-secondary);
}
.file-input-wrapper input[type="file"]:hover {
    border-color: var(--primary-color);
}
.csv-format {
    display: block;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    font-size: 0.8rem;
    word-break: break-all;
    line-height: 1.6;
}
.guidelines {
    list-style: none;
    padding: 0;
}
.guidelines li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}
.guidelines li:last-child {
    border-bottom: none;
}
</style>
