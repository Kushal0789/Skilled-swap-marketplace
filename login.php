<?php
/**
 * Login Page (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Sign In - Skill Swap Marketplace';
$pageScript = 'auth.js';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-desc">Sign in to manage your skill exchanges and messages.</p>
        </div>

        <form id="login-form" novalidate>
            <div class="form-group">
                <label class="form-label">Email or Username <span class="req">*</span></label>
                <input type="text" name="identity" class="form-input" placeholder="e.g. sarah@skillswap.com or sarah_c" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label">Password <span class="req">*</span></label>
                </div>
                <div class="password-field-wrapper">
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="margin: 1.5rem 0 1rem;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </div>
        </form>

        <!-- Quick Demo Credentials Picker for Viva / Quick Testing -->
        <div class="demo-accounts-box">
            <span class="demo-box-title">⚡ Quick Demo Logins (Viva / Testing):</span>
            <div class="demo-buttons-grid">
                <button type="button" class="btn-demo-fill" data-ident="sarah@skillswap.com" data-pass="Password@123">
                    <strong>Sarah Chen</strong> (Python / SQL)
                </button>
                <button type="button" class="btn-demo-fill" data-ident="alex@skillswap.com" data-pass="Password@123">
                    <strong>Alex Miller</strong> (Photoshop / UI)
                </button>
                <button type="button" class="btn-demo-fill" data-ident="admin@skillswap.com" data-pass="Admin@123">
                    <strong>Administrator</strong> (Admin Role)
                </button>
            </div>
        </div>

        <div class="auth-footer">
            Don't have an account yet? <a href="register.php" class="text-primary font-bold">Create Account &rarr;</a>
        </div>
    </div>
</div>

<style>
.auth-page-container {
    min-height: calc(100vh - 250px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
}

.auth-card {
    width: 100%;
    max-width: 440px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2.5rem 2rem;
    box-shadow: var(--shadow-lg);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-title {
    font-size: 1.85rem;
    margin-bottom: 0.4rem;
}

.auth-desc {
    font-size: 0.92rem;
    color: var(--text-secondary);
}

.demo-accounts-box {
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--bg-surface-elevated);
    border: 1px dashed var(--border-color);
    border-radius: var(--radius-md);
}

.demo-box-title {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    display: block;
    margin-bottom: 0.5rem;
}

.demo-buttons-grid {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.btn-demo-fill {
    text-align: left;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    padding: 6px 10px;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    cursor: pointer;
    color: var(--text-primary);
    transition: all var(--transition-fast);
}

.btn-demo-fill:hover {
    border-color: var(--primary);
    background: var(--primary-glow);
}

.auth-footer {
    text-align: center;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border-color);
    font-size: 0.9rem;
    color: var(--text-secondary);
}
</style>

<script>
document.querySelectorAll('.btn-demo-fill').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = document.getElementById('login-form');
        form.querySelector('[name="identity"]').value = btn.getAttribute('data-ident');
        form.querySelector('[name="password"]').value = btn.getAttribute('data-pass');
        window.showToast('Demo credentials filled! Click Sign In.', 'info');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
