<?php
/**
 * MailQueueService
 * Async email queue backed by the `email_queue` table.
 *
 * Ticket submission and other events enqueue emails instead of sending
 * synchronously, so the HTTP request returns immediately. A CLI worker
 * (mail_queue_worker.php) or the admin-triggered controller processes the
 * queue and sends via MailService.
 */

namespace App\Services;

use App\Core\Database;

class MailQueueService
{
    private Database $db;
    private AuditService $audit;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->audit = new AuditService();
    }

    /**
     * Enqueue an email for async delivery.
     *
     * @param string $toEmail
     * @param string $subject
     * @param string $body
     * @param int $maxAttempts
     * @return int The new email_queue row id (0 on failure).
     */
    public function enqueue(string $toEmail, string $subject, string $body, int $maxAttempts = 5): int
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            return 0;
        }

        try {
            $id = $this->db->insert('email_queue', [
                'to_email'     => $toEmail,
                'subject'      => $subject,
                'body'         => $body,
                'status'       => 'queued',
                'attempts'     => 0,
                'max_attempts' => max(1, $maxAttempts),
                'available_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return 0;
        }

        // Audit: only the recipient + subject — never the message body.
        $this->audit->log('mail_queued', 'mail', $id, $toEmail, null, null, null, 'Email queued: ' . $subject);

        return $id;
    }

    /**
     * Process due queued emails.
     *
     * @param int $limit Max number of emails to process in this run.
     * @return array{processed:int,sent:int,failed:int}
     */
    public function process(int $limit = 10): array
    {
        $stats = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        $rows = $this->db->fetchAll(
            "SELECT * FROM email_queue
             WHERE status = 'queued' AND available_at <= ?
             ORDER BY id ASC
             LIMIT " . (int) $limit,
            [date('Y-m-d H:i:s')]
        );

        foreach ($rows as $row) {
            $stats['processed']++;
            $id = (int) $row['id'];

            // Mark as sending (prevents double-processing by concurrent workers)
            $this->db->update(
                'email_queue',
                ['status' => 'sending'],
                'id = ?',
                [$id]
            );

            try {
                $mail = new MailService();
                $sent = $mail->send($row['to_email'], $row['subject'], $row['body']);
            } catch (\Throwable $e) {
                $sent = false;
            }

            if ($sent) {
                $this->db->update(
                    'email_queue',
                    ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'last_error' => null],
                    'id = ?',
                    [$id]
                );
                $stats['sent']++;
                $this->audit->log('mail_sent', 'mail', $id, (string) $row['to_email'], null, null, null, 'Email sent: ' . (string) $row['subject']);
            } else {
                $attempts = (int) $row['attempts'] + 1;
                $maxAttempts = (int) $row['max_attempts'];

                if ($attempts >= $maxAttempts) {
                    $this->db->update(
                        'email_queue',
                        ['status' => 'failed', 'attempts' => $attempts, 'last_error' => 'Max attempts reached'],
                        'id = ?',
                        [$id]
                    );
                } else {
                    // Retry later (backoff: 5 min * attempts)
                    $this->db->update(
                        'email_queue',
                        [
                            'status'       => 'queued',
                            'attempts'     => $attempts,
                            'last_error'   => 'Send failed, will retry',
                            'available_at' => date('Y-m-d H:i:s', time() + (60 * 5 * $attempts)),
                        ],
                        'id = ?',
                        [$id]
                    );
                }
                $stats['failed']++;
                $status = $attempts >= $maxAttempts ? 'giving up after ' . $attempts . ' attempts' : 'will retry';
                $this->audit->log('mail_failed', 'mail', $id, (string) $row['to_email'], null, null, null, 'Email send failed (' . $status . '): ' . (string) $row['subject']);
            }
        }

        return $stats;
    }

    /**
     * Re-queue failed emails that are under their max attempts.
     *
     * @param int $limit
     * @return int Number of emails re-queued.
     */
    public function retryFailed(int $limit = 10): int
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM email_queue
             WHERE status = 'failed' AND attempts < max_attempts
             ORDER BY id ASC
             LIMIT " . (int) $limit
        );

        $count = 0;
        foreach ($rows as $row) {
            $this->db->update(
                'email_queue',
                ['status' => 'queued', 'available_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [(int) $row['id']]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Get queue status counts.
     *
     * @return array<string,int>
     */
    public function status(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM email_queue GROUP BY status"
        );
        $result = ['queued' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }
        return $result;
    }
}
