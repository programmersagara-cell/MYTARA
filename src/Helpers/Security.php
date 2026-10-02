<?php
/**
 * Security Helpers
 * CSRF protection, XSS prevention, input sanitization
 */

namespace App\Helpers;

class Security
{
    /**
     * Generate a CSRF token and store in session
     */
    public static function generateCsrfToken(): string
    {
        $session = \App\Core\Session::getInstance();
        
        if (!$session->has('_csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $session->set('_csrf_token', $token);
        }

        return $session->get('_csrf_token');
    }

    /**
     * Verify a CSRF token against the stored one
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $session = \App\Core\Session::getInstance();
        $storedToken = $session->get('_csrf_token');

        if (!$storedToken || empty($token)) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Generate a CSRF hidden input field
     */
    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /**
     * Generate a CSRF meta tag for AJAX requests
     */
    public static function csrfMeta(): string
    {
        $token = self::generateCsrfToken();
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Escape output for HTML (XSS prevention)
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * Sanitize filename (prevent path traversal)
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^\w\-\. ]/', '', $filename);
        
        // Limit length
        $filename = substr($filename, 0, 100);
        
        return $filename;
    }

    /**
     * Validate and sanitize an IP address
     */
    public static function sanitizeIp(string $ip): ?string
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $ip;
        }
        return null;
    }

    /**
     * Sanitize a MAC address to standard format (AA:BB:CC:DD:EE:FF)
     */
    public static function sanitizeMac(string $mac): ?string
    {
        $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
        
        if (strlen($mac) !== 12) {
            return null;
        }

        return implode(':', str_split($mac, 2));
    }

    /**
     * Generate a secure random password
     */
    public static function generatePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Hash a password with bcrypt
     */
    public static function hashPassword(string $password): string
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $cost = $appConfig['auth']['password_cost'] ?? 12;
        
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Verify a password against a hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if request is rate-limited
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $window = 900): bool
    {
        $session = \App\Core\Session::getInstance();
        $attempts = $session->get($key, []);
        
        // Clean old attempts
        $attempts = array_filter($attempts, fn($time) => $time > time() - $window);
        
        if (count($attempts) >= $maxAttempts) {
            return true; // Rate limited
        }
        
        $attempts[] = time();
        $session->set($key, $attempts);
        
        return false;
    }

    /**
     * Reset rate limit counter
     */
    public static function resetRateLimit(string $key): void
    {
        $session = \App\Core\Session::getInstance();
        $session->remove($key);
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
        }

        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File exceeds maximum size of ' . ($maxSize / 1024 / 1024) . 'MB'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowedTypes)];
        }

        return ['valid' => true, 'extension' => $extension];
    }
}

