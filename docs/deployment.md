# Deployment Guide — ITSaAMS

> **IT Service and Asset Management System**
> PHP 8+ · MySQL 5.7+/MariaDB 10.3+ · Apache with mod_rewrite

This guide covers deploying ITSaAMS to a new server.

---

## 1. What you are deploying

| Layer | Notes |
|-------|-------|
| Entry point | `index.php` + `.htaccess` front controller |
| Config | `config/app.php`, `config/database.php`, `config/constants.php` |
| App code | `src/` (Core, Controllers, Models, Services, Middleware) |
| Views | `views/` with layouts under `views/layouts/` |
| Public assets | `public/assets/` (CSS/JS) and `public/uploads/` (avatars) |
| Database | MySQL/MariaDB; schema in `database/schema.sql` |
| Runtime data | `storage/` (logs, backups, view cache) |

The app is not tied to a framework. There is no compiled build step and no containerization layer shipped by default.

---

## 2. System Requirements

### Required

- PHP 8.0 or later
- PDO driver for MySQL (`pdo_mysql`)
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4 with `mod_rewrite` enabled, or a web server that can forward all requests to `index.php`

### Strongly recommended for production

- HTTPS
- Write access only where the app explicitly needs it
- Off-site backups of the database and `storage/backups/`
- Centralized or rotated logs

### Helpful PHP extensions

- `mbstring`
- `json`
- `openssl`
- `fileinfo`

### Session

Yes — `session` is needed; the app uses PHP-file sessions and sets session cookies from `config/app.php`.



---

## 3. Production Configuration
## 3. Production Configuration

### 3.1 `config/app.php`

This file is the main place to change for production. Key settings:

- **debug**
  ```php
  'debug' => getenv('APP_DEBUG') ?: true,
  ```
  For production, set `APP_DEBUG=false`, or change the default to `false`.

- **url**
  ```php
  'url' => 'http://localhost/itassets',
  ```
  Replace this with the real base URL of the deployed site. The current value is a dev placeholder.

- **timezone**
  ```php
  'timezone' => 'Asia/Manila',
  ```
  Keep or change to match your operational timezone.

- **session**
  ```php
  'session' => [
      'name'    => 'itassets_session',
      'lifetime'=> 7200,
      'path'    => '/',
      'domain'  => '',
      'secure'  => false,
      'httponly'=> true,
      'samesite'=> 'Lax',
  ],
  ```
  For HTTPS deployments, set `'secure' => true`.

- **auth**
  ```php
  'auth' => [
      'password_cost'      => 12,
      'max_login_attempts' => 5,
      'lockout_duration'   => 900,
  ],
  ```
  Reasonable defaults. Adjust brute-force protection if needed.

- **uploads**
  ```php
  'uploads' => [
      'max_size'     => 5 * 1024 * 1024,
      'allowed_types'=> ['jpg','jpeg','png','gif','svg','webp'],
      'path'         => __DIR__ . '/../public/uploads',
  ],
  ```
  Confirm `public/uploads/` is writable. Adjust max size/allowed types if your workflow uses larger or different files.

- **cache**
  ```php
  'cache' => [
      'enabled' => true,
      'path'    => __DIR__ . '/../storage/cache',
      'ttl'     => 3600,
  ],
  ```

- **backup**
  ```php
  'backup' => [
      'path'           => __DIR__ . '/../storage/backups',
      'retention_days' => 30,
  ],
  ```

- **audit**
  ```php
  'audit' => [
      'enabled'  => true,
      'log_path' => __DIR__ . '/../storage/logs/audit.log',
  ],
  ```

- **mail**
  ```php
  'mail' => [
      'smtp' => [
          'host'     => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
          'port'     => (int)(getenv('SMTP_PORT') ?: 587),
          'username' => getenv('SMTP_USERNAME') ?: 'programmersagara@gmail.com',
          'password' => getenv('SMTP_PASSWORD') ?: 'innj eanl dsth qaut',
          'secure'   => getenv('SMTP_SECURE') ?: 'tls',

          'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'programmersagara@gmail.com',
          'from_name'  => getenv('SMTP_FROM_NAME')  ?: 'Ticketing System',
      ],

      'admin_notification_email' => array_filter([
          getenv('ADMIN_NOTIFICATION_EMAIL') ?: 'programmersagara@gmail.com',
          // Add additional recipients below as needed:
          // 'networks@smpiclaguna.com',
          // 'technicals@smpiclaguna.com',
      ]),
  ],
  ```
  **Before going live, rotate the SMTP credentials.** The fallbacks are sample/dev values. Prefer environment variables: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_SECURE`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`, `ADMIN_NOTIFICATION_EMAIL`.

  If you do not want email notifications, set `admin_notification_email` to an empty array. The mail queue may still run, but it will have no recipients.

### 3.2 `config/database.php`

No structural changes are usually required. In production, prefer environment variables:

```php
return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'itassets',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'   => '',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];
```

Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` in the deployment environment. Keep `charset` and `collation` as `utf8mb4` / `utf8mb4_unicode_ci` unless you have a specific reason to change them.

### 3.3 `config/constants.php`

Key items to review in production:

- **App name / version**
  ```php
  define('APP_NAME', 'IT Services and Asset Management System');
  define('APP_VERSION', '1.5.0');
  ```

- **Debug flag**
  ```php
  define('APP_DEBUG', true);
  ```
  Set to `false` for production. This is used by `index.php` and the dispatch path to decide whether to render a detailed error page or a generic one.

- **Base path detection**
  ```php
  $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/itassets/index.php';
  $basePath = rtrim(dirname($scriptName), '/\\');
  define('APP_BASE_PATH', $basePath ?: '');
  ```
  In a root-level deployment this should resolve cleanly. If the app is under a subdirectory, verify it produces the expected base path.

- **URL detection**
  ```php
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  define('BASE_URL', $scheme . '://' . $host . APP_BASE_PATH);
  ```
  If you terminate TLS at a proxy, ensure the proxy forwards the correct scheme, otherwise the app may generate `http://` links even when the user is on HTTPS.

- **Business constants** — usually leave these alone unless your workflow changes:
  - `DEVICE_TYPES`
  - `ASSET_STATUSES`
  - `USER_ROLES`
  - `LICENSE_TYPES`, `LICENSE_STATUSES`
  - `RETIREMENT_REASONS`, `DISPOSAL_METHODS`, `DISPOSAL_STATUSES`
  - `DATA_DESTRUCTION_METHODS`
  - `LICENSE_EXPIRY_THRESHOLDS`
