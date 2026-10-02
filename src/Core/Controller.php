<?php
/**
 * Base Controller
 */

namespace App\Core;

use App\Helpers\Security;

abstract class Controller
{
    protected View $view;
    protected Request $request;
    protected Response $response;
    protected Session $session;
    protected Validator $validator;
    protected ?array $currentUser = null;

    public function __construct()
    {
        $this->view = new View();
        $this->request = new Request();
        $this->response = new Response();
        $this->session = Session::getInstance();
        $this->validator = new Validator();
        $this->currentUser = $this->session->get('user');

        // Track last_seen for online status (throttled to once per minute)
        if ($this->currentUser) {
            $lastSeen = $this->session->get('last_seen_update');
            if (!$lastSeen || (time() - $lastSeen) > 60) {
                try {
                    $db = \App\Core\Database::getInstance();
                    $db->update('users', ['last_seen' => date('Y-m-d H:i:s')], 'id = ?', [$this->currentUser['id']]);
                    $this->session->set('last_seen_update', time());
                } catch (\Exception $e) {
                    // Silently fail - last_seen tracking is best-effort
                }
            }
        }
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    /**
     * Redirect back to previous page
     */
    protected function redirectBack(): void
    {
        // Only use the Referer when it points back into this application —
        // never redirect to an externally supplied URL (open redirect).
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $base = rtrim(APP_BASE_PATH, '/');
        if ($referer === '' || stripos($referer, $base . '/') === false) {
            $this->response->redirect('/');
            return;
        }
        $this->response->redirect($referer);
    }

    /**
     * Redirect with flash message
     */
    protected function redirectWith(string $url, string $message, string $type = 'success'): void
    {
        $this->session->setFlash('message', $message);
        $this->session->setFlash('message_type', $type);
        $this->response->redirect($url);
    }

    /**
     * Render a view
     */
    protected function render(string $view, array $data = []): void
    {
        // Only set $data['user'] to currentUser if not already provided (e.g., by UserController@edit)
        if (!isset($data['user'])) {
            $data['user'] = $this->currentUser;
        }
        $this->view->render($view, $data);
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    /**
     * Check if user has a specific role
     */
    protected function requireRole(string ...$roles): bool
    {
        if (!$this->currentUser || !in_array($this->currentUser['role'], $roles)) {
            // Send the user to a landing page they can actually access,
            // otherwise operators are bounced back to /dashboard which they
            // are not allowed on, causing an infinite redirect loop.
            $fallback = (($this->currentUser['role'] ?? '') === 'user') ? '/tickets' : '/dashboard';
            $this->redirectWith($fallback, 'Unauthorized access', 'danger');
            return false;
        }
        return true;
    }

    /**
     * Get CSRF token for forms
     */
    protected function csrfField(): string
    {
        return Security::csrfField();
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): array
    {
        return $this->validator->validate($data, $rules);
    }

    /**
     * Check if validation passed
     */
    protected function validationFails(): bool
    {
        return $this->validator->hasErrors();
    }

    /**
     * Get validation errors
     */
    protected function validationErrors(): array
    {
        return $this->validator->getErrors();
    }
}

