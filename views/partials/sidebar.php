<?php $layout = 'layouts/auth'; ?>
<div class="auth-form">
    <form method="POST" action="<?= url('/login') ?>" class="form" autocomplete="off" id="loginForm" novalidate>
        <!-- Security: CSRF token escaped with htmlspecialchars() -->
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Helpers\Security::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        
        <!-- Accessibility: Error messages via aria-live region -->
        <div class="form-errors" aria-live="polite" aria-atomic="true" role="alert"></div>
        
        <div class="form-group">
            <label for="login" class="form-label">
                <i class="fas fa-user" aria-hidden="true"></i> Username or Email
            </label>
            <!-- Security: semantic autocomplete attribute for username -->
            <input type="text" id="login" name="login" class="form-input" 
                   placeholder="Enter your username or email" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                <i class="fas fa-lock" aria-hidden="true"></i> Password
            </label>
            <div class="input-wrapper">
                <!-- Security: semantic autocomplete attribute for password -->
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="Enter your password" required autocomplete="current-password">
                <!-- Accessibility: keyboard-accessible toggle with aria-pressed and aria-label -->
                <button type="button" class="input-toggle" id="passwordToggleBtn" 
                        aria-pressed="false" aria-label="Show password" tabindex="0">
                    <i class="fas fa-eye" id="passwordToggle" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="form-group form-checkbox">
            <label for="remember">
                <input type="checkbox" id="remember" name="remember" value="1">
                <span>Remember me</span>
            </label>
            <!-- Note: Server handles secure HttpOnly cookie; do NOT use localStorage/sessionStorage -->
        </div>

        <!-- Security: prevent double-submit by disabling after submit -->
        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Sign In
        </button>
    </form>
</div>

<script>
(function() {
    'use strict';
    
    // Accessible password toggle
    const passwordToggleBtn = document.getElementById('passwordToggleBtn');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordToggle');
    
    if (passwordToggleBtn && passwordInput) {
        passwordToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            passwordIcon.classList.toggle('fa-eye', isPassword);
            passwordIcon.classList.toggle('fa-eye-slash', !isPassword);
            this.setAttribute('aria-pressed', !isPassword);
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            passwordInput.focus();
        });
        
        // Keyboard support: Enter/Space to toggle
        passwordToggleBtn.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                this.click();
            }
        });
    }
    
    // Security: prevent double-submit
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
        });
    }
})();
</script>

<li id="nav-manage-active">
	<a href="<?= url('/manage-active') ?>" class="nav-link">
		<i class="fas fa-tasks" aria-hidden="true"></i>
		<span class="nav-text">Manage Active</span>
		<!-- Badge: always visible, updates via JS -->
		<span id="activeCountBadge" class="nav-badge" aria-live="polite" aria-atomic="true">0</span>
	</a>
</li>

{ /* Add minimal CSS in the same file or in your global stylesheet per project convention */ }
<style>
/* ...existing styles... */
.nav-badge {
	display: inline-block;
	min-width: 2ch;
	padding: 0.15em 0.4em;
	margin-left: 0.5rem;
	border-radius: 999px;
	font-weight: 600;
	font-size: 0.85em;
	text-align: center;
	background: #e9ecef; /* adapt to app theme */
	color: #212529;
}
.nav-badge.has-count {
	background: #dc3545; /* notification color when >0, adapt to theme */
	color: #fff;
}
@media (max-width: 576px) {
	.nav-badge { font-size: 0.8em; padding: 0.12em 0.35em; }
}
</style>

<script>
/* Lightweight active-tickets updater */
(function() {
	'use strict';
	const badge = document.getElementById('activeCountBadge');
	if (!badge) return;
	const endpoint = '<?= url("/api/active_tickets.php") ?>'; // adjust path if your router differs
	const MAX_DISPLAY = 99;
	async function fetchCount() {
		try {
			const res = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' });
			if (!res.ok) return;
			const json = await res.json();
			if (typeof json.count !== 'number') return;
			const count = json.count;
			badge.textContent = count > MAX_DISPLAY ? `${MAX_DISPLAY}+` : String(count);
			badge.classList.toggle('has-count', count > 0);
		} catch (e) {
			// fail silently; preserve existing badge
			console.error('activeCount fetch failed', e);
		}
	}
	// Expose for other pages to call after ticket create/update/close
	window.refreshActiveCount = fetchCount;
	// initial load and polling
	fetchCount();
	const POLL_INTERVAL_MS = 30000; // 30s
	setInterval(fetchCount, POLL_INTERVAL_MS);
})();
</script>