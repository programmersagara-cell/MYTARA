<?php $layout = 'layouts/main'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Departments</h1>
        <p class="page-subtitle">Manage departments, sections, and organizational units</p>
    </div>
<div class="page-actions">
        <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
        <button class="btn btn-primary" onclick="showModal('createModal')">
            <i class="fas fa-plus"></i> Add Department
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Section</th>
                        <th>Description</th>
                        <th>Assets</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($dept['code']) ?></code></td>
                        <td>
                            <a href="<?= url('/departments/' . $dept['id'] . '/assets') ?>"
                               class="dept-assets-link"
                               title="View assets registered to <?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>">
                                <i class="fas fa-building"></i>
                                <strong><?= htmlspecialchars($dept['name']) ?></strong>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($dept['section'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($dept['description'] ?? '—') ?></td>
                        <td><?= number_format($dept['asset_count']) ?> assets</td>
<td>
                            <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="editDept(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dept['code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dept['section'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($dept['description'] ?? '', ENT_QUOTES) ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="<?= url('/departments/' . $dept['id'] . '/delete') ?>" style="display:inline" 
                                  onsubmit="return confirm('Delete this department?')">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Department</h3>
            <button class="modal-close" onclick="hideModal('createModal')">&times;</button>
        </div>
        <form method="POST" action="<?= url('/departments') ?>">
            <?= \App\Helpers\Security::csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Department Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="code" class="form-input" placeholder="e.g., IT, HR, FIN" maxlength="20" required>
                    <small class="text-muted">Short code (max 20 characters)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-building"></i> Department Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-input" placeholder="e.g., Information Technology" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-sitemap"></i> Section
                    </label>
                    <input type="text" name="section" class="form-input" placeholder="e.g., IT Support, IT Development" maxlength="100">
                    <small class="text-muted">Optional sub-department or section name</small>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Optional description of the department's function"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('createModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Department
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Department</h3>
            <button class="modal-close" onclick="hideModal('editModal')">&times;</button>
        </div>
        <form method="POST" id="editDeptForm">
            <?= \App\Helpers\Security::csrfField() ?>
            <div class="modal-body">
                <input type="hidden" name="_method" value="PUT">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Department Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="code" id="editCode" class="form-input" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-building"></i> Department Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-sitemap"></i> Section
                    </label>
                    <input type="text" name="section" id="editSection" class="form-input" placeholder="e.g., IT Support, IT Development" maxlength="100">
                    <small class="text-muted">Optional sub-department or section name</small>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea name="description" id="editDesc" class="form-input" rows="3" placeholder="Optional description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('editModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Department
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.dept-assets-link {
    font-weight: 600;
    color: var(--primary);
}
.dept-assets-link i {
    margin-right: 0.35rem;
    opacity: 0.85;
}
.dept-assets-link:hover {
    color: var(--primary-hover);
    text-decoration: underline;
}
</style>

<script>
function editDept(id, name, code, section, desc) {
    document.getElementById('editName').value = name;
    document.getElementById('editCode').value = code;
    document.getElementById('editSection').value = section;
    document.getElementById('editDesc').value = desc;
    document.getElementById('editDeptForm').action = BASE_PATH + '/departments/' + id + '/update';
    showModal('editModal');
}
</script>
