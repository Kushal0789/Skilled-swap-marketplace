<?php
/**
 * Register Page (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Create Account - Skill Swap Marketplace';
$pageScript = 'auth.js';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-container">
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-header">
            <h1 class="auth-title">Join Skill Swap</h1>
            <p class="auth-desc">Connect with curious minds. Learn what you want by teaching what you know.</p>
        </div>

        <form id="register-form" novalidate>
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Maya Lin" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username <span class="req">*</span></label>
                <input type="text" name="username" class="form-input" placeholder="e.g. maya_lin (letters, numbers, _)" required>
                <span class="form-hint">Unique identifier for your swap profile.</span>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address <span class="req">*</span></label>
                <input type="email" name="email" class="form-input" placeholder="e.g. maya@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password <span class="req">*</span></label>
                <div class="password-field-wrapper">
                    <input type="password" name="password" class="form-input" placeholder="At least 8 characters, letters & numbers" required>
                    <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password <span class="req">*</span></label>
                <div class="password-field-wrapper">
                    <input type="password" name="confirm_password" class="form-input" placeholder="Re-enter your password" required>
                </div>
            </div>

            <div style="margin: 1.5rem 0 1rem;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Free Account</button>
            </div>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php" class="text-primary font-bold">Sign In &rarr;</a>
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
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2.5rem 2rem;
    box-shadow: var(--shadow-lg);
}
.auth-header {
    text-align: center;
    margin-bottom: 1.75rem;
}
.auth-title {
    font-size: 1.85rem;
    margin-bottom: 0.4rem;
}
.auth-desc {
    font-size: 0.92rem;
    color: var(--text-secondary);
}
.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border-color);
    font-size: 0.9rem;
    color: var(--text-secondary);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
