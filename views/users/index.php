<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users and permissions</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="<?= url('/users/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add User
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
<th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Assets</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar-sm">
                                    <?php if ($u['avatar']): ?>
                                        <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($u['avatar']) ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <span><?= htmlspecialchars($u['full_name']) ?></span>
                            </div>
                        </td>
                        <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
<td><?= \App\Helpers\Format::roleBadge($u['role']) ?></td>
                        <td>
                            <?php if (!empty($u['department'])): ?>
                                <span class="badge badge-outline"><?= htmlspecialchars($u['department']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($u['asset_count']) ?></td>
                        <td><?= \App\Helpers\Format::relativeTime($u['last_login']) ?></td>
                        <td>
                            <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <?php if ($u['id'] !== ($_SESSION['user']['id'] ?? 0)): ?>
                            <form method="POST" action="<?= url('/users/' . $u['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
