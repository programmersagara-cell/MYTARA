<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Ticket History</h1>
        <p class="page-subtitle">All tickets you have submitted, their status, and what happened to them</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets') ?>" class="btn btn-outline">
            <i class="fas fa-plus-circle"></i> New Concern
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Status filter -->
        <form method="get" action="<?= url('/tickets/my-history') ?>" class="row mb-3">
            <div class="col-3">
                <select name="status" class="form-input" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filterStatus ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="accepted" <?= ($filterStatus ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="repaired" <?= ($filterStatus ?? '') === 'repaired' ? 'selected' : '' ?>>Repaired</option>
                    <option value="canceled" <?= ($filterStatus ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-9 d-flex align-items-center text-muted small">
                <?= (int) ($total ?? 0) ?> ticket(s) found
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>What Happened</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No tickets found.</td></tr>
                    <?php else: foreach ($tickets as $t): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($t['ticket_number']) ?></strong><br>
                            <span class="text-muted small"><?= \App\Helpers\Format::truncate($t['description'], 50) ?></span>
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
                        <td>
                            <?php if ($t['status'] === 'active'): ?>
                                <span class="badge badge-primary">Active</span>
                            <?php elseif ($t['status'] === 'accepted'): ?>
                                <span class="badge badge-info">Accepted</span>
                            <?php elseif ($t['status'] === 'repaired'): ?>
                                <span class="badge badge-success">Repaired</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Canceled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= \App\Helpers\Format::datetime($t['submitted_at']) ?></td>
                        <td class="small">
                            <?php if ($t['status'] === 'active'): ?>
                                <span class="text-muted">Awaiting action from IT.</span>
                            <?php elseif ($t['status'] === 'repaired'): ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> Repaired
                                    on <?= \App\Helpers\Format::datetime($t['repaired_date']) ?>
                                    by <?= htmlspecialchars($t['repaired_by'] ?? 'IT') ?></span>
                            <?php else: ?>
                                <span class="text-danger"><i class="fas fa-times-circle"></i> Canceled
                                    on <?= \App\Helpers\Format::datetime($t['canceled_date']) ?>
                                    by <?= htmlspecialchars($t['canceled_by'] ?? 'IT') ?></span><br>
                                <span class="text-muted">Reason: <?= htmlspecialchars(\App\Helpers\Format::truncate($t['canceled_reason'] ?? '', 60)) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($t['remarks'])): ?>
                                <br><span class="text-muted"><i class="fas fa-comment"></i> Remarks: <?= htmlspecialchars(\App\Helpers\Format::truncate($t['remarks'], 60)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline" onclick="showModal('myTicketModal<?= $t['id'] ?>')" title="View details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Details Modal -->
                    <div class="modal-overlay" id="myTicketModal<?= $t['id'] ?>">
                        <div class="modal">
                            <div class="modal-header">
                                <h3>Ticket <?= htmlspecialchars($t['ticket_number']) ?></h3>
                                <button type="button" class="modal-close" onclick="hideModal('myTicketModal<?= $t['id'] ?>')">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Status:</strong>
                                    <?php if ($t['status'] === 'active'): ?>
                                        <span class="badge badge-primary">Active</span>
                                    <?php elseif ($t['status'] === 'accepted'): ?>
                                        <span class="badge badge-info">Accepted</span>
                                    <?php elseif ($t['status'] === 'repaired'): ?>
                                        <span class="badge badge-success">Repaired</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Canceled</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Sender:</strong> <?= htmlspecialchars($t['sender_name']) ?></p>
                                <p><strong>Department:</strong> <?= htmlspecialchars($t['department']) ?></p>
                                <p><strong>Priority:</strong> <?= htmlspecialchars($t['priority']) ?></p>
                                <p><strong>Submitted:</strong> <?= \App\Helpers\Format::datetime($t['submitted_at']) ?></p>
                                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($t['description'])) ?></p>
                                <?php if (!empty($t['image_path'])): ?>
                                    <p><strong>Attachment:</strong></p>
                                    <img src="<?= UPLOADS_URL ?>/<?= htmlspecialchars($t['image_path']) ?>" alt="Attachment" class="img-thumbnail" style="max-height: 200px;">
                                <?php endif; ?>

                                <hr>
                                <?php if ($t['status'] === 'accepted'): ?>
                                    <p><strong>Accepted:</strong> IT support accepted this ticket on <?= \App\Helpers\Format::datetime($t['accepted_at']) ?><?= !empty($t['accepted_by']) ? ' by ' . htmlspecialchars($t['accepted_by']) : '' ?>.</p>
                                    <?php if (!empty($t['accepted_at'])): ?>
                                        <p><strong>Repair Started:</strong> <?= \App\Helpers\Format::datetime($t['accepted_at']) ?></p>
                                    <?php endif; ?>
                                <?php elseif ($t['status'] === 'repaired'): ?>
                                    <p><strong>Resolved:</strong> This ticket was marked as repaired on <?= \App\Helpers\Format::datetime($t['repaired_date']) ?> by <?= htmlspecialchars($t['repaired_by'] ?? 'IT') ?>.</p>
                                <?php elseif ($t['status'] === 'canceled'): ?>
                                    <p><strong>Canceled:</strong> This ticket was canceled on <?= \App\Helpers\Format::datetime($t['canceled_date']) ?> by <?= htmlspecialchars($t['canceled_by'] ?? 'IT') ?>.</p>
                                    <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($t['canceled_reason'] ?? '')) ?></p>
                                <?php else: ?>
                                    <p class="text-muted">This ticket is still active and awaiting IT action.</p>
                                <?php endif; ?>
                                <?php if (!empty($t['remarks'])): ?>
                                    <p><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($t['remarks'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" onclick="hideModal('myTicketModal<?= $t['id'] ?>')">Close</button>
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
                    <a href="?page=<?= $i ?>&status=<?= urlencode($filterStatus ?? '') ?>" class="page-link <?= $i === (int) ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>