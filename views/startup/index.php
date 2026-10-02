<?php $layout = 'layouts/startup'; ?>

<div class="startup-overlay" id="startupOverlay">
    <!-- Background -->
    <div class="startup-bg">
        <div class="startup-grid"></div>
        <div class="startup-particles" id="startupParticles"></div>
    </div>

    <!-- Content -->
    <div class="startup-content">
        <!-- Logo / Branding -->
        <div class="startup-logo-wrapper">
            <div class="startup-logo">
                <img src="<?= IMG_URL ?>/devices/logo.png" class="logo-image" alt="ITSaAMS">
            </div>
            <div class="startup-title-group">
                <h1 class="startup-brand">ITSaAMS</h1>
                <p class="startup-subtitle">IT Service and Asset Management System</p>
            </div>
        </div>

        <!-- System Status -->
        <div class="startup-status-section">
            <div class="startup-status-line">
                <span class="status-indicator"></span>
                <span class="status-text" id="statusText">Initializing ITSaAMS...</span>
            </div>
            <div class="startup-progress-container">
                <div class="startup-progress-bar" id="progressBar">
                    <div class="startup-progress-fill" id="progressFill"></div>
                </div>
                <span class="startup-progress-label" id="progressLabel">0%</span>
            </div>
            <div class="startup-module-list" id="moduleList"></div>
        </div>

        <!-- System Ready State -->
        <div class="startup-ready-state" id="readyState" style="display: none;">
            <div class="ready-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="ready-text-group">
                <span class="ready-label">SYSTEM READY</span>
                <p class="ready-message">All systems operational</p>
            </div>
        </div>
    </div>

    <!-- Version Footer -->
    <div class="startup-footer">
        <span>v<?= APP_VERSION ?></span>
        <span class="startup-footer-divider">|</span>
        <span>&copy; <?= date('Y') ?> ITSaAMS</span>
    </div>

<script>
(function() {
    'use strict';

    var TOTAL_DURATION = 2000;
    var REDIRECT_TARGET = '<?= addslashes(url($redirect_target ?? '/dashboard')) ?>';
    var STATUS_MESSAGES = [
        { text: 'Authenticating user...', module: 'auth' },
        { text: 'Loading IT services...', module: 'services' },
        { text: 'Loading asset management module...', module: 'assets' },
        { text: 'Preparing workspace...', module: 'workspace' },
        { text: 'Synchronizing system modules...', module: 'sync' }
    ];
    var MODULE_NAMES = {
        auth: 'Authentication Service',
        services: 'IT Service Module',
        assets: 'Asset Management Module',
        workspace: 'Workspace Environment',
        sync: 'System Synchronization'
    };

    var overlay = document.getElementById('startupOverlay');
    var statusText = document.getElementById('statusText');
    var progressFill = document.getElementById('progressFill');
    var progressLabel = document.getElementById('progressLabel');
    var moduleList = document.getElementById('moduleList');
    var readyState = document.getElementById('readyState');
    var particlesEl = document.getElementById('startupParticles');

    function createParticles() {
        if (!particlesEl) return;
        var count = 15;
        for (var i = 0; i < count; i++) {
            var dot = document.createElement('span');
            dot.className = 'startup-particle';
            var size = 2 + Math.random() * 3;
            dot.style.width = size + 'px';
            dot.style.height = size + 'px';
            dot.style.left = (5 + Math.random() * 90) + '%';
            dot.style.top = (5 + Math.random() * 90) + '%';
            dot.style.animationDelay = (Math.random() * 4) + 's';
            dot.style.animationDuration = (6 + Math.random() * 8) + 's';
            dot.style.opacity = 0.15 + Math.random() * 0.25;
            particlesEl.appendChild(dot);
        }
    }

    function updateProgress(percent) {
        var clamped = Math.min(100, Math.max(0, percent));
        if (progressFill) progressFill.style.width = clamped + '%';
        if (progressLabel) progressLabel.textContent = Math.round(clamped) + '%';
    }

    function showStatus(text) {
        if (!statusText) return;
        statusText.style.opacity = '0';
        statusText.style.transform = 'translateY(4px)';
        setTimeout(function() {
            statusText.textContent = text;
            statusText.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            statusText.style.opacity = '1';
            statusText.style.transform = 'translateY(0)';
        }, 80);
    }

    function markModuleLoaded(moduleKey) {
        if (!moduleList) return;
        var moduleName = MODULE_NAMES[moduleKey] || moduleKey;
        var item = document.createElement('div');
        item.className = 'startup-module-item';
        var dot = document.createElement('span');
        dot.className = 'module-status-dot';
        dot.style.background = '#10B981';
        dot.style.boxShadow = '0 0 6px rgba(16, 185, 129, 0.4)';
        var nameSpan = document.createElement('span');
        nameSpan.className = 'module-name';
        nameSpan.textContent = moduleName;
        var label = document.createElement('span');
        label.className = 'module-status-label show';
        label.textContent = 'loaded';
        item.appendChild(dot);
        item.appendChild(nameSpan);
        item.appendChild(label);
        moduleList.appendChild(item);
        item.style.opacity = '0';
        item.style.transform = 'translateX(-8px)';
        requestAnimationFrame(function() {
            item.style.transition = 'opacity 0.35s ease 0.1s, transform 0.35s ease 0.1s';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        });
    }

    function showReadyState() {
        if (!readyState) return;
        readyState.style.display = 'flex';
        readyState.style.opacity = '0';
        readyState.style.transform = 'scale(0.92)';
        requestAnimationFrame(function() {
            readyState.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
            readyState.style.opacity = '1';
            readyState.style.transform = 'scale(1)';
        });
        if (statusText) {
            statusText.style.transition = 'opacity 0.3s ease';
            statusText.style.opacity = '0';
        }
        if (progressFill) {
            progressFill.style.transition = 'width 0.5s ease';
            progressFill.style.width = '100%';
        }
        if (progressLabel) {
            progressLabel.style.opacity = '0';
            progressLabel.style.transition = 'opacity 0.3s ease 0.3s';
        }
    }

    function transitionToDashboard() {
        if (!overlay) return;
        overlay.style.transition = 'opacity 0.4s ease';
        overlay.style.opacity = '0';
        setTimeout(function() {
            // Append a flag so the dashboard knows to run its entrance animation
            var separator = REDIRECT_TARGET.indexOf('?') !== -1 ? '&' : '?';
            window.location.href = REDIRECT_TARGET + separator + 'from_startup=1';
        }, 450);
    }

    function runStartupSequence() {
        createParticles();
        updateProgress(8);
        showStatus(STATUS_MESSAGES[0].text);
        markModuleLoaded('auth');

        setTimeout(function() {
            updateProgress(30);
            showStatus(STATUS_MESSAGES[1].text);
            markModuleLoaded('services');
        }, 350);

        setTimeout(function() {
            updateProgress(52);
            showStatus(STATUS_MESSAGES[2].text);
            markModuleLoaded('assets');
        }, 650);

        setTimeout(function() {
            updateProgress(74);
            showStatus(STATUS_MESSAGES[3].text);
            markModuleLoaded('workspace');
        }, 1000);

        setTimeout(function() {
            updateProgress(90);
            showStatus(STATUS_MESSAGES[4].text);
            markModuleLoaded('sync');
        }, 1350);

        setTimeout(function() {
            updateProgress(100);
            showReadyState();
        }, 1700);

        setTimeout(transitionToDashboard, TOTAL_DURATION + 200);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runStartupSequence);
    } else {
        runStartupSequence();
    }
})();
</script>
</div>