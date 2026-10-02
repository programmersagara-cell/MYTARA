<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Ticket History & Remarks</h1>
        <p class="page-subtitle">Search repaired and canceled tickets</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets/manage') ?>" class="btn btn-outline"><i class="fas fa-tasks"></i> Manage Active</a>
        <a href="<?= url('/tickets/analytics') ?>" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Analytics</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
<form method="get" action="<?= url('/tickets/history') ?>" class="row mb-3">
            <div class="col-4">
                <input type="text" name="q" class="form-input" placeholder="Search ticket #, sender, description, remarks" value="<?= htmlspecialchars($q ?? '') ?>">
            </div>
            <div class="col-2">
                <select name="department" class="form-input">
                    <option value="">All Departments</option>
                    <?php foreach (($departments ?? []) as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['department']) ?>" <?= ($filterDepartment ?? '') === $dept['department'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <select name="status" class="form-input">
                    <option value="">All Status</option>
                    <option value="repaired" <?= ($filterStatus ?? '') === 'repaired' ? 'selected' : '' ?>>Repaired</option>
                    <option value="accepted" <?= ($filterStatus ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="canceled" <?= ($filterStatus ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-2">
                <select name="period" class="form-input">
                    <option value="">All Time</option>
                    <option value="daily" <?= ($filterPeriod ?? '') === 'daily' ? 'selected' : '' ?>>Today</option>
                    <option value="weekly" <?= ($filterPeriod ?? '') === 'weekly' ? 'selected' : '' ?>>This Week</option>
                    <option value="monthly" <?= ($filterPeriod ?? '') === 'monthly' ? 'selected' : '' ?>>This Month</option>
                </select>
            </div>
            <div class="col-2">
                <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Sender</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No tickets found.</td></tr>
                    <?php else: foreach ($tickets as $t): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t['ticket_number']) ?></strong></td>
                        <td><?= htmlspecialchars($t['sender_name']) ?></td>
                        <td><?= htmlspecialchars($t['department']) ?></td>
                        <td>
                            <span class="badge badge-<?= $t['status'] === 'repaired' ? 'success' : ($t['status'] === 'accepted' ? 'info' : 'danger') ?>">
                                <?= htmlspecialchars(ucfirst($t['status'])) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= \App\Helpers\Format::datetime($t['submitted_at']) ?></td>
                        <td class="small"><?= htmlspecialchars(\App\Helpers\Format::truncate($t['remarks'] ?? '', 40)) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline" onclick="showModal('viewModal<?= $t['id'] ?>')" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>

<!-- View Modal -->
                    <div class="modal-overlay" id="viewModal<?= $t['id'] ?>">
                        <div class="modal">
                            <div class="modal-header">
                                <h3>Ticket <?= htmlspecialchars($t['ticket_number']) ?></h3>
                                <button type="button" class="modal-close" onclick="hideModal('viewModal<?= $t['id'] ?>')">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Sender:</strong> <?= htmlspecialchars($t['sender_name']) ?></p>
                                <p><strong>Department:</strong> <?= htmlspecialchars($t['department']) ?></p>
                                <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($t['status'])) ?></p>
                                <p><strong>Priority:</strong> <?= htmlspecialchars($t['priority']) ?></p>
                                <p><strong>Submitted:</strong> <?= \App\Helpers\Format::datetime($t['submitted_at']) ?></p>
                                <?php if (!empty($t['accepted_at'])): ?>
                                    <p><strong>Accepted:</strong> <?= \App\Helpers\Format::datetime($t['accepted_at']) ?> by <?= htmlspecialchars($t['accepted_by'] ?? 'IT Support') ?></p>
                                <?php endif; ?>
                                <?php if ($t['repaired_date']): ?>
                                    <p><strong>Repaired:</strong> <?= \App\Helpers\Format::datetime($t['repaired_date']) ?> by <?= htmlspecialchars($t['repaired_by']) ?></p>
                                    <?php if (!empty($t['accepted_at'])): ?>
                                        <?php $repairSeconds = max(0, strtotime((string) $t['repaired_date']) - strtotime((string) $t['accepted_at'])); ?>
                                        <p><strong>Actual Repair Time:</strong> <?= sprintf('%02d:%02d:%02d', intdiv($repairSeconds, 3600), intdiv($repairSeconds % 3600, 60), $repairSeconds % 60) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($t['canceled_date']): ?>
                                    <p><strong>Canceled:</strong> <?= \App\Helpers\Format::datetime($t['canceled_date']) ?> by <?= htmlspecialchars($t['canceled_by']) ?></p>
                                    <p><strong>Reason:</strong> <?= htmlspecialchars($t['canceled_reason']) ?></p>
                                <?php endif; ?>
                                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($t['description'])) ?></p>
                                <?php if (!empty($t['image_path'])): ?>
                                    <p><strong>Attachment:</strong></p>
                                    <img src="<?= UPLOADS_URL ?>/<?= htmlspecialchars($t['image_path']) ?>" alt="Attachment" class="img-thumbnail" style="max-height: 200px;">
                                <?php endif; ?>

                                <hr>
                                <form method="post" action="<?= url('/tickets/history/remarks/' . $t['id']) ?>">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                    <div class="form-group">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="remarks" class="form-input" rows="3"><?= htmlspecialchars($t['remarks'] ?? '') ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Remarks</button>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" onclick="hideModal('viewModal<?= $t['id'] ?>')">Close</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

<?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer">
            <nav class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= urlencode($q ?? '') ?>&department=<?= urlencode($filterDepartment ?? '') ?>&status=<?= urlencode($filterStatus ?? '') ?>&period=<?= urlencode($filterPeriod ?? '') ?>" class="page-link <?= $i === (int) ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
