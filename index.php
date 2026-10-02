<?php
/**
 * IT Asset Management System - Front Controller
 * 
 * Entry point for all requests. Routes to appropriate controllers.
 */

// ─── Load Configuration (defines APP_DEBUG and other constants) ───
require_once __DIR__ . '/config/constants.php';

// ─── Error Reporting ───
error_reporting(E_ALL);
// Errors are only displayed when APP_DEBUG is explicitly enabled; otherwise
// they are logged to the PHP error log so no stack traces reach end users.
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// ─── Timezone ───
date_default_timezone_set('Asia/Manila');

// ─── Autoloader ───
spl_autoload_register(function (string $class) {
    // Convert namespace to file path
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

// ─── Start Session ───
use App\Core\Session;
use App\Core\Router;
use App\Core\Response;

Session::getInstance();

// ─── Initialize Router ───
$router = new Router();
$response = new Response();

// ─── Define Routes ───

// Public routes (no auth required)
$router->get('/', 'AuthController@loginForm');
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login', ['csrf']);
$router->get('/logout', 'AuthController@logout');

// Authenticated routes
// 'auth' gates every route on login; 'csrf' validates the CSRF token on
// every state-changing request (POST/PUT/DELETE). All views supply the
// token via the hidden _csrf_token field or the X-CSRF-Token header.
$router->group('', ['auth', 'csrf'], function (Router $router) {
    // System startup/initialization sequence (shown after successful login)
    $router->get('/startup', 'StartupController@index');

    // Dashboard
    $router->get('/dashboard', 'DashboardController@index');

    // Assets (static routes BEFORE parameterized routes)
    $router->get('/assets', 'AssetController@index');
    $router->get('/assets/create', 'AssetController@create');
    // Static route: must stay registered BEFORE the /assets/{id} pattern route.
    $router->get('/assets/next-tag', 'AssetController@nextTag');
    $router->get('/assets/labels', 'AssetController@labels');
    $router->post('/assets', 'AssetController@store');
    $router->get('/assets/export/csv', 'AssetController@exportCsv');
$router->get('/assets/import', 'AssetController@showImport');
    $router->get('/assets/import/download-sample', 'AssetController@sampleCsv');
    $router->post('/assets/import', 'AssetController@import');
    $router->get('/assets/retired', 'DisposalController@retired');
    $router->post('/assets/{assetId}/return-to-service', 'DisposalController@returnToService');
    $router->get('/assets/{id}', 'AssetController@show');
    $router->get('/assets/{id}/edit', 'AssetController@edit');
    $router->post('/assets/{id}/update', 'AssetController@update');
    $router->post('/assets/{id}/delete', 'AssetController@destroy');
    $router->post('/assets/{id}/retire', 'AssetController@retire');

    // Network Topology
    $router->get('/topology', 'TopologyController@index');
    $router->get('/topology/data', 'TopologyController@data');
    $router->post('/topology/save-positions', 'TopologyController@savePositions');
    $router->post('/topology/save-node', 'TopologyController@saveNode');
    $router->post('/topology/add-link', 'TopologyController@createLink');
    $router->post('/topology/add-junction', 'TopologyController@addJunction');
    $router->post('/topology/remove-link', 'TopologyController@deleteLink');
    $router->post('/topology/remove-link/{id}', 'TopologyController@deleteLinkById');
    $router->post('/topology/asset-links/{assetId}', 'TopologyController@deleteLinks');
$router->get('/topology/info/{id}', 'TopologyController@info');
    $router->get('/topology/labels', 'TopologyController@getLabels');
    $router->post('/topology/labels/save', 'TopologyController@saveLabel');
    $router->post('/topology/labels/delete/{id}', 'TopologyController@deleteLabel');
    $router->post('/topology/save-label-positions', 'TopologyController@saveLabelPositions');

    // Departments
    $router->get('/departments', 'DepartmentController@index');
    // Parameterized route registered BEFORE the static /departments/list so a
    // segment like "list" can never be captured by a {id} placeholder.
    $router->get('/departments/{id}/assets', 'DepartmentController@assets');
    $router->post('/departments', 'DepartmentController@store');
    $router->post('/departments/{id}/update', 'DepartmentController@update');
    $router->post('/departments/{id}/delete', 'DepartmentController@destroy');
    $router->get('/departments/list', 'DepartmentController@list');

    // History
    $router->get('/history', 'HistoryController@index');
    $router->get('/history/asset/{id}', 'HistoryController@asset');

// Profile
    $router->get('/profile', 'AuthController@profile');
    $router->post('/profile', 'AuthController@updateProfile');

// Messages (Messenger)
    $router->get('/messages', 'MessageController@index');
    $router->get('/messages/users', 'MessageController@users');
    $router->get('/messages/conversation/{id}', 'MessageController@conversation');
    $router->post('/messages/send', 'MessageController@send');
    $router->get('/messages/poll/{id}', 'MessageController@poll');
    $router->get('/messages/unread', 'MessageController@unread');
    $router->get('/messages/conversations', 'MessageController@conversations');

    // ─── Software License Management Module ───
    $router->get('/licenses', 'LicenseController@index');
    $router->get('/licenses/create', 'LicenseController@create');
    $router->post('/licenses', 'LicenseController@store');
    $router->get('/licenses/{id}', 'LicenseController@show');
    $router->get('/licenses/{id}/edit', 'LicenseController@edit');
    $router->post('/licenses/{id}/update', 'LicenseController@update');
    $router->post('/licenses/{id}/delete', 'LicenseController@destroy');
    $router->post('/licenses/{id}/assign/user', 'LicenseController@assignToUser');
    $router->post('/licenses/{id}/assign/asset', 'LicenseController@assignToAsset');
    $router->post('/licenses/{id}/assignments/{assignmentId}/remove', 'LicenseController@removeAssignment');

    // ─── Asset Disposal Management Module ───
    $router->get('/disposals', 'DisposalController@index');
    $router->get('/disposals/create/{assetId}', 'DisposalController@create');
    $router->post('/disposals/store/{assetId}', 'DisposalController@store');
    $router->get('/disposals/{id}', 'DisposalController@show');
    $router->get('/disposals/{id}/certificate', 'DisposalController@certificate');
    $router->post('/disposals/{id}/submit', 'DisposalController@submit');
    $router->post('/disposals/{id}/approve', 'DisposalController@approve');
    $router->post('/disposals/{id}/reject', 'DisposalController@reject');
    $router->post('/disposals/{id}/schedule', 'DisposalController@schedule');
    $router->post('/disposals/{id}/record-data-destruction', 'DisposalController@recordDataDestruction');
    $router->post('/disposals/{id}/complete', 'DisposalController@complete');
    $router->post('/disposals/{id}/cancel', 'DisposalController@cancel');
    $router->post('/disposals/{id}/upload-attachment', 'DisposalController@uploadAttachment');

    // ─── Ticketing (IT Concerns) Module ───
    // User-facing ticket routes
    $router->get('/tickets', 'TicketController@index');
    $router->post('/tickets', 'TicketController@store', ['csrf']);
    $router->get('/tickets/my-history', 'TicketController@myHistory');
    $router->get('/tickets/{id}/edit', 'TicketController@edit');
    $router->post('/tickets/{id}/update', 'TicketController@update', ['csrf']);
    $router->post('/tickets/{id}/repair-my', 'TicketController@repairMy', ['csrf']);
$router->get('/tickets/stats', 'TicketController@stats');

    // Admin ticket management
    $router->get('/tickets/manage', 'TicketController@manage');
    $router->post('/tickets/{id}/accept', 'TicketController@accept', ['csrf']);
    $router->post('/tickets/{id}/complete', 'TicketController@complete', ['csrf']);
    $router->post('/tickets/{id}/cancel', 'TicketController@cancel', ['csrf']);

    // Admin history + remarks
    $router->get('/tickets/history', 'TicketHistoryController@index');
    $router->get('/tickets/history/get/{id}', 'TicketHistoryController@getJson');
    $router->post('/tickets/history/remarks/{id}', 'TicketHistoryController@saveRemarks', ['csrf']);

    // Admin analytics + exports
    $router->get('/tickets/analytics', 'AnalyticsController@index');
    $router->get('/tickets/analytics/series', 'AnalyticsController@monthlySeries');
    $router->get('/tickets/analytics/export', 'AnalyticsController@export');
    $router->get('/tickets/export', 'TicketExportController@filtered');

    // Admin instructions
    $router->get('/tickets/instructions', 'InstructionsController@index');
    $router->post('/tickets/instructions', 'InstructionsController@store', ['csrf']);
    $router->post('/tickets/instructions/{id}/update', 'InstructionsController@update', ['csrf']);
    $router->post('/tickets/instructions/{id}/delete', 'InstructionsController@destroy', ['csrf']);

// Admin incremental backup
    $router->get('/tickets/backup', 'IncrementalBackupController@index');
    $router->get('/tickets/backup/run', 'IncrementalBackupController@run');
    $router->post('/tickets/backup/run', 'IncrementalBackupController@run');
    $router->get('/tickets/backup/download', 'IncrementalBackupController@download');

    // Mail queue (admin) — manual processing + status
    $router->get('/mail-queue/process', 'MailQueueController@process');
    $router->get('/mail-queue/status', 'MailQueueController@status');

    // API
    $router->get('/api/assets', 'ApiController@searchAssets');
    $router->get('/api/assets/{id}', 'ApiController@getAsset');
    $router->get('/api/users', 'ApiController@searchUsers');
    $router->get('/api/departments', 'ApiController@getDepartments');
    $router->get('/api/stats', 'ApiController@dashboardStats');
    $router->post('/api/topology/positions', 'ApiController@savePositions');

    // Admin-only routes
    $router->group('/users', ['admin'], function (Router $router) {
        $router->get('', 'UserController@index');
        $router->get('/create', 'UserController@create');
        $router->post('', 'UserController@store');
        $router->get('/{id}/edit', 'UserController@edit');
        $router->post('/{id}/update', 'UserController@update');
        $router->post('/{id}/delete', 'UserController@destroy');
    });

    $router->group('/settings', ['admin'], function (Router $router) {
        $router->get('', 'SettingsController@index');
        $router->post('/update', 'SettingsController@update');
    });
});

// ─── Dispatch Request ───
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    $router->dispatch($method, $uri);
} catch (\Throwable $e) {
    // Handle errors gracefully
    if (APP_DEBUG) {
        $message = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
        $trace = $e->getTraceAsString();
    } else {
        $message = 'An internal error occurred. Please try again later.';
        $trace = '';

        // Log error
        error_log($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }

    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>500 - Internal Server Error</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #0F172A; color: #E2E8F0; }
            .error-container { text-align: center; max-width: 600px; padding: 2rem; }
            h1 { font-size: 4rem; margin: 0; color: #EF4444; }
            p { margin: 1rem 0; color: #94A3B8; }
            pre { background: #1E293B; padding: 1rem; border-radius: 8px; text-align: left; overflow-x: auto; font-size: 0.8rem; }
            a { color: #3B82F6; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>500</h1>
            <p>Internal Server Error</p>
            <p><?= htmlspecialchars($message) ?></p>
            <?php if ($trace && APP_DEBUG): ?>
                <pre><?= htmlspecialchars($trace) ?></pre>
            <?php endif; ?>
            <p><a href="/dashboard">← Back to Dashboard</a></p>
        </div>
    </body>
    </html>
    <?php
}
