<?php
/**
 * TicketController
 * Handles IT Concern (Ticket) user + admin actions.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Concern;
use App\Models\Department;
use App\Models\Instruction;
use App\Models\TroubleNotification;
use App\Services\TicketService;
use App\Services\AuditService;
use App\Helpers\Format;

class TicketController extends Controller
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
     * User dashboard for tickets: submit form + my active concerns.
     */
    public function index(): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

        $departments = Department::getOptions();
        $departmentsList = $this->departmentNames();

        // Active concerns for this user (also match username_ref for legacy tickets)
        $mine = Concern::activeForUser(
            (int) $this->currentUser['id'],
            (string) ($this->currentUser['username'] ?? '')
        );

        $this->render('tickets/index', [
            'title' => 'IT Concerns',
            'activeTickets' => $mine,
            'departments' => $departments,
            'departmentsList' => $departmentsList,
            'unreadCount' => TroubleNotification::countUnread((int) $this->currentUser['id']),
        ]);
    }

    /**
     * User's own ticket history: all tickets they submitted, with status
     * and what happened to each (repaired/canceled details, remarks).
     */
    public function myHistory(): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

        $page = (int) $this->request->query('page', 1);
        $status = (string) $this->request->query('status', '');

        $result = Concern::historyForUser(
            (int) $this->currentUser['id'],
            $status,
            $page,
            10,
            (string) ($this->currentUser['username'] ?? '')
        );

        $this->render('tickets/my_history', [
            'title' => 'My Ticket History',
            'tickets' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['current_page'],
            'totalPages' => $result['total_pages'],
            'filterStatus' => $status,
        ]);
    }

    /**
     * Submit a new concern.
     */
    public function store(): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

$senderName = trim((string) $this->request->input('sender_name'));
        $description = trim((string) $this->request->input('description'));
        $priority = trim((string) $this->request->input('priority', 'Medium'));

        // Department is always auto-detected from the logged-in user's account.
        // The user never manually inputs/selects a department.
        $department = (string) ($this->currentUser['department'] ?? '');
        if ($department === '') {
            $department = 'General';
        }

        $errors = [];
        if ($senderName === '') {
            $errors[] = 'Sender name is required.';
        }
        if ($description === '') {
            $errors[] = 'Description is required.';
        }
        if (!in_array($priority, ['Critical', 'High', 'Medium', 'Low'], true)) {
            $priority = 'Medium';
        }
        if (!empty($errors)) {
            $this->redirectWith('/tickets', implode('<br>', $errors), 'danger');
            return;
        }

        $imageFile = $this->request->hasFile('image') ? $this->request->file('image') : null;

$result = $this->ticketService->submit(
            (int) $this->currentUser['id'],
            (string) $this->currentUser['username'],
            $senderName,
            $department,
            $description,
            $imageFile,
            $priority,
            $this->request->ip()
        );

        if ($result['success']) {
            $this->audit('ticket_created', $result['ticket_number'] ?? null, 'Created ticket');
            $this->redirectWith('/tickets', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets', $result['message'], 'danger');
        }
    }

    /**
     * Show edit form for a user's own active concern.
     */
    public function edit(int $id): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

        $concern = Concern::find($id);
        if (!$concern) {
            $this->redirectWith('/tickets', 'Concern not found.', 'danger');
            return;
        }
        if ($concern['status'] !== 'active') {
            $this->redirectWith('/tickets', 'Only active concerns can be edited.', 'warning');
            return;
        }
        // Owner check for operators (admins may edit any)
        if ($this->currentUser['role'] !== 'admin' && (int) $concern['user_id'] !== (int) $this->currentUser['id']) {
            $this->redirectWith('/tickets', 'You can only edit your own concerns.', 'danger');
            return;
        }

        $this->render('tickets/edit', [
            'title' => 'Edit Concern: ' . ($concern['ticket_number'] ?? ''),
            'concern' => $concern,
        ]);
    }

    /**
     * Update an existing concern.
     */
    public function update(int $id): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

        $senderName = trim((string) $this->request->input('sender_name'));
        $description = trim((string) $this->request->input('description'));

        if ($senderName === '') {
            $this->redirectWith('/tickets/' . $id . '/edit', 'Sender name is required.', 'danger');
            return;
        }
        if ($description === '') {
            $this->redirectWith('/tickets/' . $id . '/edit', 'Description is required.', 'danger');
            return;
        }

$imageFile = $this->request->hasFile('image') ? $this->request->file('image') : null;

        $existing = Concern::find($id);
        $result = $this->ticketService->edit($id, (int) $this->currentUser['id'], $senderName, $description, $imageFile);

        if ($result['success']) {
            $this->audit('ticket_updated', (string) ($existing['ticket_number'] ?? $id), 'Updated ticket');
            $this->redirectWith('/tickets', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets/' . $id . '/edit', $result['message'], 'danger');
        }
    }

    /**
     * Admin: list all active tickets with department filter.
     */
    public function manage(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $page = (int) $this->request->query('page', 1);
        $department = (string) $this->request->query('department', '');

        $result = Concern::allActive($page, PER_PAGE, $department);

        $stats = [
            'active' => Concern::countByStatus('active'),
            'accepted' => Concern::countByStatus('accepted'),
            'repaired' => Concern::countByStatus('repaired'),
            'canceled' => Concern::countByStatus('canceled'),
            'repairedToday' => Concern::countRepairedToday(),
        ];

        $this->render('tickets/manage', [
            'title' => 'Manage Active Concerns',
            'tickets' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['current_page'],
            'totalPages' => $result['total_pages'],
            'departments' => Concern::activeDepartments(),
            'filterDepartment' => $department,
            'stats' => $stats,
            'serverNow' => time(), // epoch reference for the JS countdown clock offset
        ]);
    }

    /**
     * Accept a pending concern (admin) — starts the repair timer.
     */
    public function accept(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $actorName = $this->currentUser['full_name'] ?? $this->currentUser['username'];

        $result = $this->ticketService->accept($id, (int) $this->currentUser['id'], (string) $actorName);

        if ($result['success']) {
            $this->audit('ticket_accepted', (string) $id, 'Accepted ticket, repair timer started');
            $this->redirectWith('/tickets/manage', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets/manage', $result['message'], 'danger');
        }
    }

    /**
     * Cancel a concern with a reason (admin).
     */
    public function cancel(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $reason = trim((string) $this->request->input('reason'));
        $actorName = $this->currentUser['full_name'] ?? $this->currentUser['username'];

        $result = $this->ticketService->cancel($id, (int) $this->currentUser['id'], (string) $actorName, $reason);

        if ($result['success']) {
            $this->audit('ticket_canceled', (string) $id, 'Canceled ticket');
            $this->redirectWith('/tickets/manage', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets/manage', $result['message'], 'danger');
        }
    }

    /**
     * Mark an accepted concern as repaired (admin).
     * The "Repaired By" assignment made during acceptance is preserved —
     * this only completes the repair and stops the timer.
     */
    public function complete(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $actorName = $this->currentUser['full_name'] ?? $this->currentUser['username'];
        $result = $this->ticketService->markRepaired($id, (int) $this->currentUser['id'], (string) $actorName);

        if ($result['success']) {
            $this->audit('ticket_completed', (string) $id, 'Marked ticket repaired');
            $this->redirectWith('/tickets/manage', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets/manage', $result['message'], 'danger');
        }
    }

    /**
     * User marks their own concern as repaired.
     */
    public function repairMy(int $id): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }

        $concern = Concern::find($id);
        if (!$concern) {
            $this->redirectWith('/tickets', 'Concern not found.', 'danger');
            return;
        }
        if ($this->currentUser['role'] !== 'admin' && (int) $concern['user_id'] !== (int) $this->currentUser['id']) {
            $this->redirectWith('/tickets', 'You can only update your own concerns.', 'danger');
            return;
        }

        $actorName = $this->currentUser['full_name'] ?? $this->currentUser['username'];
        $result = $this->ticketService->markRepaired($id, (int) $this->currentUser['id'], (string) $actorName);

        if ($result['success']) {
            $this->audit('ticket_repaired', (string) $id, 'Marked ticket repaired');
            $this->redirectWith('/tickets', $result['message'], 'success');
        } else {
            $this->redirectWith('/tickets', $result['message'], 'danger');
        }
    }

    /**
     * JSON API for ticket stats (dashboard).
     */
    public function stats(): void
    {
        if (!$this->requireRole('admin', 'user')) {
            return;
        }
        $this->json([
            'active' => Concern::countByStatus('active'),
            'accepted' => Concern::countByStatus('accepted'),
            'repaired' => Concern::countByStatus('repaired'),
            'canceled' => Concern::countByStatus('canceled'),
            'repairedToday' => Concern::countRepairedToday(),
            'unread' => TroubleNotification::countUnread((int) $this->currentUser['id']),
        ]);
    }

/**
     * Departments available for ticket submission.
     */
    private function departmentNames(): array
    {
        $db = \App\Core\Database::getInstance();
        $rows = $db->fetchAll("SELECT DISTINCT name FROM departments WHERE name IS NOT NULL AND name != '' ORDER BY name");
        return array_column($rows, 'name');
    }

    /**
     * Record a ticket action in the audit log (best-effort).
     */
    private function audit(string $action, ?string $reference, string $description): void
    {
        if (!$this->auditService) {
            return;
        }
        try {
            $this->auditService->logAction($action, $reference, $description);
        } catch (\Throwable $e) {
            // Swallow audit errors — the primary action already succeeded
        }
    }
}
