<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (file_exists(PUBLIC_PATH . '/assets/img/favicon.png')): ?>
    <link rel="icon" type="image/png" href="<?= IMG_URL ?>/favicon.png">
    <link rel="apple-touch-icon" href="<?= IMG_URL ?>/favicon.png">
<?php else: ?>
    <link rel="icon" type="image/svg+xml" href="<?= IMG_URL ?>/favicon.svg">
<?php endif; ?>
<?php if (file_exists(PUBLIC_PATH . '/assets/img/favicon.ico')): ?>
    <link rel="shortcut icon" type="image/x-icon" href="<?= IMG_URL ?>/favicon.ico">
<?php endif; ?>
    <title><?= htmlspecialchars($title ?? 'Error') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; background: #0F172A; color: #E2E8F0;
        }
        .error-page { text-align: center; padding: 2rem; }
        .error-code { font-size: 8rem; font-weight: 800; background: linear-gradient(135deg, #3B82F6, #8B5CF6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .error-title { font-size: 1.5rem; color: #94A3B8; margin: 1rem 0; }
        .error-message { color: #64748B; max-width: 400px; margin: 0 auto 2rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #3B82F6; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background 0.2s; }
        .btn:hover { background: #2563EB; }
        .error-icon { font-size: 2rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-icon">
            <?php if ($code === 404): ?>
                <i class="fas fa-search" style="color: #F59E0B;"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i>
            <?php endif; ?>
        </div>
        <div class="error-code"><?= htmlspecialchars($code ?? 500) ?></div>
        <h1 class="error-title">
            <?= $code === 404 ? 'Page Not Found' : 'Something went wrong' ?>
        </h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'An unexpected error occurred.') ?></p>
        <a href="<?= url('/dashboard') ?>" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
