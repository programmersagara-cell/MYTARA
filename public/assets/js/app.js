/**
 * IT Asset Management System - Core Application Script
 */

(function() {
    'use strict';

    // ─── DOM Ready ───
    document.addEventListener('DOMContentLoaded', function() {
        initSidebar();
        initGlobalSearch();
        initThemeToggle();
        initAlertDismiss();
        initConfirmDialogs();
        initAvatarUpload();
    });

    // ─── Avatar Upload Preview ───
    function initAvatarUpload() {
        const fileInputs = document.querySelectorAll('input[type="file"][name="avatar"]');

        fileInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const preview = document.getElementById('avatarPreview');
                if (!preview) return;

                const file = this.files && this.files[0];
                if (!file) return;

                // Validate it's an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file.');
                    this.value = '';
                    return;
                }

                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image exceeds maximum size of 5MB.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar preview">';
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ─── Sidebar ───
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileToggle = document.getElementById('mobileToggle');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024 && sidebar.classList.contains('mobile-open')) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Restore sidebar state
        const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (collapsed && window.innerWidth > 1024) {
            sidebar.classList.add('collapsed');
        }
    }

    // ─── Global Search ───
    function initGlobalSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;

        let searchTimeout;

        // Also search on Enter key press
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length >= 2) {
                    // Redirect to asset search
                    window.location.href = (window.BASE_PATH || '') + '/assets?search=' + encodeURIComponent(query);
                }
            }
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            searchTimeout = setTimeout(function() {
                if (query.length >= 8) {
                    // Redirect to asset search
                    window.location.href = (window.BASE_PATH || '') + '/assets?search=' + encodeURIComponent(query);
                }
            }, 2500);
        });
    }

    // ─── Theme Toggle ───
    function initThemeToggle() {
        const toggle = document.getElementById('themeToggle');
        if (!toggle) return;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);

        toggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.innerHTML = theme === 'dark' 
                ? '<i class="fas fa-sun"></i>' 
                : '<i class="fas fa-moon"></i>';
        }
    }

    // ─── Alert Dismiss ───
    function initAlertDismiss() {
        document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    alert.style.animation = 'slideDown 0.3s ease reverse';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                });
            }

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                if (alert.parentElement) {
                    alert.style.animation = 'slideDown 0.3s ease reverse';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }
            }, 5000);
        });
    }

    // ─── Confirm Dialogs ───
    function initConfirmDialogs() {
        document.querySelectorAll('[data-confirm]').forEach(function(element) {
            element.addEventListener('click', function(e) {
                const message = this.getAttribute('data-confirm') || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    // ─── Utility: Show Modal ───
    window.showModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    // ─── Utility: Hide Modal ───
    window.hideModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // ─── Utility: Fetch API helper ───
    window.apiFetch = function(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
        };

        const config = { ...defaults, ...options };
        config.headers = { ...defaults.headers, ...options.headers };

        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                // Parse JSON and optionally trigger active-count refresh for ticket-affecting calls
                return response.json().then(function(data) {
                    try {
                        const method = (config.method || 'GET').toUpperCase();
                        const path = String(url || '');
                        const ticketAffecting = /\/tickets(\/|$)/.test(path);
                        if (ticketAffecting && ['POST','PUT','PATCH','DELETE'].includes(method)) {
                            if (typeof window.refreshActiveCount === 'function') {
                                // Fire-and-forget; don't await
                                try { window.refreshActiveCount(); } catch (e) {}
                            }
                        }
                    } catch (e) {
                        // ignore errors here to avoid breaking callers
                    }
                    return data;
                });
            });
    };

    // ─── Utility: Get CSRF Token ───
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // ─── Utility: Format Date ───
    window.formatDate = function(dateStr) {
        if (!dateStr) return '—';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    // ─── Utility: Format DateTime ───
    window.formatDateTime = function(dateStr) {
        if (!dateStr) return '—';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

})();
