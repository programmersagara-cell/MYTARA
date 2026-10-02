<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Manage Active Concerns</h1>
        <p class="page-subtitle">Review, repair, or cancel active tickets</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets/history') ?>" class="btn btn-outline"><i class="fas fa-history"></i> History</a>
        <a href="<?= url('/tickets/analytics') ?>" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Analytics</a>
    </div>
</div>

<?php if (isset($stats)): ?>
<div class="row mb-3">
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fas fa-inbox"></i></div>
            <div>
                <div class="stat-value"><?= (int) $stats['active'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="stat-value"><?= (int) $stats['accepted'] ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-value"><?= (int) $stats['repaired'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="stat-value"><?= (int) $stats['canceled'] ?></div>
                <div class="stat-label">Canceled</div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-icon bg-wrench-soft"><i class="fas fa-wrench"></i></div>
            <div>
                <div class="stat-value"><?= (int) $stats['repairedToday'] ?></div>
                <div class="stat-label">Completed Today</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Active Tickets
    </div>
    <div class="card-body">
<form method="get" action="<?= url('/tickets/manage') ?>" class="row mb-3">
            <div class="col-4">
                <select name="department" class="form-input">
                    <option value="">All Departments</option>
                    <?php foreach (($departments ?? []) as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $filterDepartment === $dept['department'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Sender</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Submitted</th>
                        <th>Status / Repair Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No active tickets found.</td></tr>
                    <?php else: foreach ($tickets as $t): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($t['ticket_number']) ?></strong>
                            <br>
                            <span class="text-muted small"><?= \App\Helpers\Format::truncate($t['description'], 40) ?></span>
                        </td>
                        <td><?= htmlspecialchars($t['sender_name']) ?></td>
                        <td><?= htmlspecialchars($t['department']) ?></td>
                        <td>
                            <span class="badge badge-<?= match(strtolower($t['priority'])) {
                                'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary'
                            } ?>"><?= htmlspecialchars($t['priority']) ?></span>
                        </td>
                        <td class="text-muted small"><?= \App\Helpers\Format::datetime($t['submitted_at']) ?></td>
                        <?php $isAccepted = ($t['status'] ?? '') === 'accepted'; ?>
                        <td>
                            <?php if ($isAccepted): ?>
                                <span class="badge badge-info"><i class="fas fa-hourglass-half"></i> ACCEPTED — IN PROGRESS</span>
                                <div class="small mt-1">
                                    <i class="fas fa-stopwatch"></i>
                                    Elapsed: <strong class="elapsed-timer" data-starts="<?= (int) strtotime((string) $t['accepted_at']) ?>">--:--:--</strong>
                                </div>
                                <div class="small text-muted">
                                    Started: <?= \App\Helpers\Format::datetime($t['accepted_at']) ?>
                                    <?php if (!empty($t['accepted_by'])): ?> · by <?= htmlspecialchars($t['accepted_by']) ?><?php endif; ?>
                                </div>
                                <div class="small text-muted">
                                    Repaired By: <strong><?= htmlspecialchars($t['accepted_by'] ?? 'IT Support') ?></strong>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-secondary">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex gap-1">
                            <?php if (!$isAccepted): ?>
                            <!-- Accept (PENDING → ACCEPTED): confirm + repair duration -->
                            <button type="button" class="btn btn-sm btn-success" onclick="showModal('acceptModal<?= $t['id'] ?>')" title="Accept"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                            <!-- Mark as repaired (early completion); Repaired By stays the accepting admin -->
                            <form method="post" action="<?= url('/tickets/' . $t['id'] . '/complete') ?>" class="d-inline" onsubmit="var b=this.querySelector('button'); if(b.disabled){return false;} b.disabled=true; return true;">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-primary" title="Mark as repaired" data-confirm="Mark this ticket as repaired?"><i class="fas fa-clipboard-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="showModal('cancelModal<?= $t['id'] ?>')" title="Cancel"><i class="fas fa-times"></i></button>

<?php if (!$isAccepted): ?>
<!-- Accept modal -->
                            <div class="modal-overlay" id="acceptModal<?= $t['id'] ?>">
                                <div class="modal">
                                    <div class="modal-header">
                                        <h3>Accept Ticket <?= htmlspecialchars($t['ticket_number']) ?></h3>
                                        <button type="button" class="modal-close" onclick="hideModal('acceptModal<?= $t['id'] ?>')">&times;</button>
                                    </div>
                                    <form method="post" action="<?= url('/tickets/' . $t['id'] . '/accept') ?>" onsubmit="var b=this.querySelector('button[type=submit]'); if(b.disabled){return false;} b.disabled=true; return true;">
                                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                        <div class="modal-body">
                                            <p class="small text-muted">
                                                <strong><?= htmlspecialchars($t['sender_name']) ?></strong> (<?= htmlspecialchars($t['department']) ?>):<br>
                                                <?= nl2br(htmlspecialchars(\App\Helpers\Format::truncate($t['description'], 160))) ?>
                                            </p>
                                            <div class="form-group">
                                                <label class="form-label">Accept this ticket?</label>
                                                <p class="small text-muted">Accepting records you as the person responsible for the repair (Repaired By) and starts the repair timer immediately. Click <strong>Mark as Repaired</strong> on this ticket when the job is done — the actual repair time is then recorded automatically.</p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" onclick="hideModal('acceptModal<?= $t['id'] ?>')">Close</button>
                                            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Accept &amp; Start Repair Timer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
<?php endif; ?>

<!-- Cancel modal -->
                            <div class="modal-overlay" id="cancelModal<?= $t['id'] ?>">
                                <div class="modal">
                                    <div class="modal-header">
                                        <h3>Cancel Ticket <?= htmlspecialchars($t['ticket_number']) ?></h3>
                                        <button type="button" class="modal-close" onclick="hideModal('cancelModal<?= $t['id'] ?>')">&times;</button>
                                    </div>
                                    <form method="post" action="<?= url('/tickets/' . $t['id'] . '/cancel') ?>">
                                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea name="reason" class="form-input" rows="3" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" onclick="hideModal('cancelModal<?= $t['id'] ?>')">Close</button>
                                            <button type="submit" class="btn btn-danger">Cancel Ticket</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

<?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer">
            <nav class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&department=<?= urlencode($filterDepartment) ?>" class="page-link <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .elapsed-timer { font-variant-numeric: tabular-nums; }
</style>
<script>
(function() {
    'use strict';

    var timers = document.querySelectorAll('.elapsed-timer');
    if (!timers.length) return;

    // Anchor the client clock to the server clock so every admin/device sees
    // the same elapsed time regardless of local clock skew. Elapsed time is
    // ALWAYS computed as (now - started), never by incrementing a stored
    // value, so refreshing the page (or opening it from another device)
    // shows the correct running total. It stops server-side when the ticket
    // is marked as repaired (repaired_date).
    var serverNowMs = <?= (int) ($serverNow ?? time()) ?> * 1000;
    var offsetMs = serverNowMs - Date.now();

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function formatElapsed(ms) {
        var total = Math.max(0, Math.floor(ms / 1000));
        var h = Math.floor(total / 3600);
        var m = Math.floor((total % 3600) / 60);
        var s = total % 60;
        return pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    function tick() {
        var nowMs = Date.now() + offsetMs;

        timers.forEach(function(el) {
            var started = parseInt(el.dataset.starts, 10) * 1000;
            el.textContent = formatElapsed(nowMs - started);
        });
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
