<?php
/**
 * MailQueueController
 * Admin-only endpoints to manually trigger queue processing and view status.
 * Useful for testing and as a fallback when no CLI worker is scheduled.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MailQueueService;

class MailQueueController extends Controller
{
    private MailQueueService $queue;

    public function __construct()
    {
        parent::__construct();
        $this->queue = new MailQueueService();
    }

    /**
     * Process due emails in the queue.
     * GET /mail-queue/process?limit=10
     */
    public function process(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $limit = (int) $this->request->query('limit', 10);
        $stats = $this->queue->process($limit);

        $this->json([
            'success'   => true,
            'processed' => $stats['processed'],
            'sent'      => $stats['sent'],
            'failed'    => $stats['failed'],
        ]);
    }

    /**
     * Get queue status counts.
     * GET /mail-queue/status
     */
    public function status(): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $this->json([
            'success' => true,
            'queue'   => $this->queue->status(),
        ]);
    }
}
