<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Login') ?> — <?= APP_NAME ?></title>
<?php if (file_exists(PUBLIC_PATH . '/assets/img/favicon.png')): ?>
    <link rel="icon" type="image/png" href="<?= IMG_URL ?>/favicon.png">
    <link rel="apple-touch-icon" href="<?= IMG_URL ?>/favicon.png">
<?php else: ?>
    <link rel="icon" type="image/svg+xml" href="<?= IMG_URL ?>/favicon.svg">
<?php endif; ?>
<?php if (file_exists(PUBLIC_PATH . '/assets/img/favicon.ico')): ?>
    <link rel="shortcut icon" type="image/x-icon" href="<?= IMG_URL ?>/favicon.ico">
<?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/auth.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/startup.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/app.css">
    <script>window.BASE_PATH = '<?= APP_BASE_PATH ?>';</script>
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Background decoration -->
        <div class="auth-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <!-- Auth Card -->
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
<img src="<?= IMG_URL ?>/devices/logo.png" class="logo-image" alt="Logo">
                    <!-- <i class="fas fa-network-wired"></i> -->
                </div>
                <h1><?= APP_NAME ?></h1>
                <p class="auth-subtitle">Asset Management & Service Management</p>
            </div>

            <div class="auth-body">
                <?php
                $session = \App\Core\Session::getInstance();
                if ($session->hasFlash('message')):
                    $msg = $session->getFlash('message');
                    $type = $session->getFlash('message_type') ?: 'info';
                ?>
                <div class="alert alert-<?= $type ?>">
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>

            <div class="auth-footer">
                <p>v<?= APP_VERSION ?> — &copy; <?= date('Y') ?> <?= APP_NAME ?></p>
            </div>
        </div>
    </div>
</body>
</html>
