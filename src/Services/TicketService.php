<?php
/**
 * TicketService
 * Business logic for the IT Concern (Ticketing) module.
 * Handles submit, edit, repair, cancel, and ticket-number generation.
 */

namespace App\Services;

use App\Models\Concern;
use App\Models\TicketSequence;
use App\Models\TroubleNotification;
use App\Core\Database;
use App\Helpers\File;
use App\Services\MailQueueService;

class TicketService
{
    private Database $db;
    private ?File $fileHelper;

    public function __construct(?File $fileHelper = null)
    {
        $this->db = Database::getInstance();
        $this->fileHelper = $fileHelper;
    }

/**
     * Submit a new concern/ticket.
     */
    public function submit(int $userId, string $usernameRef, string $senderName, string $department, string $description, ?array $imageFile = null, string $priority = 'Medium', ?string $ip = null): array
    {
        $ticketNumber = TicketSequence::nextTicketNumber($department);

        $imagePath = null;
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $upload = (new File())->upload($imageFile, 'tickets');
            if (!$upload['success']) {
                return ['success' => false, 'message' => $upload['error']];
            }
            $imagePath = 'tickets/' . $upload['filename'];
        }

        $concernId = Concern::create([
            'user_id'       => $userId,
            'username_ref'  => $usernameRef,
            'ticket_number' => $ticketNumber,
            'sender_name'   => $senderName,
            'department'    => $department,
            'department_id' => $this->resolveDepartmentId($department),
            'description'   => $description,
            'image_path'    => $imagePath,
            'status'        => 'active',
            'priority'      => $priority,
            'submitted_at'  => date('Y-m-d H:i:s'),
            'ip_address'    => $ip,
        ]);

// Internal notification to admins
        $this->notifyAdmins($concernId, $ticketNumber, $department, $senderName);

        // Best-effort email notification to admins
        $this->emailAdmins($concernId, $ticketNumber, $senderName, $department, $description, $priority);

        return ['success' => true, 'message' => "Concern submitted successfully. Ticket #{$ticketNumber}", 'ticket_number' => $ticketNumber];
    }

    /**
     * Edit an existing concern (owner-only).
     */
    public function edit(int $concernId, int $userId, string $senderName, string $description, ?array $imageFile = null): array
    {
        $concern = Concern::find($concernId);
        if (!$concern) {
            return ['success' => false, 'message' => 'Concern not found.'];
        }
        if ((int) $concern['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'You can only edit your own concerns.'];
        }
        if ($concern['status'] !== 'active') {
            return ['success' => false, 'message' => 'Only active concerns can be edited.'];
        }

        $updateData = [
            'sender_name' => $senderName,
            'description' => $description,
        ];

        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $upload = (new File())->upload($imageFile, 'tickets');
            if (!$upload['success']) {
                return ['success' => false, 'message' => $upload['error']];
            }
            // Delete old image if exists
            if ($concern['image_path']) {
                (new File())->delete($concern['image_path']);
            }
            $updateData['image_path'] = 'tickets/' . $upload['filename'];
        }

        Concern::update($concernId, $updateData);

        return ['success' => true, 'message' => 'Concern updated successfully.'];
    }

    /**
     * Accept a pending concern (admin) and start the repair timer.
     *
     * The transition is performed with a single atomic conditional UPDATE
     * (status = 'active' → 'accepted'), so rapid double-clicks or duplicate
     * submissions can never create a second timer: the first request flips
     * the status, every later request matches 0 rows and is rejected.
     * The timer start (accepted_at) comes from the MySQL server, never from
     * the client. Elapsed repair time is measured as now - accepted_at and
     * stops when the ticket is marked as repaired (repaired_date).
     */
    public function accept(int $concernId, ?int $actorId, string $actorName): array
    {
        $concern = Concern::find($concernId);
        if (!$concern) {
            return ['success' => false, 'message' => 'Concern not found.'];
        }

        $stmt = $this->db->query(
            "UPDATE concerns
             SET status = 'accepted',
                 accepted_at = NOW(),
                 accepted_by = ?,
                 accepted_by_id = ?,
                 repaired_by = ?,
                 repaired_by_id = ?
             WHERE id = ? AND status = 'active'",
            [$actorName, $actorId, $actorName, $actorId, $concernId]
        );

        if ($stmt->rowCount() === 0) {
            // Rejected safely — explain why without changing anything
            $fresh = Concern::find($concernId);
            $message = match ($fresh['status'] ?? '') {
                'accepted' => 'This ticket has already been accepted.',
                'repaired' => 'This ticket is already completed.',
                'canceled' => 'This ticket has been canceled and can no longer be accepted.',
                default    => 'Only pending tickets can be accepted.',
            };
            return ['success' => false, 'message' => $message];
        }

        $updated = Concern::find($concernId);

        $this->notifyOwner(
            $updated,
            'accepted',
            "Your concern {$updated['ticket_number']} has been accepted by IT support ({$actorName}). "
            . 'Repair is now in progress.'
        );

        return ['success' => true, 'message' => 'Ticket accepted. Repair timer started.'];
    }

    /**
     * Mark a concern as repaired.
     */
    public function markRepaired(int $concernId, ?int $actorId, string $actorName): array
    {
        $concern = Concern::find($concernId);
        if (!$concern) {
            return ['success' => false, 'message' => 'Concern not found.'];
        }
        if (!in_array($concern['status'], ['active', 'accepted'], true)) {
            return ['success' => false, 'message' => 'Only pending or accepted concerns can be marked as repaired.'];
        }

        $updateData = [
            'status'       => 'repaired',
            'repaired_date' => date('Y-m-d H:i:s'),
        ];

        // Immutable assignment rule: for tickets that were accepted, the
        // accepting admin stays "Repaired By" — whoever clicks the button
        // later must NOT overwrite it. Only for never-accepted tickets does
        // the completing actor become the repairer.
        if ($concern['status'] === 'active') {
            $updateData['repaired_by'] = $actorName;
            if ($actorId !== null) {
                $updateData['repaired_by_id'] = $actorId;
            }
        }

        Concern::update($concernId, $updateData);

        $this->notifyOwner($concern, 'repaired', "Your concern {$concern['ticket_number']} has been marked as repaired.");

        return ['success' => true, 'message' => 'Concern marked as repaired.'];
    }

    /**
     * Cancel a concern with a reason.
     */
    public function cancel(int $concernId, ?int $actorId, string $actorName, string $reason): array
    {
        if (trim($reason) === '') {
            return ['success' => false, 'message' => 'A cancellation reason is required.'];
        }

        $concern = Concern::find($concernId);
        if (!$concern) {
            return ['success' => false, 'message' => 'Concern not found.'];
        }
        if (!in_array($concern['status'], ['active', 'accepted'], true)) {
            return ['success' => false, 'message' => 'Only pending or accepted concerns can be canceled.'];
        }

        Concern::update($concernId, [
            'status'         => 'canceled',
            'canceled_reason' => $reason,
            'canceled_date'  => date('Y-m-d H:i:s'),
            'canceled_by'    => $actorName,
        ]);

        $this->notifyOwner($concern, 'canceled', "Your concern {$concern['ticket_number']} has been canceled. Reason: {$reason}");

        return ['success' => true, 'message' => 'Concern canceled.'];
    }

    /**
     * Save/set remarks for a concern (admin).
     */
    public function setRemarks(int $concernId, string $remarks): array
    {
        $concern = Concern::find($concernId);
        if (!$concern) {
            return ['success' => false, 'message' => 'Concern not found.'];
        }

        Concern::update($concernId, ['remarks' => $remarks]);

        return ['success' => true, 'message' => 'Remarks saved.'];
    }

    /**
     * Resolve department_id from the departments table (best-effort).
     */
    private function resolveDepartmentId(string $department): ?int
    {
        $row = $this->db->fetch("SELECT id FROM departments WHERE name = ? LIMIT 1", [$department]);
        if ($row) {
            return (int) $row['id'];
        }
        $row = $this->db->fetch("SELECT id FROM departments WHERE LOWER(code) = LOWER(?) LIMIT 1", [$department]);
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Create an internal notification for all admin users.
     */
    private function notifyAdmins(int $concernId, string $ticketNumber, string $department, string $senderName): void
    {
        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
        foreach ($admins as $admin) {
            TroubleNotification::notify(
                (int) $admin['id'],
                null,
                $concernId,
                $ticketNumber,
                $department,
                'submission',
                "New concern {$ticketNumber} submitted by {$senderName} ({$department})."
            );
        }
    }

/**
     * Send an email notification to all active admins when a new concern is submitted.
     * Best-effort: failures are logged and never block the ticket submission.
     */
    private function emailAdmins(int $concernId, string $ticketNumber, string $senderName, string $department, string $description, string $priority): void
    {
        try {
            // Gather admin email addresses from the users table
            $rows = $this->db->fetchAll(
                "SELECT email FROM users WHERE role = 'admin' AND is_active = 1 AND email IS NOT NULL AND email != ''"
            );
            $emails = array_values(array_unique(array_filter(array_column($rows, 'email'), 'is_string')));

            // Also include any admin notification email configured in config/app.php
            $appConfig = require CONFIG_PATH . '/app.php';
            $configured = $appConfig['mail']['admin_notification_email'] ?? [];
            foreach ($configured as $email) {
                if (is_string($email) && trim($email) !== '') {
                    $emails[] = trim($email);
                }
            }
            $emails = array_values(array_unique(array_filter($emails)));

            if (empty($emails)) {
                return;
            }

            $subject = "[IT Assets] New Concern Submitted: {$ticketNumber}";
            $body = "A new IT concern has been submitted.\n\n"
                . "Ticket #: " . $ticketNumber . "\n"
                . "Sender: " . $senderName . "\n"
                . "Department: " . $department . "\n"
                . "Priority: " . $priority . "\n"
                . "Submitted at: " . date('Y-m-d H:i:s') . "\n\n"
                . "Description:\n" . $description . "\n\n"
                . "Please review the concern in the Manage Active section.\n";

// Enqueue emails for async delivery via the MailQueueService.
            // This keeps ticket submission non-blocking.
            $queue = new MailQueueService();
            foreach ($emails as $email) {
                $queue->enqueue($email, $subject, $body);
            }
        } catch (\Throwable $e) {
            // Swallow email errors — the ticket submission already succeeded
        }
    }

    /**
     * Create an internal notification for the concern owner.
     */
    private function notifyOwner(array $concern, string $type, string $message): void
    {
        if ($concern['user_id']) {
            TroubleNotification::notify(
                (int) $concern['user_id'],
                $concern['username_ref'],
                (int) $concern['id'],
                $concern['ticket_number'],
                $concern['department'],
                $type,
                $message
            );
        }
    }
}
