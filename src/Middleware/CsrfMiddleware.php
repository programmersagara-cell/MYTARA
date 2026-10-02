<?php
/**
 * CSRF Protection Middleware
 * Validates CSRF token on POST/PUT/DELETE requests
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Helpers\Security;

class CsrfMiddleware extends Middleware
{
    public function handle(): void
    {
        if (in_array($this->request->method(), ['POST', 'PUT', 'DELETE'])) {
            $token = $this->request->csrfToken();

            if (!$token || !Security::verifyCsrfToken($token)) {
                if ($this->request->isAjax()) {
                    $this->response->json(['error' => 'Invalid CSRF token.'], 403);
                }

                $this->session->setFlash('message', 'Invalid security token. Please try again.');
                $this->session->setFlash('message_type', 'danger');
                // Only fall back to the Referer when it points back into this
                // application — never redirect to an externally supplied URL.
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                $base = rtrim(APP_BASE_PATH, '/');
                if ($referer === '' || stripos($referer, $base . '/') === false) {
                    $referer = '/';
                }
                $this->response->redirect($referer);
            }
        }
    }
}

