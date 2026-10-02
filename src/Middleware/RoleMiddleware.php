<?php
/**
 * Role-based Access Control Middleware
 * Checks if user has required role(s)
 */

namespace App\Middleware;

use App\Core\Middleware;

class RoleMiddleware extends Middleware
{
    private array $allowedRoles;

    public function __construct(array $roles = ['admin'])
    {
        parent::__construct();
        $this->allowedRoles = $roles;
    }

    public function handle(): void
    {
        $user = $this->session->getUser();

        if (!$user) {
            $this->reject(401, 'Authentication required.');
        }

        if (!in_array($user['role'], $this->allowedRoles)) {
            $this->reject(403, 'Insufficient permissions to access this resource.');
        }
    }

    /**
     * Create middleware instance with specific roles
     */
    public static function allow(string ...$roles): self
    {
        return new self($roles);
    }
}

