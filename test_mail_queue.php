<?php
/**
 * Mail Queue smoke test.
 * Verifies enqueue + process + status of the async email queue.
 *
 * Run: php test_mail_queue.php
 *
 * NOTE: This will actually attempt to send real emails via SMTP when
 * processing. To avoid real sends, use the --enqueue-only flag.
 */

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

$enqueueOnly = in_array('--enqueue-only', $argv ?? [], true);

$queue = new \App\Services\MailQueueService();

echo "=== Mail Queue Smoke Test ===\n";

// 1. Enqueue
$id1 = $queue->enqueue('test@example.com', 'Test Subject 1', 'Hello from queue test 1.');
$id2 = $queue->enqueue('test2@example.com', 'Test Subject 2', 'Hello from queue test 2.');
echo "Enqueued id1={$id1}, id2={$id2}\n";
if ($id1 <= 0 || $id2 <= 0) {
    echo "FAIL: enqueue returned invalid ids.\n";
    exit(1);
}

// 2. Status
$status = $queue->status();
echo "Status after enqueue: " . json_encode($status) . "\n";
if (($status['queued'] ?? 0) < 2) {
    echo "FAIL: expected at least 2 queued emails.\n";
    exit(1);
}

if ($enqueueOnly) {
    echo "\nOK: enqueue verified. Skipping process (--enqueue-only).\n";
    exit(0);
}

// 3. Process
$stats = $queue->process(5);
echo "Process result: " . json_encode($stats) . "\n";

// 4. Status after process
$after = $queue->status();
echo "Status after process: " . json_encode($after) . "\n";

echo "\nQueue smoke test completed.\n";
