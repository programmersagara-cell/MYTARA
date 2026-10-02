<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Create User</h1>
        <p class="page-subtitle">Add a new system user</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/users') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/users') ?>" class="form">
            <?= \App\Helpers\Security::csrfField() ?>
            <div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" required placeholder="John Doe">
                </div>
                <div class="form-group col-6">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" required placeholder="johndoe" minlength="3">
                </div>
            </div>
<div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required placeholder="john@company.com">
                </div>
                <div class="form-group col-3">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-input" required>
                        <option value="viewer">Viewer</option>
                        <option value="user">Users</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group col-3">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-input">
                        <option value="">— Select Department —</option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label class="form-label">Initial Password *</label>
                    <input type="password" name="password" class="form-input" required minlength="8" placeholder="Min. 8 chars">
                </div>
            </div>
            <div class="form-actions">
                <a href="<?= url('/users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
            </div>
        </form>
    </div>
</div>
