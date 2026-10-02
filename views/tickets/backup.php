<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Incremental Backup Viewer</h1>
        <p class="page-subtitle">Browse and download ticketing backups</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets/backup/run') ?>" class="btn btn-primary" data-confirm="Run an incremental backup now?">
            <i class="fas fa-database"></i> Run Backup
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-archive"></i> Backup Files</div>
    <div class="card-body">
        <?php if (empty($backups)): ?>
            <div class="empty-state">
                <i class="fas fa-database"></i>
                <p>No incremental backups found yet. Click "Run Backup" to create one.</p>
            </div>
        <?php else: ?>
            <?php foreach ($backups as $date => $files): ?>
            <h6 class="mb-2 mt-3"><i class="fas fa-calendar-day"></i> <?= htmlspecialchars($date) ?></h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Records</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($f['name']) ?></code></td>
                            <td><?= (int) $f['records'] ?></td>
                            <td><?= \App\Helpers\Format::fileSize((int) $f['size']) ?></td>
                            <td class="text-muted small"><?= $f['modified'] ?></td>
                            <td>
                                <a href="<?= url('/tickets/backup/download?file=' . urlencode($date . '/' . $f['name'])) ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
