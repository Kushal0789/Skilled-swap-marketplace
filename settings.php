<?php
/**
 * User Settings (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

require_auth();
$user = current_user();
$pdo = get_db();
$successMsg = '';
$errorMsg = '';

// Password Change Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Security token invalid. Please refresh the page.';
    } else {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        // Query current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($currentPass, $currentHash)) {
            $errorMsg = 'Current password is incorrect.';
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $passErrors = validate_password($newPass);
            if (!empty($passErrors)) {
                $errorMsg = $passErrors[0];
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $upStmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                $upStmt->execute([':p' => $newHash, ':id' => $user['id']]);
                $successMsg = 'Password updated successfully!';
            }
        }
    }
}

$pageTitle = 'Settings - Skill Swap Marketplace';
include __DIR__ . '/includes/header.php';
?>

<div class="settings-container">
    <div class="card" style="max-width: 600px; margin: 0 auto; padding: 2.5rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.5rem;">Account Settings</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Manage your security and platform preferences.</p>

        <?php if (!empty($successMsg)): ?>
            <div style="background: var(--success-bg); color: var(--success); padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                <?= e($successMsg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                <?= e($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="change_password">

            <h3 style="font-size: 1.15rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Change Password
            </h3>

            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-input" placeholder="At least 8 chars with letters & numbers" required>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input" required>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>

        <div style="margin-top: 3rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Account Overview</h3>
            <p style="font-size: 0.88rem; color: var(--text-secondary);">
                Email: <strong><?= e($user['email']) ?></strong><br>
                Role: <span class="badge-pill" style="background: var(--bg-surface-elevated);"><?= e($user['role']) ?></span><br>
                Account status: <span class="badge-pill bg-success">Active</span>
            </p>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1280px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
