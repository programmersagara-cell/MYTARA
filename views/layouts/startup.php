<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Initializing') ?> — <?= APP_NAME ?></title>
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/startup.css">
    <style>
        /* Reset so the startup overlay owns the full viewport */
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: #0B1120;
            overflow: hidden;
        }
    </style>
</head>
<body class="startup-page">
    <?= $content ?? '' ?>
</body>
</html>