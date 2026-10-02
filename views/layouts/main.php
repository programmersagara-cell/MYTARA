<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <meta name="csrf-token" content="<?= \App\Helpers\Security::generateCsrfToken() ?>">
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/app.css">
    <?php if (isset($extraStyles)): foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?= CSS_URL ?>/<?= $style ?>">
    <?php endforeach; endif; ?>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?= url('/dashboard') ?>" class="sidebar-logo">
<img src="<?= IMG_URL ?>/devices/logo.png" class="logo-image" alt="Logo">
                    <span class="logo-text">ITSaAMS</span>
                </a>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

<?php
// Precompute active concern count for the current user (non-admins)
$activeConcernCount = 0;
$latestActiveTicketNumber = '';
$manageActiveCount = 0;
if (isset($user) && $user && !empty($user['id'])) {
    $userConcerns = \App\Models\Concern::activeForUser((int) $user['id']);
    if (!empty($userConcerns)) {
        $latestActiveTicketNumber = $userConcerns[0]['ticket_number'] ?? '';
    }
    if (isset($user['role']) && $user['role'] !== 'admin') {
        $activeConcernCount = count($userConcerns);
    }
    if (isset($user['role']) && $user['role'] === 'admin') {
        $manageActiveCount = \App\Models\Concern::countByStatus('active');
    }
}
?>

<nav class="sidebar-nav">
                <?php if (isset($user) && $user && in_array($user['role'], ['admin', 'viewer'])): ?>
                <div class="nav-section">
                    <span class="nav-section-title">Main</span>
                    <a href="<?= url('/dashboard') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= url('/topology') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/topology') ? 'active' : '' ?>">
                        <i class="fas fa-project-diagram"></i>
                        <span>Network Diagram</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">Management</span>
                    <a href="<?= url('/assets') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/assets') && !str_contains($_SERVER['REQUEST_URI'], '/assets/retired') ? 'active' : '' ?>">
                        <i class="fas fa-desktop"></i>
                        <span>Asset Ledger</span>
                    </a>
                    <a href="<?= url('/assets/retired') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/assets/retired') ? 'active' : '' ?>">
                        <i class="fas fa-archive"></i>
                        <span>Retired Assets</span>
                    </a>
                    <a href="<?= url('/licenses') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/licenses') ? 'active' : '' ?>">
                        <i class="fas fa-key"></i>
                        <span>Software Licenses</span>
                    </a>
                    <a href="<?= url('/disposals') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/disposals') ? 'active' : '' ?>">
                        <i class="fas fa-recycle"></i>
                        <span>Disposals</span>
                    </a>
                    <a href="<?= url('/departments') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/departments') ? 'active' : '' ?>">
                        <i class="fas fa-building"></i>
                        <span>Departments</span>
                    </a>
                </div>
                <?php endif; ?>

                <div class="nav-section">
                    <span class="nav-section-title">Communication</span>
                    <a href="<?= url('/messages') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/messages') ? 'active' : '' ?>">
                        <i class="fas fa-comment-dots"></i>
                        <span>Messages</span>
                        <?php if (isset($user) && $user && $user['id']): ?>
                            <span class="nav-badge" id="messagesBadge" style="display:none;" aria-live="polite">0</span>
                        <?php endif; ?>
                    </a>
                </div>

                <?php if (isset($user) && $user && in_array($user['role'], ['admin', 'user'])): ?>
                <div class="nav-section">
                    <span class="nav-section-title">Tickets</span>
                    <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>
                    <a href="<?= url('/tickets') ?>" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], '/tickets') && !str_contains($_SERVER['REQUEST_URI'], '/manage') && !str_contains($_SERVER['REQUEST_URI'], '/history') && !str_contains($_SERVER['REQUEST_URI'], '/analytics') && !str_contains($_SERVER['REQUEST_URI'], '/instructions') && !str_contains($_SERVER['REQUEST_URI'], '/backup')) ? 'active' : '' ?>">
                        <i class="fas fa-ticket-alt"></i>
                        <span>My Concerns</span>
                        <?php if ($activeConcernCount > 0): ?>
                            <span class="nav-badge"><?= $activeConcernCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= url('/tickets/my-history') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/my-history') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>My Ticket History</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= url('/tickets/manage') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/manage') ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Manage Active</span>
                        <span id="activeCountBadge" class="nav-badge" aria-live="polite" aria-atomic="true"><?= (int)$manageActiveCount ?></span>
                    </a>
                    <a href="<?= url('/tickets/history') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/history') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>History & Remarks</span>
                    </a>
                    <a href="<?= url('/tickets/analytics') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/analytics') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="<?= url('/tickets/instructions') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/instructions') ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Instructions</span>
                    </a>
                    <a href="<?= url('/tickets/backup') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/tickets/backup') ? 'active' : '' ?>">
                        <i class="fas fa-database"></i>
                        <span>Backups</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

<div class="nav-section">
                    <span class="nav-section-title">System</span>
                    <?php if (isset($user) && $user && in_array($user['role'], ['admin', 'viewer'])): ?>
                    <a href="<?= url('/history') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/history') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>Audit Log</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($user) && $user && $user['role'] === 'admin'): ?>
                    <a href="<?= url('/users') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/users') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i>
                        <span>Users</span>
                    </a>
                    <a href="<?= url('/settings') ?>" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/settings') ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-avatar">
                        <?php if (isset($user) && $user && $user['avatar']): ?>
                            <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars(isset($user) && $user ? ($user['full_name'] ?? 'User') : 'User') ?></span>
                        <span class="user-role <?= isset($user) && $user ? ($user['role'] ?? '') : '' ?>"><?= isset($user) && $user ? ucfirst($user['role'] ?? '') : '' ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search assets, users, departments..." autocomplete="off">
                    </div>
                </div>
                <div class="header-right">
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="<?= url('/profile') ?>" class="header-profile" title="Profile">
                        <?php if (isset($user) && $user && $user['avatar']): ?>
                            <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile" class="header-avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </a>
                    <a href="<?= url('/logout') ?>" class="btn btn-sm btn-danger" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?php
                // Display flash messages
                $session = \App\Core\Session::getInstance();
                if ($session->hasFlash('message')):
                    $msg = $session->getFlash('message');
                    $type = $session->getFlash('message_type') ?: 'info';
                ?>
                <div class="alert alert-<?= $type ?> alert-dismissible">
                    <span><?= $msg ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

<script>window.BASE_PATH = '<?= APP_BASE_PATH ?>'; window.UPLOADS_URL = '<?= UPLOADS_URL ?>';</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= JS_URL ?>/theme.js"></script>
    <script src="<?= JS_URL ?>/app.js"></script>

    <!-- Dashboard entrance animation (triggered after startup sequence redirect) -->
    <script>
    (function() {
        'use strict';
        // Check if we arrived here from the startup sequence
        var referrer = document.referrer || '';
        var fromStartup = referrer.indexOf('/startup') !== -1 ||
            (window.location.search.indexOf('from_startup=1') !== -1);

        if (fromStartup) {
            var appContainer = document.querySelector('.app-container');
            var mainContent = document.querySelector('.main-content');
            if (appContainer) appContainer.classList.add('dashboard-entering');
            if (mainContent) mainContent.classList.add('dashboard-content-entering');
        }
    })();
    </script>

    <!-- Active Tickets Badge Updater -->
    <style>
        .nav-badge {
            display: inline-block;
            min-width: 2ch;
            padding: 0.2em 0.5em;
            margin-left: 0.5rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.75em;
            text-align: center;
            background: #dc3545;
            color: #fff;
            vertical-align: middle;
        }
        .nav-badge.empty {
            background: #e9ecef;
            color: #6c757d;
        }
    </style>
    <script>
    (function() {
        'use strict';
        const badge = document.getElementById('activeCountBadge');
        if (!badge) {
            console.log('activeCountBadge element not found');
            return;
        }
        console.log('Badge script initialized', badge);
        const MAX_DISPLAY = 99;
        
        async function fetchCount() {
            try {
                const apiUrl = (window.BASE_PATH || '') + '/public/api/active_tickets.php';
                console.log('Fetching active count from:', apiUrl);
                const res = await fetch(apiUrl, {
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                console.log('API response status:', res.status);
                if (!res.ok) {
                    console.warn('activeCount API error:', res.status, res.statusText);
                    return;
                }
                const json = await res.json();
                console.log('API response data:', json);
                if (typeof json.count !== 'number') {
                    console.warn('activeCount API returned invalid data:', json);
                    return;
                }
                const count = json.count;
                console.log('Active count:', count);
                badge.textContent = count > MAX_DISPLAY ? MAX_DISPLAY + '+' : String(count);
                badge.classList.toggle('empty', count === 0);
                // Always show badge but with different styling based on count
                badge.style.display = 'inline-block';
                console.log('Badge updated - display:', badge.style.display, 'text:', badge.textContent);
            } catch (e) {
                console.error('activeCount fetch failed:', e.message, e);
            }
        }
        
        window.refreshActiveCount = fetchCount;
        
        // Initial fetch on page load
        console.log('Initial fetch on page load');
        fetchCount();
        
        // Polling every 30 seconds
        const POLL_INTERVAL_MS = 30000;
        setInterval(fetchCount, POLL_INTERVAL_MS);
        console.log('Polling enabled every', POLL_INTERVAL_MS, 'ms');
    })();
    </script>

    <!-- Unread Messages Badge Updater (global) -->
    <script>
    (function() {
        'use strict';
        // Exactly one unread poller per page: messenger.js checks this flag
        // before starting its own interval on the Messages page.
        if (window.__msgUnreadPollStarted) {
            return;
        }
        window.__msgUnreadPollStarted = true;

        const badge = document.getElementById('messagesBadge');
        if (!badge) {
            return;
        }

        const MAX_DISPLAY = 99;
        const POLL_INTERVAL_MS = 10000;
        let lastCount = null; // baseline until the first successful fetch
        let inFlight = false;

        function showToast() {
            const container = document.querySelector('.page-content') || document.body;
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible';
            toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;animation:slideIn 0.3s ease;';
            toast.innerHTML = '<span>You have a new message.</span>'
                + '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
        }

        function updateBadge(count) {
            if (count > 0) {
                badge.textContent = count > MAX_DISPLAY ? MAX_DISPLAY + '+' : String(count);
                badge.classList.remove('empty');
                badge.style.display = 'inline-flex';
            } else {
                badge.textContent = '0';
                badge.classList.add('empty');
                badge.style.display = 'none';
            }
        }

        async function fetchUnread() {
            if (inFlight) return;
            inFlight = true;
            try {
                const res = await fetch((window.BASE_PATH || '') + '/messages/unread', {
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const json = await res.json();
                if (typeof json.unread !== 'number') return;

                const count = json.unread;
                const increased = lastCount !== null && count > lastCount;
                updateBadge(count);

                if (increased) {
                    // Keep the per-conversation badges fresh on the Messages page
                    if (window.Messenger && typeof window.Messenger.refreshConversations === 'function') {
                        window.Messenger.refreshConversations();
                    }
                    // Small toast when new messages arrive while on another page
                    if (window.location.pathname.indexOf('/messages') === -1) {
                        showToast();
                    }
                }
                lastCount = count;
            } catch (e) {
                // Fail silently (same as the ticket badge updater)
            } finally {
                inFlight = false;
            }
        }

        // Allows pages (e.g. messenger.js after marking a conversation read)
        // to refresh the badge immediately instead of waiting for the next poll.
        window.refreshMessagesUnreadCount = fetchUnread;

        // Initial fetch on page load
        fetchUnread();

        // Polling every 10 seconds
        setInterval(fetchUnread, POLL_INTERVAL_MS);

        // Refresh fast when the tab regains focus / becomes visible again
        window.addEventListener('focus', fetchUnread);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) fetchUnread();
        });
    })();
    </script>
<?php if (isset($extraScripts)): foreach ($extraScripts as $script): ?>
        <script src="<?= JS_URL ?>/<?= $script ?>"></script>
    <?php endforeach; endif; ?>

</body>
</html>
