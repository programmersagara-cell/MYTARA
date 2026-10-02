<?php
/**
 * AnalyticsController
 * Ticketing analytics: monthly/weekly/daily charts, summary cards, exports.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Concern;

class AnalyticsController extends Controller
{
    /**
     * Show analytics dashboard (admin).
     */
    public function index(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $year = (int) $this->request->query('year', date('Y'));

        $monthly = Concern::analyticsByMonth($year);
        $monthlyData = array_fill(1, 12, 0);
        foreach ($monthly as $row) {
            $monthlyData[(int) $row['month']] = (int) $row['count'];
        }

        $total = array_sum($monthlyData);
        $avg = $total > 0 ? round($total / 12, 1) : 0;
        $activeMonths = count(array_filter($monthlyData));

        $this->render('tickets/analytics', [
            'title' => 'Ticket Analytics',
            'year' => $year,
            'monthlyData' => $monthlyData,
            'total' => $total,
            'avg' => $avg,
            'activeMonths' => $activeMonths,
            'years' => $this->availableYears(),
        ]);
    }

    /**
     * JSON endpoint for chart refresh (AJAX polling).
     */
    public function monthlySeries(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }
        $year = (int) $this->request->query('year', date('Y'));
        $monthly = Concern::analyticsByMonth($year);
        $monthlyData = array_fill(1, 12, 0);
        foreach ($monthly as $row) {
            $monthlyData[(int) $row['month']] = (int) $row['count'];
        }
        $this->json([
            'labels' => array_keys($monthlyData),
            'data' => array_values($monthlyData),
            'total' => array_sum($monthlyData),
        ]);
    }

    /**
     * Export analytics as CSV (admin).
     */
    public function export(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $scope = (string) $this->request->query('scope', 'monthly');
        $year = $this->request->query('year') !== null ? (int) $this->request->query('year') : (int) date('Y');
        $rows = Concern::analyticsByMonth($year);

        $filename = 'ticket_analytics_' . date('Y-m-d') . '.csv';

        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->header('Cache-Control', 'no-cache');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Month', 'Count']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['month'], $row['count']]);
        }
        fclose($out);
        exit;
    }

    /**
     * Available years from data.
     */
    private function availableYears(): array
    {
        $db = \App\Core\Database::getInstance();
        $rows = $db->fetchAll("SELECT DISTINCT YEAR(submitted_at) as yr FROM concerns ORDER BY yr DESC");
        $years = array_column($rows, 'yr');
        if (empty($years)) {
            $years = [(int) date('Y')];
        }
        return $years;
    }
}
