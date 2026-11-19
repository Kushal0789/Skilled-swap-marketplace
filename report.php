<?php
/**
 * Report & Feedback Page (Presentation Layer)
 * Skill Swap Marketplace
 *
 * Allows authenticated members to report issues, suggest improvements,
 * report community rule violations, or submit general feedback.
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$currentUserId = current_user_id();
$currentUser = current_user();

$prefilledUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$prefilledType = isset($_GET['type']) ? trim($_GET['type']) : '';

// Retrieve active peers for the reported user selector (excluding current user)
$activeUsers = [];
try {
    $pdo = get_db();
    $uStmt = $pdo->prepare("
        SELECT id, name, username
        FROM users
        WHERE status = 'active' AND id != :my_id
        ORDER BY name ASC
    ");
    $uStmt->execute([':my_id' => $currentUserId]);
    $activeUsers = $uStmt->fetchAll();
} catch (Exception $e) {
    error_log('[Report Page DB Error] ' . $e->getMessage());
}

$pageTitle = 'Report & Feedback · SkillSwap';
$pageDesc = 'Submit platform bug reports, community flags, feature suggestions, or feedback to the SkillSwap team.';
$pageScript = 'report.js';

include __DIR__ . '/includes/header.php';
?>

<!-- Dedicated Report & Feedback Stylesheet -->
<link rel="stylesheet" href="assets/css/report.css?v=<?= filemtime(__DIR__ . '/assets/css/report.css') ?>">

<div class="report-page-container">

    <!-- Page Header -->
    <div class="report-header">
        <span class="report-header-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Trust, Safety &amp; Quality
        </span>
        <h1 class="report-title">Report &amp; Feedback</h1>
        <p class="report-subtitle">
            Help us maintain a safe, high-quality skill-sharing community. Report bugs, suggest improvements, or alert moderators to rule violations.
        </p>
    </div>

    <!-- Main Grid: Submission Form + Sidebar Context -->
    <div class="report-grid">

        <!-- Form Card (Primary Column) -->
        <div class="report-card">
            <h2 class="report-card-title">
                <span>Submit Your Report or Feedback</span>
            </h2>
            <p class="report-card-desc">
                Submissions are reviewed directly by platform administrators. All reports are handled discreetly and securely.
            </p>

            <form id="report-feedback-form" novalidate>

                <!-- 1. Category Selection -->
                <div class="form-group">
                    <label class="form-label" for="report-category-select">
                        Category <span class="req">*</span>
                    </label>
                    <div class="category-select-wrapper">
                        <span class="category-icon-preview" id="category-icon-preview">🐞</span>
                        <select id="report-category-select" name="category" class="form-select has-icon" required>
                            <option value="Bug Report" selected>Bug Report</option>
                            <option value="User Report">User Report</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Suggestion">Suggestion</option>
                            <option value="General Feedback">General Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <span class="form-hint">Choose the category that best describes your inquiry.</span>
                </div>

                <!-- 2. Reported User (Relevant primarily for User Report) -->
                <div class="reported-user-group" id="reported-user-group">
                    <div class="reported-user-header">
                        <div class="reported-user-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Reported Member</span>
                        </div>
                        <span class="reported-user-badge" id="reported-user-badge">Optional</span>
                    </div>
                    <p class="reported-user-help">
                        Only relevant if you are reporting a member for harassment, no-show, fraudulent claims, or inappropriate behavior.
                    </p>
                    <select id="reported-user-select" name="reported_user_id" class="form-select">
                        <option value="">-- Select Member (Optional) --</option>
                        <?php foreach ($activeUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= ($prefilledUserId === (int)$u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Subject / Title -->
                <div class="form-group">
                    <label class="form-label" for="report-subject-input">
                        Subject / Title <span class="req">*</span>
                    </label>
                    <input
                        type="text"
                        id="report-subject-input"
                        name="title"
                        class="form-input"
                        placeholder="e.g. Chat scroll issue on mobile, Profile link broken, Great match idea…"
                        maxlength="150"
                        required
                    >
                    <div class="char-count-row">
                        <span class="form-hint">Summarize your feedback in a concise title.</span>
                        <span id="subject-char-count">0 / 150</span>
                    </div>
                </div>

                <!-- 4. Detailed Description -->
                <div class="form-group">
                    <label class="form-label" for="report-desc-textarea">
                        Detailed Description <span class="req">*</span>
                    </label>
                    <textarea
                        id="report-desc-textarea"
                        name="description"
                        class="form-textarea"
                        rows="6"
                        placeholder="Please provide specifics. If reporting a bug, list the steps to reproduce it. If reporting a member, describe what happened…"
                        required
                    ></textarea>
                    <div class="char-count-row">
                        <span class="form-hint">Minimum 10 characters. Please be as descriptive as possible.</span>
                        <span id="desc-char-count">0 characters</span>
                    </div>
                </div>

                <!-- 5. Submit Row -->
                <div class="report-submit-row">
                    <div class="report-privacy-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <span>Encrypted &amp; private report directly to moderators.</span>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn-submit-report">
                        <span>Submit Report &amp; Feedback</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: Guidelines & My Submissions -->
        <aside class="report-sidebar">

            <!-- Guidelines Card -->
            <div class="report-side-card">
                <h3 class="report-side-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Community Guidelines
                </h3>
                <ul class="report-guidelines-list">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><strong>Respect &amp; Civility:</strong> SkillSwap fosters reciprocal learning and mutual peer respect.</span>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><strong>Bug Verification:</strong> For technical glitches, include your browser or operating system if possible.</span>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><strong>Prompt Action:</strong> Critical user reports and technical issues are typically addressed within 24–48 hours.</span>
                    </li>
                </ul>
            </div>

            <!-- Past Submissions History Card -->
            <div class="report-side-card">
                <h3 class="report-side-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="12 8 12 12 14 14"></polyline><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"></path></svg>
                    My Submissions History
                </h3>
                <div class="my-reports-list" id="my-submissions-list">
                    <div style="text-align:center;padding:1.5rem 0.5rem;color:var(--text-muted);font-size:0.85rem;">
                        <span class="spinner spinner-primary"></span> Loading history…
                    </div>
                </div>
            </div>

        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
