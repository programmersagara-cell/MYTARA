<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Instructions</h1>
        <p class="page-subtitle">Manage department instructions and key notes</p>
    </div>
</div>

<div class="row">
    <div class="col-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus-circle"></i> Add Instruction</div>
            <div class="card-body">
                <form method="post" action="<?= url('/tickets/instructions') ?>">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                    <div class="form-group">
                        <label for="tittle" class="form-label">Title</label>
                        <input type="text" id="tittle" name="tittle" class="form-input" placeholder="Instruction title">
                    </div>
                    <div class="form-group">
                        <label for="instruction_text" class="form-label">Instruction Text <span class="text-danger">*</span></label>
                        <textarea id="instruction_text" name="instruction_text" class="form-input" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Add Instruction</button>
                </form>
            </div>
        </div>
    </div>

<div class="col-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Instructions
                <span class="badge badge-primary float-end"><?= count($instructions ?? []) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($instructions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No instructions yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($instructions as $in): ?>
                    <div class="list-item">
                        <div class="list-item-body">
                            <div class="list-item-title"><?= htmlspecialchars($in['tittle'] ?? 'Untitled') ?></div>
                            <div class="list-item-text"><?= nl2br(htmlspecialchars($in['instruction_text'])) ?></div>
                            <small class="text-muted"><?= \App\Helpers\Format::datetime($in['created_at'] ?? '') ?></small>
                        </div>
                        <div class="list-item-actions">
                            <button type="button" class="btn btn-sm btn-outline" onclick="showModal('editModal<?= $in['id'] ?>')" title="Edit"><i class="fas fa-edit"></i></button>
                            <form method="post" action="<?= url('/tickets/instructions/' . $in['id'] . '/delete') ?>" class="d-inline">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-confirm="Delete this instruction?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>

<!-- Edit Modal -->
                    <div class="modal-overlay" id="editModal<?= $in['id'] ?>">
                        <div class="modal">
                            <div class="modal-header">
                                <h3>Edit Instruction</h3>
                                <button type="button" class="modal-close" onclick="hideModal('editModal<?= $in['id'] ?>')">&times;</button>
                            </div>
                            <form method="post" action="<?= url('/tickets/instructions/' . $in['id'] . '/update') ?>">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="tittle" class="form-input" value="<?= htmlspecialchars($in['tittle'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Instruction Text <span class="text-danger">*</span></label>
                                        <textarea name="instruction_text" class="form-input" rows="4" required><?= htmlspecialchars($in['instruction_text']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" onclick="hideModal('editModal<?= $in['id'] ?>')">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
