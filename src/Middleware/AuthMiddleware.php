<?php
/**
 * Authentication Middleware
 * Checks if user is logged in before accessing protected routes
 */

namespace App\Middleware;

use App\Core\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(): void
    {
        if (!$this->session->isLoggedIn()) {
            // Session expired — try to restore it via the remember-me cookie
            if ((new \App\Services\AuthService())->attemptRememberLogin()) {
                return;
            }
            $this->session->setFlash('message', 'Please login to continue.');
            $this->session->setFlash('message_type', 'warning');
            $this->response->redirect('/login');
        }
    }
}

