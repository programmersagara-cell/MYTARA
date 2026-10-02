<?php
/**
 * User Management Controller (Admin)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Department;
use App\Helpers\Security;
use App\Helpers\File;
use App\Services\AuditService;

class UserController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin');
        $this->auditService = new AuditService();
    }

    /**
     * List all users
     */
    public function index(): void
    {
        $users = User::getAllWithCounts();
        $this->render('users/index', [
            'title' => 'User Management',
            'users' => $users,
        ]);
    }

    /**
     * Show create user form
     */
public function create(): void
    {
        $this->render('users/create', [
            'title' => 'Create User',
            'departments' => Department::getOptions(),
        ]);
    }

    /**
     * Store new user
     */
    public function store(): void
    {
        $data = $this->request->only(['username', 'email', 'password', 'full_name', 'role', 'department']);

        $validated = $this->validate($data, [
            'username' => 'required|min:3|max:50|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'full_name' => 'required|max:100',
            'role' => 'required|in:admin,user,viewer',
            'department' => 'max:100',
        ]);

        if ($this->validationFails()) {
            $this->redirectWith('/users/create', 'Please fix validation errors.', 'danger');
            return;
        }

        $validated['password'] = Security::hashPassword($validated['password']);
        $userId = User::create($validated);

        $this->auditService->log('user_created', 'user', $userId, $validated['username'], 'role', null, $validated['role'], 'User account created');

        $this->redirectWith('/users', 'User created successfully.', 'success');
    }

    /**
     * Show edit user form
     */
    public function edit(int $id): void
    {
        $editUser = User::find($id);
        if (!$editUser) {
            $this->redirectWith('/users', 'User not found.', 'danger');
            return;
        }
$this->render('users/edit', [
            'title' => 'Edit User',
            'editUser' => $editUser,
            'departments' => Department::getOptions(),
        ]);
    }

    /**
     * Update user
     */
    public function update(int $id): void
    {
        $data = $this->request->only(['email', 'full_name', 'role', 'is_active', 'department']);
        $data['is_active'] = $this->request->input('is_active') ? 1 : 0;

        $validated = $this->validate(array_merge($data, ['id' => $id]), [
            'email' => 'required|email|unique:users,email,' . $id,
            'full_name' => 'required|max:100',
            'role' => 'required|in:admin,user,viewer',
            'department' => 'max:100',
            // Without a rule the validator drops is_active, so the toggle was
            // never persisted — which would also make user_deactivated lie.
            'is_active' => 'boolean',
        ]);

        if ($this->validationFails()) {
            $this->redirectWith('/users/' . $id . '/edit', 'Please fix validation errors.', 'danger');
            return;
        }

        // Handle password change (validate length and confirmation)
        $password = $this->request->input('password');
        $passwordConfirmation = $this->request->input('password_confirmation');
        if ($password) {
            if (strlen($password) < 8) {
                $this->redirectWith('/users/' . $id . '/edit', 'Password must be at least 8 characters.', 'danger');
                return;
            }
            if ($passwordConfirmation === null || $password !== $passwordConfirmation) {
                $this->redirectWith('/users/' . $id . '/edit', 'Password confirmation does not match.', 'danger');
                return;
            }
            $validated['password'] = Security::hashPassword($password);
        }

        // Handle avatar removal
        if ($this->request->input('remove_avatar') && !$this->request->hasFile('avatar')) {
            $user = User::find($id);
            if ($user && $user['avatar']) {
                (new File())->delete('avatars/' . $user['avatar']);
            }
            $validated['avatar'] = null;
        }

        // Handle avatar upload
        if ($this->request->hasFile('avatar')) {
            $user = User::find($id);
            $upload = (new File())->upload($this->request->file('avatar'), 'avatars');

            if (!$upload['success']) {
                $this->redirectWith('/users/' . $id . '/edit', $upload['error'], 'danger');
                return;
            }

            // Delete old avatar if exists
            if ($user && $user['avatar']) {
                (new File())->delete('avatars/' . $user['avatar']);
            }

            $validated['avatar'] = $upload['filename'];
        }

        $before = User::find($id);
        $username = $before['username'] ?? ('#' . $id);

        User::update($id, $validated);

        // ─── Audit: one entry per changed field; dedicated security actions ───
        foreach (['email', 'full_name', 'department', 'avatar'] as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }
            $old = $before[$field] ?? null;
            if ((string) $old === (string) $validated[$field]) {
                continue;
            }
            $this->auditService->log('user_updated', 'user', $id, $username, $field, $old, $validated[$field], 'User details updated');
        }

        if (array_key_exists('role', $validated) && ($before['role'] ?? null) !== $validated['role']) {
            $this->auditService->log('user_role_changed', 'user', $id, $username, 'role', $before['role'] ?? null, $validated['role'], 'User role changed');
        }

        if (array_key_exists('is_active', $validated) && (int) ($before['is_active'] ?? 1) !== (int) $validated['is_active']) {
            if ((int) $validated['is_active'] === 0) {
                $this->auditService->log('user_deactivated', 'user', $id, $username, 'is_active', 1, 0, 'User account deactivated');
            } else {
                $this->auditService->log('user_updated', 'user', $id, $username, 'is_active', 0, 1, 'User account re-activated');
            }
        }

        if (!empty($validated['password'])) {
            // Never write the password (or its hash) to the audit trail.
            $this->auditService->log('user_password_reset', 'user', $id, $username, 'password', null, null, 'Password reset by admin');
        }

        // If this is the currently logged-in user, refresh session data
        if ($this->currentUser && (int) $this->currentUser['id'] === (int) $id) {
            $updated = User::find($id);
            if ($updated) {
                unset($updated['password']);
                $this->session->set('user', $updated);
            }
        }

        $this->redirectWith('/users', 'User updated successfully.', 'success');
    }

    /**
     * Delete user
     */
    public function destroy(int $id): void
    {
        if ($id === $this->currentUser['id']) {
            $this->redirectWith('/users', 'You cannot delete your own account.', 'danger');
            return;
        }

        $user = User::find($id);
        User::delete($id);

        $this->auditService->log('user_deleted', 'user', $id, $user['username'] ?? ('#' . $id), 'role', $user['role'] ?? null, null, 'User account deleted');

        $this->redirectWith('/users', 'User deleted successfully.', 'success');
    }
}

