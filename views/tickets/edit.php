<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Concern</h1>
        <p class="page-subtitle">Ticket #<?= htmlspecialchars($concern['ticket_number'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/tickets') ?>" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Concerns
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Update Concern
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('/tickets/' . $concern['id'] . '/update') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\Security::generateCsrfToken() ?>">

                    <div class="form-group">
                        <label for="sender_name" class="form-label">Sender Name <span class="text-danger">*</span></label>
                        <input type="text" id="sender_name" name="sender_name" class="form-input"
                               value="<?= htmlspecialchars($concern['sender_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-input" rows="5" required><?= htmlspecialchars($concern['description']) ?></textarea>
                    </div>

                    <?php if (!empty($concern['image_path'])): ?>
                    <div class="form-group">
                        <label class="form-label">Current Attachment</label>
                        <div>
                            <img src="<?= UPLOADS_URL ?>/<?= htmlspecialchars($concern['image_path']) ?>" alt="Attachment" class="img-thumbnail" style="max-height: 160px;">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="image" class="form-label">Replace Attachment (optional)</label>
                        <input type="file" id="image" name="image" class="form-input" accept="image/*">
                        <small class="text-muted">JPG, PNG, GIF, WebP — max 5MB</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Concern
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
