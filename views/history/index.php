<?php $layout = 'layouts/main'; ?>

<?php
// Preserve the active filters in pagination + export links.
$activeQuery = array_filter([
    'entity_type' => $filters['entity_type'] ?? '',
    'action'      => $filters['action'] ?? '',
    'user_id'     => ($filters['user_id'] ?? 0) > 0 ? (int) $filters['user_id'] : '',
    'from'        => $filters['from'] ?? '',
    'to'          => $filters['to'] ?? '',
], fn($value) => $value !== '' && $value !== null);

// Keep a non-default ordering sticky too.
if (($filters['sort'] ?? 'time') !== 'time' || ($filters['dir'] ?? 'desc') !== 'desc') {
    $activeQuery['sort'] = $filters['sort'] ?? 'time';
    $activeQuery['dir'] = $filters['dir'] ?? 'desc';
}
$pageQuery = $activeQuery ? http_build_query($activeQuery) . '&' : '';
$exportUrl = url('/history') . '?' . http_build_query(array_merge($activeQuery, ['export' => 'csv']));
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Audit Log</h1>
        <p class="page-subtitle">
            Unified audit trail of every tracked action
            <span class="text-muted small">(<?= \App\Helpers\Format::number($total) ?> entries)</span>
        </p>
    </div>
    <div class="page-actions">
        <?php if (!empty($canExport)): ?>
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/history') ?>" class="filter-form">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort'] ?? 'time') ?>">
            <input type="hidden" name="dir" value="<?= htmlspecialchars($filters['dir'] ?? 'desc') ?>">
            <div class="form-row">
                <div class="form-group col-2">
                    <label class="form-label">Entity</label>
                    <select name="entity_type" class="form-input">
                        <option value="">All entities</option>
                        <?php foreach (($entityTypes ?? []) as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= ($filters['entity_type'] ?? '') === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($type)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-input">
                        <option value="">All actions</option>
                        <?php foreach (($actions ?? []) as $action): ?>
                        <option value="<?= htmlspecialchars($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                            <?= htmlspecialchars($action) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-3">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-input">
                        <option value="">All users</option>
                        <?php foreach (($auditUsers ?? []) as $auditUser): ?>
                        <option value="<?= (int) $auditUser['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $auditUser['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($auditUser['full_name'] ?: $auditUser['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-input" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
                </div>
                <div class="form-group col-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-input" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
                </div>
                <div class="form-group col-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block" title="Apply filters">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Field / Change</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                    <?php
                    $action = (string) ($entry['action'] ?? '');
                    $badge = match (true) {
                        str_contains($action, 'failed'), str_contains($action, 'locked') => 'danger',
                        str_contains($action, 'deleted'), $action === 'logout' => 'danger',
                        str_contains($action, 'created'), str_contains($action, 'sent'), $action === 'login' => 'success',
                        str_contains($action, 'updated'), str_contains($action, 'changed'), str_contains($action, 'saved') => 'info',
                        str_contains($action, 'assigned'), str_contains($action, 'approved') => 'primary',
                        default => 'secondary',
                    };
                    $oldValue = $entry['old_value'] ?? null;
                    $newValue = $entry['new_value'] ?? null;
                    ?>
                    <tr>
                        <td>
                            <span class="text-muted small"><?= \App\Helpers\Format::datetime($entry['created_at']) ?></span>
                            <br>
                            <span class="text-muted smaller"><?= \App\Helpers\Format::relativeTime($entry['created_at']) ?></span>
                        </td>
                        <td>
                            <div class="user-cell">
                                <?php if (!empty($entry['user_avatar'])): ?>
                                    <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($entry['user_avatar']) ?>" class="avatar-sm">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($entry['user_name'] ?? $entry['user_username'] ?? 'System') ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($action) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($entry['entity_url'])): ?>
                                <a href="<?= htmlspecialchars($entry['entity_url']) ?>"><?= htmlspecialchars($entry['entity_label']) ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($entry['entity_label']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($entry['description'])): ?>
                                <br><span class="text-muted smaller"><?= htmlspecialchars(\App\Helpers\Format::truncate($entry['description'], 70)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($entry['field_changed']) || $oldValue !== null || $newValue !== null): ?>
                                <?php if (!empty($entry['field_changed'])): ?>
                                    <code class="small"><?= htmlspecialchars($entry['field_changed']) ?></code>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <?= htmlspecialchars(\App\Helpers\Format::truncate($oldValue, 40)) ?>
                                    <i class="fas fa-arrow-right"></i>
                                    <?= htmlspecialchars(\App\Helpers\Format::truncate($newValue, 40)) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="small"><?= htmlspecialchars($entry['ip_address'] ?? '—') ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted p-4">No audit entries match these filters.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <?php
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        ?>
        <div class="card-footer">
            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                <a href="?<?= $pageQuery ?>page=<?= $currentPage - 1 ?>" class="page-item"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= $pageQuery ?>page=<?= $i ?>" class="page-item <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= $pageQuery ?>page=<?= $currentPage + 1 ?>" class="page-item"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
                <span class="text-muted small ml-2">Page <?= $currentPage ?> of <?= $totalPages ?></span>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

    </div>
</div>
