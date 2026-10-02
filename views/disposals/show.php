<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Disposal: <?= htmlspecialchars($disposal['asset_tag'] ?? '') ?></h1>
        <p class="page-subtitle">Disposal record details</p>
    </div>
    <div class="page-actions">
        <?php if (($disposal['disposal_status'] ?? '') === 'disposed'): ?>
        <a href="<?= url('/disposals/' . $disposal['id'] . '/certificate') ?>" class="btn btn-primary">
            <i class="fas fa-file-alt"></i> Certificate
        </a>
        <?php endif; ?>
        <a href="<?= url('/disposals') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-8">
        <!-- Asset Information -->
        <div class="card">
            <div class="card-header">
                <h3>Asset Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Asset Tag</label>
                        <span class="detail-value"><?= htmlspecialchars($disposal['asset_tag'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Hostname</label>
                        <span><?= htmlspecialchars($disposal['hostname'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Type</label>
                        <span><?= \App\Helpers\Format::assetType($disposal['asset_type'] ?? '') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Serial Number</label>
                        <code><?= htmlspecialchars($disposal['serial_number'] ?? '—') ?></code>
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <span><?= htmlspecialchars($disposal['department_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Previous User</label>
                        <span><?= htmlspecialchars($disposal['assigned_user_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Vendor</label>
                        <span><?= htmlspecialchars($disposal['vendor'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Model</label>
                        <span><?= htmlspecialchars($disposal['model'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Purchase Date</label>
                        <span><?= \App\Helpers\Format::date($disposal['purchase_date']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retirement Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Retirement Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Retirement Date</label>
                        <span><?= \App\Helpers\Format::datetime($disposal['retirement_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Reason</label>
                        <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $disposal['retirement_reason'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disposal Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Disposal Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Status</label>
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
                    </div>
                    <div class="detail-item">
                        <label>Method</label>
                        <span><?= $disposal['disposal_method'] ? ucwords(str_replace('_', ' ', $disposal['disposal_method'])) : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Disposal Date</label>
                        <span><?= \App\Helpers\Format::datetime($disposal['disposal_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Vendor</label>
                        <span><?= htmlspecialchars($disposal['disposal_vendor'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Location</label>
                        <span><?= htmlspecialchars($disposal['disposal_location'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Certificate #</label>
                        <span><?= htmlspecialchars($disposal['certificate_number'] ?? '—') ?></span>
                    </div>
                    <?php if ($disposal['approved_by']): ?>
                    <div class="detail-item">
                        <label>Approved By</label>
                        <span><?= htmlspecialchars($disposal['approved_by_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Approval Date</label>
                        <span><?= \App\Helpers\Format::datetime($disposal['approval_date']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($disposal['disposed_by']): ?>
                    <div class="detail-item">
                        <label>Disposed By</label>
                        <span><?= htmlspecialchars($disposal['disposed_by_name'] ?? '—') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($disposal['notes']): ?>
                <div class="mt-3">
                    <label>Notes:</label>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($disposal['notes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Destruction -->
        <?php if ($disposal['data_destruction_required']): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Data Destruction</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Method</label>
                        <span><?= $disposal['data_destruction_method'] ? ucwords(str_replace('_', ' ', $disposal['data_destruction_method'])) : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Date</label>
                        <span><?= \App\Helpers\Format::datetime($disposal['data_destruction_date']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Performed By</label>
                        <span><?= htmlspecialchars($disposal['data_destruction_by_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Verified</label>
                        <span class="badge badge-<?= $disposal['data_destruction_verified'] ? 'success' : 'warning' ?>">
                            <?= $disposal['data_destruction_verified'] ? 'Yes' : 'No' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Financial Information -->
        <?php if ($disposal['resale_value'] !== null || $disposal['disposal_cost'] !== null): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Financial Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Original Cost</label>
                        <span><?= \App\Helpers\Format::number($disposal['purchase_price'] ?? $disposal['cost'] ?? 0) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Resale Value</label>
                        <span><?= $disposal['resale_value'] !== null ? \App\Helpers\Format::number($disposal['resale_value']) : '—' ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Disposal Cost</label>
                        <span><?= $disposal['disposal_cost'] !== null ? \App\Helpers\Format::number($disposal['disposal_cost']) : '—' ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attachments -->
        <?php if (!empty($attachments)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Attachments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attachments as $attachment): ?>
                            <tr>
                                <td><?= htmlspecialchars($attachment['original_name']) ?></td>
                                <td><?= htmlspecialchars($attachment['uploaded_by_name'] ?? '—') ?></td>
                                <td><?= \App\Helpers\Format::datetime($attachment['created_at']) ?></td>
                                <td>
                                    <a href="<?= UPLOADS_URL ?>/disposals/<?= $disposal['id'] ?>/<?= urlencode($attachment['filename']) ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-4">
        <!-- Workflow Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Workflow Actions</h3>
            </div>
            <div class="card-body">
                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                    <?php if ($disposal['disposal_status'] === 'pending_disposal'): ?>
                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/submit') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Submit for Approval
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (in_array($disposal['disposal_status'], ['pending_disposal', 'awaiting_approval']) && $user['role'] === 'admin'): ?>
                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/approve') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/reject') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <div class="form-group">
                                <input type="text" name="notes" class="form-input" placeholder="Rejection reason">
                            </div>
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($disposal['disposal_status'] === 'approved'): ?>
                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/schedule') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <div class="form-group">
                                <label class="form-label">Disposal Date</label>
                                <input type="date" name="disposal_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Method</label>
                                <select name="disposal_method" class="form-input" required>
                                    <?php foreach (DISPOSAL_METHODS as $method): ?>
                                    <option value="<?= $method ?>"><?= ucwords(str_replace('_', ' ', $method)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="disposal_location" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="disposal_vendor" class="form-input">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-calendar-check"></i> Schedule
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($disposal['disposal_status'] === 'scheduled'): ?>
                        <?php if ($disposal['data_destruction_required']): ?>
                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/record-data-destruction') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <h5 class="mb-3">Data Destruction</h5>
                            <div class="form-group">
                                <label class="form-label">Method</label>
                                <select name="data_destruction_method" class="form-input" required>
                                    <?php foreach (DATA_DESTRUCTION_METHODS as $method): ?>
                                    <option value="<?= $method ?>"><?= ucwords(str_replace('_', ' ', $method)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="data_destruction_date" class="form-input" required>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="data_destruction_verified" value="1" class="form-check-input">
                                <label class="form-check-label">Verified</label>
                            </div>
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-shredder"></i> Record Data Destruction
                            </button>
                        </form>
                        <?php endif; ?>

                        <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/complete') ?>" class="mb-3">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                            <h5 class="mb-3">Complete Disposal</h5>
                            <div class="form-group">
                                <label class="form-label">Certificate Number</label>
                                <input type="text" name="certificate_number" class="form-input" placeholder="Auto-generated if empty">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Disposal Date</label>
                                <input type="date" name="disposal_date" class="form-input" value="<?= date('Y-m-d') ?>">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check-circle"></i> Complete Disposal
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!in_array($disposal['disposal_status'], ['disposed', 'cancelled', 'rejected', 'archived'])): ?>
                    <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this disposal?');">
                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                        <button type="submit" class="btn btn-secondary btn-block">
                            <i class="fas fa-ban"></i> Cancel Disposal
                        </button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted text-center">View-only access. Contact an administrator for disposal actions.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload Attachment -->
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Upload Attachment</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/disposals/' . $disposal['id'] . '/upload-attachment') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                    <div class="form-group">
                        <input type="file" name="attachment" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>