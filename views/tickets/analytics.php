<?php $layout = 'layouts/main'; ?>

<?php $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Ticket Analytics</h1>
        <p class="page-subtitle">Monthly trend of submitted IT concerns</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets/manage') ?>" class="btn btn-outline"><i class="fas fa-tasks"></i> Manage Active</a>
        <a href="<?= url('/tickets/analytics/export?year=' . $year) ?>" class="btn btn-primary" title="Download CSV">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-value"><?= (int) $total ?></div>
                <div class="stat-label">Total Concerns (<?= (int) $year ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-value"><?= $avg ?></div>
                <div class="stat-label">Average per Month</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="stat-value"><?= (int) $activeMonths ?></div>
                <div class="stat-label">Active Months</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <i class="fas fa-chart-bar"></i> Monthly Trend
        <form method="get" action="<?= url('/tickets/analytics') ?>" class="d-inline float-end">
            <select name="year" class="form-input form-input-sm" onchange="this.form.submit()">
                <?php foreach (($years ?? []) as $y): ?>
                    <option value="<?= (int) $y ?>" <?= (int) $y === (int) $year ? 'selected' : '' ?>><?= (int) $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <canvas id="ticketChart" height="100"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-table"></i> Monthly Breakdown</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Count</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $m => $count): ?>
                    <tr>
                        <td><?= $monthNames[$m - 1] ?></td>
                        <td><?= (int) $count ?></td>
                        <td>
                            <div class="progress">
                                <?php $pct = $total > 0 ? round($count / $total * 100) : 0; ?>
                                <div class="progress-bar" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var data = <?= json_encode(array_values($monthlyData)) ?>;
    var labels = <?= json_encode($monthNames) ?>;

    // Chart.js if available
    if (window.Chart) {
        new Chart(document.getElementById('ticketChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Concerns',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>
