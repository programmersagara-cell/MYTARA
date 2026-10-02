<?php
/**
 * Application Configuration
 */

return [
    'name' => 'IT Asset Management System',
    'version' => '1.0.0',
    // Debug follows the central APP_DEBUG constant (config/constants.php),
    // which is only true when the APP_DEBUG environment variable is set.
    'debug' => APP_DEBUG,
    'url' => 'http://localhost/itassets',
    'timezone' => 'Asia/Manila',

    // Session configuration
    'session' => [
        'name' => 'itassets_session',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set true in production (HTTPS only)
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // Authentication
    'auth' => [
        'password_cost' => 12, // bcrypt cost factor
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],

    // Pagination
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    // File uploads
    'uploads' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        'path' => __DIR__ . '/../public/uploads',
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/cache',
        'ttl' => 3600, // 1 hour
    ],

    // Backup
    'backup' => [
        'path' => __DIR__ . '/../storage/backups',
        'retention_days' => 30,
    ],

    // Audit
    'audit' => [
        'enabled' => true,
        'log_path' => __DIR__ . '/../storage/logs/audit.log',
    ],

    // Mail (SMTP) — used by the ticketing notification system
    'mail' => [
       'smtp' => [
        // Environment variable fallback — if env vars are set, they override.
        // For local XAMPP development, hardcoded values are used as fallback.
        'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => (int)(getenv('SMTP_PORT') ?: 587),
        'username' => getenv('SMTP_USERNAME') ?: '',
        // SECURITY: never hardcode the SMTP password here — it is a committed
        // secret. Set it via the SMTP_PASSWORD environment variable instead.
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'secure' => getenv('SMTP_SECURE') ?: 'tls',

    'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'programmersagara@gmail.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Ticketing System',
    ],

    // List of admin email addresses to notify when a new concern is submitted.
    'admin_notification_email' => array_filter([
        getenv('ADMIN_NOTIFICATION_EMAIL') ?: 'programmersagara@gmail.com',
        // Add additional recipients below as needed:
        // 'networks@smpiclaguna.com',
        // 'technicals@smpiclaguna.com',
    ]),
    ],
];

