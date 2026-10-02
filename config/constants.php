<?php
/**
 * Global Constants
 */

// Path constants
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('SRC_PATH', BASE_PATH . '/src');
define('VIEWS_PATH', BASE_PATH . '/views');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CACHE_PATH', STORAGE_PATH . '/cache');

// Dynamic base path detection
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/itassets/index.php';
$basePath = rtrim(dirname($scriptName), '/\\');
define('APP_BASE_PATH', $basePath ?: '');

// URL constants - dynamically detected
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $scheme . '://' . $host . APP_BASE_PATH);
define('ASSETS_URL', BASE_URL . '/public/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMG_URL', ASSETS_URL . '/img');
define('UPLOADS_URL', BASE_URL . '/public/uploads');

/**
 * Generate a fully qualified URL with base path prefix.
 * Example: url('/dashboard') => '/itassets/dashboard'
 */
function url(string $path): string
{
    return APP_BASE_PATH . $path;
}

// Application constants
define('APP_NAME', 'IT Services and Asset Management System');
define('APP_VERSION', '1.5.0');
/**
 * Debug mode.
 * SECURITY: this MUST stay disabled in production — when enabled, unhandled
 * errors print full exception classes, file paths and stack traces to the
 * end user. Enable explicitly via the APP_DEBUG environment variable
 * (e.g. APP_DEBUG=1 in local development).
 */
$envDebug = strtolower(trim((string) getenv('APP_DEBUG')));
define('APP_DEBUG', in_array($envDebug, ['1', 'true', 'on', 'yes'], true));

// Pagination
define('PER_PAGE', 20);

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y h:i A');

// Device types
define('DEVICE_TYPES', ['pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'vm', 'other']);

// Asset statuses
define('ASSET_STATUSES', ['active', 'inactive', 'maintenance', 'retired', 'lost', 'reserved']);

// User roles
define('USER_ROLES', ['admin', 'user', 'viewer']);

// License types
define('LICENSE_TYPES', ['per_user', 'per_device', 'volume', 'subscription', 'perpetual', 'oem', 'trial', 'other']);

// License statuses
define('LICENSE_STATUSES', ['active', 'expiring_soon', 'expired', 'suspended', 'retired']);

// Retirement reasons
define('RETIREMENT_REASONS', [
    'end_of_life', 'hardware_failure', 'beyond_repair', 'obsolete',
    'warranty_expired', 'replacement', 'lost', 'damaged',
    'security_risk', 'upgrade', 'other'
]);

// Disposal methods
define('DISPOSAL_METHODS', [
    'e_waste_recycling', 'physical_destruction', 'data_destruction',
    'vendor_return', 'resale', 'donation', 'internal_transfer',
    'parts_recovery', 'other'
]);

// Disposal statuses
define('DISPOSAL_STATUSES', [
    'pending_disposal', 'awaiting_approval', 'approved', 'scheduled',
    'disposed', 'rejected', 'cancelled', 'archived'
]);

// Data destruction methods
define('DATA_DESTRUCTION_METHODS', [
    'secure_erase', 'disk_wiping', 'cryptographic_erase',
    'physical_destruction', 'factory_reset', 'not_applicable'
]);

// License expiry notification thresholds
define('LICENSE_EXPIRY_THRESHOLDS', [90, 60, 30, 7]);

