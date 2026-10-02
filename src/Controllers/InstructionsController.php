<?php
/**
 * InstructionsController
 * Admin management of department instructions / key notes.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Instruction;
use App\Services\AuditService;

class InstructionsController extends Controller
{
    private ?AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        try {
            $this->auditService = new AuditService();
        } catch (\Throwable $e) {
            $this->auditService = null;
        }
    }

    /**
     * List all instructions.
     */
    public function index(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $this->render('tickets/instructions', [
            'title' => 'Instructions',
            'instructions' => Instruction::allOrdered(),
        ]);
    }

    /**
     * Store a new instruction.
     */
    public function store(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $tittle = trim((string) $this->request->input('tittle'));
        $text = trim((string) $this->request->input('instruction_text'));

        if ($text === '') {
            $this->redirectWith('/tickets/instructions', 'Instruction text is required.', 'danger');
            return;
        }

        Instruction::create([
            'tittle' => $tittle !== '' ? $tittle : null,
            'instruction_text' => $text,
        ]);

        $this->audit('instruction_created', null, 'Created instruction');
        $this->redirectWith('/tickets/instructions', 'Instruction added.', 'success');
    }

    /**
     * Update an instruction.
     */
    public function update(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $instruction = Instruction::find($id);
        if (!$instruction) {
            $this->redirectWith('/tickets/instructions', 'Instruction not found.', 'danger');
            return;
        }

        $tittle = trim((string) $this->request->input('tittle'));
        $text = trim((string) $this->request->input('instruction_text'));

        if ($text === '') {
            $this->redirectWith('/tickets/instructions', 'Instruction text is required.', 'danger');
            return;
        }

        Instruction::update($id, [
            'tittle' => $tittle !== '' ? $tittle : null,
            'instruction_text' => $text,
        ]);

        $this->audit('instruction_updated', (string) $id, 'Updated instruction');
        $this->redirectWith('/tickets/instructions', 'Instruction updated.', 'success');
    }

    /**
     * Delete an instruction.
     */
    public function destroy(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $instruction = Instruction::find($id);
        if (!$instruction) {
            $this->redirectWith('/tickets/instructions', 'Instruction not found.', 'danger');
            return;
        }

        Instruction::delete($id);
        $this->audit('instruction_deleted', (string) $id, 'Deleted instruction');
        $this->redirectWith('/tickets/instructions', 'Instruction deleted.', 'success');
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
