<?php
/**
 * Authentication Service
 */

namespace App\Services;

use App\Models\User;
use App\Core\Session;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Security;
use App\Helpers\File;

class AuthService
{
    private Session $session;
    private Request $request;
    private AuditService $audit;
    private const REMEMBER_COOKIE = 'itassets_remember';
    private const REMEMBER_TTL = 2592000; // 30 days

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->request = new Request();
        $this->audit = new AuditService();
    }

    /**
     * Attempt login with credentials
     */
    public function login(string $login, string $password, bool $remember = false): array
    {
        // Check rate limiting. The key uses the real peer IP (never a
        // client-supplied forwarding header) so lockouts cannot be bypassed.
        $rateKey = 'login_attempts_' . $this->request->peerIp();
        if (Security::checkRateLimit($rateKey)) {
            // Rate limiter fired — one audit entry per lockout event.
            $this->audit->log('account_locked', 'auth', null, $login, null, null, null, 'Too many failed login attempts');
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again in 15 minutes.'
            ];
        }

        $user = User::authenticate($login, $password);

        if (!$user) {
            // Never log the submitted password — only the attempted identifier.
            $this->audit->log('login_failed', 'auth', null, $login, null, null, null, 'invalid credentials');
            return [
                'success' => false,
                'message' => 'Invalid credentials or account is disabled.'
            ];
        }

        // Set session
        $this->session->setUser($user);

        // Issue persistent login token if "Remember me" was checked
        if ($remember) {
            $this->issueRememberToken((int) $user['id']);
        } else {
            $this->clearRememberCookie();
        }

        // Reset rate limit on success
        Security::resetRateLimit($rateKey);

        // Log login
        $this->logAction($user['id'], 'login', 'User logged in');

        return [
            'success' => true,
            'user' => $user,
            'message' => 'Welcome back, ' . $user['full_name'] . '!'
        ];
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        $user = $this->session->getUser();
        if ($user) {
            $this->logAction($user['id'], 'logout', 'User logged out');
        }
        $this->revokeRememberToken();
        $this->session->destroy();
    }

    /**
     * Issue a persistent "remember me" token and set its cookie.
     * Uses selector + validator pattern: only the SHA-256 hash of the
     * validator is stored, so a DB leak cannot be replayed as a cookie.
     */
    private function issueRememberToken(int $userId): void
    {
        try {
            $selector = bin2hex(random_bytes(12));   // 24 chars, lookup key
            $validator = bin2hex(random_bytes(32));  // 64 chars, secret
            $expires = time() + self::REMEMBER_TTL;

            $db = Database::getInstance();
            // Housekeeping: purge expired tokens
            $db->query('DELETE FROM remember_tokens WHERE expires_at < NOW()');
            $db->insert('remember_tokens', [
                'user_id' => $userId,
                'selector' => $selector,
                'validator_hash' => hash('sha256', $validator),
                'expires_at' => date('Y-m-d H:i:s', $expires),
            ]);

            (new Response())->cookie(
                self::REMEMBER_COOKIE,
                $selector . ':' . $validator,
                $expires,
                '/',
                null,
                false, // set true when served over HTTPS
                true
            );
        } catch (\Exception $e) {
            error_log('Failed to issue remember token: ' . $e->getMessage());
        }
    }

    /**
     * Attempt to restore a session from the remember-me cookie.
     * Called by AuthMiddleware when the PHP session has expired.
     * Rotates the token after successful use (token reuse detection safe).
     */
    public function attemptRememberLogin(): bool
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (!$cookie || substr_count($cookie, ':') !== 1) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        try {
            $db = Database::getInstance();
            $token = $db->fetch(
                'SELECT rt.*, u.id AS uid, u.is_active FROM remember_tokens rt
                 JOIN users u ON u.id = rt.user_id
                 WHERE rt.selector = ? AND rt.expires_at > NOW()',
                [$selector]
            );

            if (!$token || !$token['is_active']) {
                // Unknown/expired/stale token — remove the cookie to avoid loops
                $this->clearRememberCookie();
                return false;
            }

            if (!hash_equals($token['validator_hash'], hash('sha256', $validator))) {
                // Validator mismatch: possible theft — revoke all tokens for this user
                $db->delete('remember_tokens', 'user_id = ?', [$token['user_id']]);
                $this->clearRememberCookie();
                return false;
            }

            $user = \App\Models\User::find((int) $token['user_id']);
            if (!$user) {
                $this->clearRememberCookie();
                return false;
            }
            unset($user['password']);

            // Rotate: single-use tokens prevent replay of a stolen cookie
            $db->delete('remember_tokens', 'id = ?', [$token['id']]);
            $this->session->setUser($user);
            $this->issueRememberToken((int) $user['id']);

            $this->logAction((int) $user['id'], 'login', 'Session restored via remember-me token');
            return true;
        } catch (\Exception $e) {
            error_log('Remember-me restore failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all remember tokens for the current user and clear the cookie.
     */
    public function revokeRememberToken(): void
    {
        $user = $this->session->getUser();
        if ($user) {
            try {
                Database::getInstance()->delete('remember_tokens', 'user_id = ?', [$user['id']]);
            } catch (\Exception $e) {
                error_log('Failed to revoke remember token: ' . $e->getMessage());
            }
        }
        $this->clearRememberCookie();
    }

    /**
     * Expire the remember cookie in the browser.
     */
    private function clearRememberCookie(): void
    {
        (new Response())->deleteCookie(self::REMEMBER_COOKIE, '/');
    }

    /**
     * Get current authenticated user
     */
    public function currentUser(): ?array
    {
        return $this->session->getUser();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->session->isLoggedIn();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string ...$roles): bool
    {
        $user = $this->currentUser();
        if (!$user) return false;
        return in_array($user['role'], $roles);
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): array
    {
        $updateData = [];

        if (isset($data['full_name'])) {
            $updateData['full_name'] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        // Handle avatar removal
        if (!empty($data['remove_avatar']) && empty($data['avatar'])) {
            $user = User::find($userId);
            if ($user && $user['avatar']) {
                (new File())->delete('avatars/' . $user['avatar']);
            }
            $updateData['avatar'] = null;
        }

        // Handle avatar upload
        if (!empty($data['avatar']) && is_array($data['avatar'])) {
            $user = User::find($userId);
            $upload = (new File())->upload($data['avatar'], 'avatars');

            if (!$upload['success']) {
                return ['success' => false, 'message' => $upload['error']];
            }

            // Delete old avatar if exists
            if ($user && $user['avatar']) {
                (new File())->delete('avatars/' . $user['avatar']);
            }

            $updateData['avatar'] = $upload['filename'];
        }

        if (!empty($updateData)) {
            User::update($userId, $updateData);

            // Update session data
            $user = User::find($userId);
            if ($user) {
                unset($user['password']);
                $this->session->set('user', $user);
            }

            $this->logAction($userId, 'profile_update', 'Profile updated');
        }

        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            $user = User::find($userId);
            if ($user && Security::verifyPassword($data['current_password'], $user['password'])) {
                User::update($userId, [
                    'password' => Security::hashPassword($data['new_password'])
                ]);
                $this->logAction($userId, 'password_change', 'Password changed');
            } else {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
        }

        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }

    /**
    *Log an authentication action into the unified audit trail
     * Uses audit_log: the acting user comes from the session (a failed login
     * has none, and is stored with user_id NULL, which the FK permits).
     */
    private function logAction(int $userId, string $action, string $description): void
    {
        $user = $this->session->getUser();
        $reference = $user['username'] ?? null;

        $this->audit->log($action, 'auth', (int) $userId, $reference, null, null, null, $description);
    }
}

