<?php
/**
 * Mail Queue Worker (CLI)
 * Processes pending emails in the email_queue table.
 *
 * Usage:
 *   php mail_queue_worker.php              # process up to 10 emails
 *   php mail_queue_worker.php --limit=50   # process up to 50 emails
 *   php mail_queue_worker.php --loop        # run continuously, processing every 60s
 *
 * Recommended setup: schedule this via cron / Task Scheduler every few minutes.
 */

// CLI-only worker. Must never be reachable over the web (it would allow
// anonymous visitors to trigger outbound mail sending).
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require __DIR__ . '/config/constants.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Parse CLI args
$options = getopt('', ['limit:', 'loop']);
$limit = isset($options['limit']) ? (int) $options['limit'] : 10;
$loop  = isset($options['loop']);

$queue = new \App\Services\MailQueueService();

if ($loop) {
    echo "Mail queue worker started in loop mode (limit={$limit}, interval=60s). Ctrl+C to stop.\n";
    while (true) {
        runOnce($queue, $limit);
        sleep(60);
    }
}

runOnce($queue, $limit);

/**
 * Process the queue once.
 *
 * `mail_sent` / `mail_failed` audit entries are written by
 * MailQueueService::process() itself, so this CLI worker and the
 * admin-only /mail-queue/process endpoint both land in the unified audit
 * trail. With no session user the entries carry user_id NULL (the FK and
 * AuditService::getUserId() both allow that).
 */
function runOnce(\App\Services\MailQueueService $queue, int $limit): void
{
    $stats = $queue->process($limit);
    $counts = $queue->status();
    echo sprintf(
        "[%s] processed=%d sent=%d failed=%d | queue status: queued=%d sending=%d sent=%d failed=%d\n",
        date('Y-m-d H:i:s'),
        $stats['processed'],
        $stats['sent'],
        $stats['failed'],
        $counts['queued'],
        $counts['sending'],
        $counts['sent'],
        $counts['failed']
    );
}
