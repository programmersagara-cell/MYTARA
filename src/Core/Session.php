<?php
/**
 * Session Manager
 */

namespace App\Core;

class Session
{
    private static ?Session $instance = null;
    private bool $started = false;

    private function __construct()
    {
        $this->start();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start the session
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookies
            $appConfig = require CONFIG_PATH . '/app.php';
            $sessionConfig = $appConfig['session'];

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', $sessionConfig['httponly'] ? '1' : '0');
            ini_set('session.cookie_samesite', $sessionConfig['samesite']);
            ini_set('session.gc_maxlifetime', (string) $sessionConfig['lifetime']);
            ini_set('session.sid_length', '48');
            ini_set('session.sid_bits_per_character', '6');

            session_name($sessionConfig['name']);
            session_start();
        }

        $this->started = true;

        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['_last_regeneration'])) {
            $this->regenerate();
        } elseif (time() - $_SESSION['_last_regeneration'] > 3600) {
            $this->regenerate();
        }
    }

    /**
     * Set a session value
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set a flash message (one-time notification)
     */
    public function setFlash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flash message and remove it
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash message exists
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Get all flash messages (clears them)
     */
    public function allFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /**
     * Set user data after authentication
     */
    public function setUser(array $user): void
    {
        $this->set('user', $user);
        $this->set('logged_in', true);
        $this->set('login_time', time());
        $this->regenerate(); // Prevent session fixation
    }

    /**
     * Get current authenticated user
     */
    public function getUser(): ?array
    {
        return $this->get('user');
    }

    /**
     * Check if user is authenticated
     */
    public function isLoggedIn(): bool
    {
        return $this->get('logged_in') === true;
    }

    /**
     * Destroy the session (logout)
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        $this->started = false;
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}

