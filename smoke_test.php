<?php
/**
 * Smoke test: verifies all new ticketing classes + route actions load correctly.
 * Run: php smoke_test.php
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

$classes = [
    \App\Models\Concern::class,
    \App\Models\Instruction::class,
    \App\Models\TicketSequence::class,
    \App\Models\TroubleNotification::class,
    \App\Services\TicketService::class,
    \App\Services\MailService::class,
    \App\Controllers\TicketController::class,
    \App\Controllers\AnalyticsController::class,
    \App\Controllers\TicketHistoryController::class,
    \App\Controllers\InstructionsController::class,
    \App\Controllers\TicketExportController::class,
    \App\Controllers\IncrementalBackupController::class,
];

$ok = true;
foreach ($classes as $class) {
    if (!class_exists($class)) {
        echo "FAIL: {$class} could not be loaded.\n";
        $ok = false;
    } else {
        echo "OK: {$class}\n";
    }
}

// Verify route actions exist
$routes = [
    ['TicketController', ['index', 'store', 'edit', 'update', 'repairMy', 'stats', 'manage', 'accept', 'complete', 'cancel']],
    ['TicketHistoryController', ['index', 'getJson', 'saveRemarks']],
    ['AnalyticsController', ['index', 'monthlySeries', 'export']],
    ['TicketExportController', ['filtered']],
    ['InstructionsController', ['index', 'store', 'update', 'destroy']],
    ['IncrementalBackupController', ['index', 'run', 'download']],
];

foreach ($routes as $route) {
    list($ctrl, $actions) = $route;
    $class = 'App\\Controllers\\' . $ctrl;
    foreach ($actions as $action) {
        if (!method_exists($class, $action)) {
            echo "FAIL: {$ctrl}@{$action} missing.\n";
            $ok = false;
        } else {
            echo "OK: {$ctrl}@{$action}\n";
        }
    }
}

if ($ok) {
    echo "\nALL CLASSES AND ROUTE ACTIONS OK\n";
} else {
    echo "\nVERIFICATION FAILED\n";
    exit(1);
}
