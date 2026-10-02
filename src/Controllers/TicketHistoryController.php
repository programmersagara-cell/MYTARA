<?php
/**
 * TicketHistoryController
 * History & remarks for repaired/canceled tickets (admin).
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Concern;
use App\Services\TicketService;
use App\Services\AuditService;

class TicketHistoryController extends Controller
{
    private TicketService $ticketService;
    private ?AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketService = new TicketService();
        try {
            $this->auditService = new AuditService();
        } catch (\Throwable $e) {
            $this->auditService = null;
        }
    }

    /**
     * Show history of repaired/canceled tickets with search/filter/pagination.
     */
    public function index(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $page = (int) $this->request->query('page', 1);
        $query = (string) $this->request->query('q', '');
        $department = (string) $this->request->query('department', '');
        $status = (string) $this->request->query('status', '');
        $period = (string) $this->request->query('period', '');

        $result = Concern::searchConcerns(
            $query,
            $department,
            $status,
            $period,
            'submitted_at',
            'DESC',
            $page,
            10
        );

        $this->render('tickets/history', [
            'title' => 'Ticket History & Remarks',
            'tickets' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['current_page'],
            'totalPages' => $result['total_pages'],
            'departments' => Concern::allDepartments(),
            'q' => $query,
            'filterDepartment' => $department,
            'filterStatus' => $status,
            'filterPeriod' => $period,
        ]);
    }

    /**
     * AJAX: get a concern detail for the modal.
     */
    public function getJson(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }
        $concern = Concern::find($id);
        if (!$concern) {
            $this->json(['error' => 'Concern not found.'], 404);
            return;
        }
        $this->json(['concern' => $concern]);
    }

    /**
     * AJAX: save remarks for a ticket.
     */
    public function saveRemarks(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $remarks = (string) $this->request->input('remarks');
        $result = $this->ticketService->setRemarks($id, $remarks);

        if ($result['success']) {
            $this->audit('ticket_remarks', (string) $id, 'Updated remarks');
            $this->json(['success' => true, 'message' => 'Remarks saved.']);
        } else {
            $this->json(['success' => false, 'message' => $result['message']], 400);
        }
    }

    private function audit(string $action, ?string $reference, string $description): void
    {
        if (!$this->auditService) {
            return;
        }
        try {
            $this->auditService->logAction($action, $reference, $description);
        } catch (\Throwable $e) {
            // best-effort
        }
    }
}
