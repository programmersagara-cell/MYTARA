<?php
/**
 * Simple Router - Front Controller Pattern
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $fullPath = $this->prefix . $path;
        $allMiddleware = array_merge($this->groupMiddleware, $middleware);

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $allMiddleware,
            'pattern'    => $this->pathToRegex($fullPath),
        ];
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix = $this->prefix . $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function pathToRegex(string $path): string
    {
        // Convert {param} to named regex groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $method, string $uri): void
    {
        // HEAD requests must be served by the GET routes (clients ignore the
        // body); otherwise health checks / monitors get spurious 404s.
        if (strtoupper($method) === 'HEAD') {
            $method = 'GET';
        }

        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remove base path from URI (case-insensitive: on Windows, mod_rewrite's
        // internal redirect canonicalizes SCRIPT_NAME to the on-disk directory
        // casing (e.g. /Itara) even when the request used /itara — a case-sensitive
        // comparison here would fail to strip the prefix and cause spurious 404s)
        if ($basePath !== '/' && stripos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = $uri ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Run middleware chain
                $this->runMiddleware($route['middleware']);

                // Parse handler (Controller@method)
                [$controller, $action] = explode('@', $route['handler']);
                
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run controller
                $this->runController($controller, $action, $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        $this->renderError(404, 'Page not found');
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $m) {
            $class = $this->resolveMiddleware($m);
            $instance = new $class();
            $instance->handle();
        }
    }

    private function resolveMiddleware(string $name): string
    {
        $map = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'csrf' => \App\Middleware\CsrfMiddleware::class,
            'admin' => \App\Middleware\RoleMiddleware::class,
        ];

        return $map[$name] ?? $name;
    }

    private function runController(string $controller, string $action, array $params): void
    {
        $controllerClass = '\\App\\Controllers\\' . $controller;
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} not found");
        }

        $instance = new $controllerClass();
        
        if (!method_exists($instance, $action)) {
            throw new \RuntimeException("Action {$action} not found in {$controllerClass}");
        }

        call_user_func_array([$instance, $action], $params);
    }

    private function renderError(int $code, string $message): void
    {
        http_response_code($code);
        $view = new View();
        $user = \App\Core\Session::getInstance()->get('user');
        $view->render('layouts/error', [
            'code'    => $code,
            'message' => $message,
            'title'   => "Error {$code}",
            'user'    => $user,
        ]);
    }
}

