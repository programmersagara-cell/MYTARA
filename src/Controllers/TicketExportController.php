<?php
/**
 * TicketExportController
 * Analytics exports for tickets: CSV (ticket-level, status-grouped, filtered).
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Concern;

class TicketExportController extends Controller
{
    /**
     * Export ticket-level analytics as CSV (admin).
     */
    public function filtered(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $scope = (string) $this->request->query('scope', 'monthly');
        $status = (string) $this->request->query('status', 'all');
        $year = $this->request->query('year') !== null ? (int) $this->request->query('year') : null;
        $month = $this->request->query('month') !== null ? (int) $this->request->query('month') : null;
        $week = $this->request->query('week') !== null ? (int) $this->request->query('week') : null;
        $date = (string) $this->request->query('date', '');

        $rows = Concern::analyticsByDepartmentStatus($scope, $status, $year, $month, $week, $date);

        $filename = 'ticket_export_' . date('Y-m-d') . '.csv';
        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->header('Cache-Control', 'no-cache');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ticket_number', 'submitted_date', 'department', 'sender_name',
            'description', 'status', 'priority', 'repaired_date', 'canceled_date', 'remarks'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['ticket_number'] ?? '',
                $r['submitted_at'] ?? '',
                $r['department'] ?? '',
                $r['sender_name'] ?? '',
                $r['description'] ?? '',
                $r['status'] ?? '',
                $r['priority'] ?? '',
                $r['repaired_date'] ?? '',
                $r['canceled_date'] ?? '',
                $r['remarks'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
