<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Edit User: <?= htmlspecialchars($editUser['username']) ?></h1>
        <p class="page-subtitle">Update user information and permissions</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/users') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/users/' . $editUser['id'] . '/update') ?>" class="form" enctype="multipart/form-data">
            <?= \App\Helpers\Security::csrfField() ?>

            <div class="form-group">
                <label class="form-label"><i class="fas fa-camera"></i> Profile Photo</label>
                <div class="avatar-upload">
                    <div class="avatar-upload-preview" id="avatarPreview">
                        <?php if (!empty($editUser['avatar'])): ?>
                            <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($editUser['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-upload-controls">
                        <input type="file" name="avatar" id="avatarInput" class="form-input" accept="image/*">
                        <small class="text-muted">JPG, PNG, GIF, SVG or WebP. Max 5MB.</small>
                        <?php if (!empty($editUser['avatar'])): ?>
                            <label class="form-checkbox mt-2">
                                <input type="checkbox" name="remove_avatar" value="1">
                                <span>Remove current photo</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" required value="<?= htmlspecialchars($editUser['full_name']) ?>">
                </div>
                <div class="form-group col-6">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required value="<?= htmlspecialchars($editUser['email']) ?>">
                </div>
            </div>
<div class="form-row">
                <div class="form-group col-4">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" value="<?= htmlspecialchars($editUser['username']) ?>" disabled>
                    <small class="text-muted">Username cannot be changed</small>
                </div>
                <div class="form-group col-4">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-input" required>
                        <option value="viewer" <?= $editUser['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                        <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : '' ?>>Users</option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-input">
                        <option value="1" <?= $editUser['is_active'] ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !$editUser['is_active'] ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-input">
                        <option value="">— No Department —</option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['name']) ?>" <?= (isset($editUser['department']) && $editUser['department'] === $dept['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Assigned department (used for ticket identification)</small>
                </div>
            </div>

            <hr class="my-3">
            <h4>Change Password</h4>
            <p class="text-muted small">Leave empty to keep current password</p>
            <div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" minlength="8">
                </div>
                <div class="form-group col-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= url('/users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
            </div>
        </form>
    </div>
</div>
