<?php
/**
 * HTTP Request Handler
 */

namespace App\Core;

class Request
{
    private array $queryParams;
    private array $body;
    private array $files;
    private array $cookies;
    private ?string $rawBody = null;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->body = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }

    /**
     * Get request method
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Check if method is GET
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Check if method is POST
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if method is AJAX
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get query parameter
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function allQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Get POST body parameter
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->isPost()) {
            return $this->body[$key] ?? $default;
        }
        return $this->query($key, $default);
    }

    /**
     * Get all input data (POST + GET)
     */
    public function all(): array
    {
        return array_merge($this->queryParams, $this->body);
    }

    /**
     * Get only specific fields
     */
    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->input($key);
        }
        return array_filter($data, fn($v) => $v !== null);
    }

    /**
     * Check if parameter exists
     */
    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->queryParams[$key]);
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if file was uploaded
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get raw request body (for JSON APIs)
     */
    public function raw(): ?string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }

    /**
     * Get JSON body as array
     */
    public function json(): ?array
    {
        $raw = $this->raw();
        if ($raw) {
            return json_decode($raw, true);
        }
        return null;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get the real peer IP address (REMOTE_ADDR only).
     *
     * Use this for security decisions (login rate-limit / lockout keys) where
     * a client-supplied forwarding header must NOT be trusted
     * (SECURITY_REMEDIATION_PROMPT.md F5). Audit records use ip().
     */
    public function peerIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get CSRF token from header
     */
    public function csrfToken(): ?string
    {
        return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('_csrf_token');
    }
}

