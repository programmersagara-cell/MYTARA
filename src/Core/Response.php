<?php
/**
 * HTTP Response Handler
 */

namespace App\Core;

class Response
{
    /**
     * Set HTTP response code
     */
    public function status(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Set a response header
     */
    public function header(string $key, string $value): self
    {
        header("{$key}: {$value}");
        return $this;
    }

    /**
     * Redirect to a URL
     * Prepends APP_BASE_PATH for relative URLs (e.g., /dashboard -> /itassets/dashboard)
     */
    public function redirect(string $url): void
    {
        // If URL is relative (starts with / but not //), prepend base path
        if (strlen($url) > 1 && $url[0] === '/' && $url[1] !== '/') {
            $url = APP_BASE_PATH . $url;
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Return JSON response
     */
    public function json(array $data, int $status = 200): void
    {
        $this->status($status);
        $this->header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Download a file
     */
    public function download(string $filePath, ?string $filename = null): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found');
        }

        $filename = $filename ?? basename($filePath);
        
        $this->header('Content-Type', mime_content_type($filePath));
        $this->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $this->header('Content-Length', (string) filesize($filePath));
        $this->header('Cache-Control', 'no-cache');

        readfile($filePath);
        exit;
    }

    /**
     * Set a cookie
     */
    public function cookie(string $name, string $value, int $expires = 0, string $path = '/', ?string $domain = null, bool $secure = false, bool $httponly = true): self
    {
        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => 'Lax',
        ]);
        return $this;
    }

    /**
     * Delete a cookie
     */
    public function deleteCookie(string $name, string $path = '/'): self
    {
        $this->cookie($name, '', time() - 3600, $path);
        return $this;
    }

    /**
     * Set cache headers
     */
    public function cache(int $seconds = 3600): self
    {
        $this->header('Cache-Control', "public, max-age={$seconds}");
        $this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        return $this;
    }

    /**
     * Disable caching
     */
    public function noCache(): self
    {
        $this->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        return $this;
    }
}

