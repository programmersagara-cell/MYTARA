<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Disposal Certificate</h1>
        <p class="page-subtitle">Certificate of asset disposal</p>
    </div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Certificate
        </button>
        <a href="<?= url('/disposals/' . $disposal['id']) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="certificate-container">
    <div class="certificate">
        <div class="certificate-header">
            <h2><?= htmlspecialchars($companyName) ?></h2>
            <h3>CERTIFICATE OF DISPOSAL</h3>
            <p>Certificate No: <?= htmlspecialchars($disposal['certificate_number'] ?? 'N/A') ?></p>
        </div>

        <div class="certificate-body">
            <p>This certifies that the following IT asset has been properly retired and disposed of in accordance with company policies and procedures:</p>

            <table class="table table-bordered">
                <tr>
                    <th>Disposal ID</th>
                    <td>DSP-<?= str_pad((string)$disposal['id'], 5, '0', STR_PAD_LEFT) ?></td>
                </tr>
                <tr>
                    <th>Asset Tag</th>
                    <td><?= htmlspecialchars($disposal['asset_tag'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Asset Description</th>
                    <td>
                        <?= \App\Helpers\Format::assetType($disposal['asset_type'] ?? '') ?>
                        <?php if ($disposal['vendor'] || $disposal['model']): ?>
                            - <?= htmlspecialchars($disposal['vendor'] ?? '') ?> <?= htmlspecialchars($disposal['model'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Serial Number</th>
                    <td><?= htmlspecialchars($disposal['serial_number'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Hostname</th>
                    <td><?= htmlspecialchars($disposal['hostname'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Previous Department</th>
                    <td><?= htmlspecialchars($disposal['department_name'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Previous User</th>
                    <td><?= htmlspecialchars($disposal['assigned_user_name'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Retirement Date</th>
                    <td><?= \App\Helpers\Format::date($disposal['retirement_date']) ?></td>
                </tr>
                <tr>
                    <th>Retirement Reason</th>
                    <td><?= ucwords(str_replace('_', ' ', $disposal['retirement_reason'] ?? 'N/A')) ?></td>
                </tr>
                <tr>
                    <th>Disposal Date</th>
                    <td><?= \App\Helpers\Format::date($disposal['disposal_date']) ?></td>
                </tr>
                <tr>
                    <th>Disposal Method</th>
                    <td><?= $disposal['disposal_method'] ? ucwords(str_replace('_', ' ', $disposal['disposal_method'])) : 'N/A' ?></td>
                </tr>
                <tr>
                    <th>Data Destruction Method</th>
                    <td><?= $disposal['data_destruction_method'] ? ucwords(str_replace('_', ' ', $disposal['data_destruction_method'])) : 'N/A' ?></td>
                </tr>
                <tr>
                    <th>Disposal Vendor</th>
                    <td><?= htmlspecialchars($disposal['disposal_vendor'] ?? 'N/A') ?></td>
                </tr>
            </table>

            <p class="mt-4">This asset has been permanently removed from service and is no longer part of the company's IT inventory.</p>
        </div>

        <div class="certificate-footer">
            <div class="row">
                <div class="col-6">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <p><strong>Approved By</strong></p>
                        <p><?= htmlspecialchars($disposal['approved_by_name'] ?? 'N/A') ?></p>
                        <p><?= \App\Helpers\Format::date($disposal['approval_date']) ?></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <p><strong>Disposed By</strong></p>
                        <p><?= htmlspecialchars($disposal['disposed_by_name'] ?? 'N/A') ?></p>
                        <p><?= \App\Helpers\Format::date($disposal['disposal_date']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.certificate-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.certificate {
    border: 3px double #333;
    padding: 30px;
    background: #fff;
}
.certificate-header {
    text-align: center;
    border-bottom: 2px solid #333;
    padding-bottom: 20px;
    margin-bottom: 20px;
}
.certificate-header h2 { color: #2c3e50; margin-bottom: 10px; }
.certificate-header h3 { color: #34495e; letter-spacing: 2px; }
.certificate-body { padding: 10px 0; }
.certificate-footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 2px solid #333;
}
.signature-block {
    text-align: center;
    padding: 20px;
}
.signature-line {
    border-bottom: 1px solid #333;
    margin-bottom: 10px;
    height: 40px;
}
@media print {
    .page-header, .sidebar, .main-header, .sidebar-footer { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .certificate-container { max-width: 100%; padding: 0; }
    body { background: #fff !important; }
}
</style>