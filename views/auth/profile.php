<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your account settings</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="profile-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="avatar-lg">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 5rem; color: var(--primary);"></i>
                    <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($user['full_name']) ?></h3>
                <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'user' ? 'primary' : 'secondary') ?>">
                    <?= ucfirst($user['role']) ?>
                </span>
                <p class="text-muted mt-2">
                    <small>Joined <?= date('M d, Y', strtotime($user['created_at'])) ?></small>
                </p>
            </div>
        </div>
    </div>

    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/profile') ?>" class="form" enctype="multipart/form-data">
                    <?= \App\Helpers\Security::csrfField() ?>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-camera"></i> Profile Photo</label>
                        <div class="avatar-upload">
                            <div class="avatar-upload-preview" id="avatarPreview">
                                <?php if ($user['avatar']): ?>
                                    <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="avatar-upload-controls">
                                <input type="file" name="avatar" id="avatarInput" class="form-input" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF, SVG or WebP. Max 5MB.</small>
                                <?php if ($user['avatar']): ?>
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
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h4>Change Password</h4>
                    <p class="text-muted small">Leave empty to keep current password</p>

                    <div class="form-row">
                        <div class="form-group col-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input" placeholder="••••••••">
                        </div>
                        <div class="form-group col-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" placeholder="Min. 8 characters">
                        </div>
                        <div class="form-group col-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="new_password_confirmation" class="form-input" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
