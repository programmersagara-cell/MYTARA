<?php
/**
 * Authentication Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * Show login form
     */
public function loginForm(): void
    {
        if ($this->session->isLoggedIn()) {
            $user = $this->session->getUser();
            $this->redirect(($user['role'] ?? '') === 'user' ? '/tickets' : '/dashboard');
            return;
        }

        // Session expired but a valid remember-me cookie exists → restore session
        if ((new AuthService())->attemptRememberLogin()) {
            $user = $this->session->getUser();
            $this->redirect(($user['role'] ?? '') === 'user' ? '/tickets' : '/dashboard');
            return;
        }

        $this->view->render('auth/login', [
            'title' => 'Login',
            'layout' => 'layouts/auth',
        ]);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        $login = $this->request->input('login');
        $password = $this->request->input('password');

        if (!$login || !$password) {
            $this->redirectWith('/login', 'Please enter username/email and password.', 'warning');
            return;
        }

$result = $this->authService->login($login, $password, (bool) $this->request->input('remember'));

        if ($result['success']) {
            // Store the intended redirect target for the startup sequence to use
            $redirect = isset($result['user']['role']) && $result['user']['role'] === 'user' ? '/tickets' : '/dashboard';
            $this->session->set('startup_redirect_target', $redirect);
            $this->redirect('/startup');
        } else {
            $this->redirectWith('/login', $result['message'], 'danger');
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->redirectWith('/login', 'You have been logged out.', 'info');
    }

    /**
     * Show profile page
     */
    public function profile(): void
    {
        $this->render('auth/profile', [
            'title' => 'My Profile',
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(): void
    {
        $data = [
            'full_name' => $this->request->input('full_name'),
            'email' => $this->request->input('email'),
            'current_password' => $this->request->input('current_password'),
            'new_password' => $this->request->input('new_password'),
            'confirm_password' => $this->request->input('confirm_password'),
            'avatar' => $this->request->hasFile('avatar') ? $this->request->file('avatar') : null,
            'remove_avatar' => $this->request->input('remove_avatar') ? true : false,
        ];

        // Validate
        $errors = [];

        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required.';
        }
        if (empty($data['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        if (!empty($data['new_password']) && $data['new_password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        if (!empty($data['new_password']) && strlen($data['new_password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/profile');
            return;
        }

        $result = $this->authService->updateProfile($this->currentUser['id'], $data);

        if ($result['success']) {
            $this->redirectWith('/profile', $result['message'], 'success');
        } else {
            $this->redirectWith('/profile', $result['message'], 'danger');
        }
    }
}

