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
