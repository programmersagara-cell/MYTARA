<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">IT Concerns</h1>
        <p class="page-subtitle">Submit and track your IT support tickets</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets/my-history') ?>" class="btn btn-outline">
            <i class="fas fa-history"></i> My History
        </a>
        <?php if (isset($user) && $user && $user['role'] === 'admin'): ?>
        <a href="<?= url('/tickets/manage') ?>" class="btn btn-outline">
            <i class="fas fa-tasks"></i> Manage Active
        </a>
        <a href="<?= url('/tickets/history') ?>" class="btn btn-outline">
            <i class="fas fa-clock-rotate-left"></i> All History
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($unreadCount) && $unreadCount > 0): ?>
<div class="alert alert-info">
    <i class="fas fa-bell"></i> You have <?= (int) $unreadCount ?> unread notification(s).
</div>
<?php endif; ?>

<div class="row">
    <!-- Submit form -->
    <div class="col-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Submit New Concern
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('/tickets') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">

                    <div class="form-group">
                        <label for="sender_name" class="form-label">Sender Name <span class="text-danger">*</span></label>
                        <input type="text" id="sender_name" name="sender_name" class="form-input"
                               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>

<div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" id="department" class="form-input" readonly
                               value="<?= htmlspecialchars($user['department'] ?? 'General') ?>">
                        <input type="hidden" name="department" value="<?= htmlspecialchars($user['department'] ?? 'General') ?>">
                        <small class="text-muted">Your department is automatically identified from your account.</small>
                    </div>

                    <div class="form-group">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-input">
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-input" rows="4" required
                                  placeholder="Describe the issue..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image" class="form-label">Attachment (optional)</label>
                        <input type="file" id="image" name="image" class="form-input" accept="image/*">
                        <small class="text-muted">JPG, PNG, GIF, WebP — max 5MB</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-paper-plane"></i> Submit Concern
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- My active tickets -->
    <div class="col-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> My Active Concerns
                <span class="badge badge-primary float-end"><?= count($activeTickets ?? []) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeTickets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No active concerns.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Department</th>
                                <th>Priority</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeTickets as $t): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($t['ticket_number']) ?></strong>
                                    <br>
                                    <span class="text-muted small"><?= \App\Helpers\Format::truncate($t['description'], 40) ?></span>
                                </td>
                                <td><?= htmlspecialchars($t['department']) ?></td>
                                <td>
                                    <span class="badge badge-<?= match(strtolower($t['priority'])) {
                                        'critical' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        default => 'secondary'
                                    } ?>">
                                        <?= htmlspecialchars($t['priority']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= \App\Helpers\Format::datetime($t['submitted_at']) ?></td>
                                <td>
                                    <?php if (($t['status'] ?? '') === 'accepted'): ?>
                                        <span class="badge badge-info">Accepted</span>
                                        <div class="small text-muted">Your request has been accepted by IT support.</div>
                                        <div class="small text-muted">Repair Status: <strong>In Progress</strong></div>
                                        <?php if (!empty($t['accepted_at'])): ?>
                                        <div class="small text-muted">Started: <?= date('M d, Y h:i A', (int) strtotime((string) $t['accepted_at'])) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'admin' || (int) $t['user_id'] === (int) $user['id']): ?>
                                    <a href="<?= url('/tickets/' . $t['id'] . '/edit') ?>" class="btn btn-sm btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (($t['status'] ?? '') === 'accepted'): ?>
                                    <form method="post" action="<?= url('/tickets/' . $t['id'] . '/repair-my') ?>" class="d-inline" onsubmit="return confirm('Mark this concern as repaired?');">
                                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark repaired">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

