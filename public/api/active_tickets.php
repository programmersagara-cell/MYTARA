<?php
/**
 * Active Tickets Count API
 * Returns JSON: { count: number, success: bool }
 * Requires: authenticated user with admin or operator role
 */
header('Content-Type: application/json; charset=utf-8');

// Bootstrap constants and autoloader from the app root.
// NOTE: this file lives in <app>/public/api, so the app root is two levels up
// (dirname(__DIR__, 2)). Using three levels escapes to the web root, which made
// the require below fail and emit a PHP fatal error as HTML, breaking the
// JSON response expected by the sidebar badge poll.
define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/constants.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Session;
use App\Models\Concern;

try {
    $session = Session::getInstance();
    if (!$session->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'success' => false]);
        exit;
    }

    $user = $session->getUser();
    $allowedRoles = ['admin', 'user'];
    if (!isset($user['role']) || !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'success' => false]);
        exit;
    }

    $result = Concern::allActive(1, 1000);
    $count = $result['total'];

    http_response_code(200);
    echo json_encode(['count' => (int)$count, 'success' => true]);
    exit;

} catch (Throwable $e) {
    // Log the real error internally; never expose exception details to clients.
    // Throwable (not just Exception) is required so PHP 8 TypeError/Error
    // faults also return JSON instead of an HTML fatal-error page.
    error_log('active_tickets.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'success' => false,
    ]);
    exit;
}
