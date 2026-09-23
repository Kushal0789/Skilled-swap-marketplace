<?php
/**
 * User Dashboard Page (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();
$user = current_user();
$pdo = get_db();
$userId = (int)$user['id'];

// Fetch quick counts for dashboard
$offeredSkillsStmt = $pdo->prepare("
    SELECT us.id, us.proficiency, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :uid AND us.skill_type = 'OFFER'
");
$offeredSkillsStmt->execute([':uid' => $userId]);
$myOffered = $offeredSkillsStmt->fetchAll();

$wantedSkillsStmt = $pdo->prepare("
    SELECT us.id, us.proficiency, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :uid AND us.skill_type = 'WANT'
");
$wantedSkillsStmt->execute([':uid' => $userId]);
$myWanted = $wantedSkillsStmt->fetchAll();

// Pending swap requests count
$pendingReqCount = (int)$pdo->prepare("SELECT COUNT(*) FROM swap_requests WHERE receiver_id = :uid AND status = 'pending'")->execute([':uid' => $userId]) ? 
    $pdo->query("SELECT COUNT(*) FROM swap_requests WHERE receiver_id = {$userId} AND status = 'pending'")->fetchColumn() : 0;

// Active swaps count
$activeSwapsCount = (int)$pdo->query("SELECT COUNT(*) FROM swap_requests WHERE (sender_id = {$userId} OR receiver_id = {$userId}) AND status = 'accepted'")->fetchColumn();

// Unread messages
$unreadMsgs = get_unread_messages_count($pdo, $userId);

// Calculate profile completion percentage
$completion = 40; // Base: registered
if (!empty($user['bio'])) $completion += 20;
if (!empty($user['location'])) $completion += 10;
if (count($myOffered) > 0) $completion += 15;
if (count($myWanted) > 0) $completion += 15;
$completion = min(100, $completion);

$pageTitle = 'Dashboard - Skill Swap Marketplace';
$pageScript = 'dashboard.js';
include __DIR__ . '/includes/header.php';
?>

<div class="dash-wrapper">
    <!-- Welcome Header Strip -->
    <div class="dash-welcome-card">
        <div class="dash-welcome-left">
            <img src="<?= e(get_avatar_url($user['profile_image'])) ?>" alt="<?= e($user['name']) ?>" class="dash-user-avatar">
            <div>
                <h1 class="dash-greeting">Welcome back, <?= e($user['name']) ?>!</h1>
                <p class="dash-subtext">
                    <?= e($user['location'] ? "Location: {$user['location']}" : "Location not set") ?> &bull; 
                    <a href="profile.php" style="color: var(--primary); text-decoration: underline;">Edit Profile</a>
                </p>
            </div>
        </div>

        <!-- Profile Completeness Bar -->
        <div class="profile-meter-box">
            <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
                <span>Profile Completeness</span>
                <strong><?= $completion ?>%</strong>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width: <?= $completion ?>%;"></div>
            </div>
            <?php if ($completion < 100): ?>
                <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;">
                    Tip: Complete your bio and skills to get higher matching rank.
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="dash-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--success-bg); color: var(--success);">🎓</div>
            <div class="metric-content">
                <span class="metric-number"><?= count($myOffered) ?></span>
                <span class="metric-label">Skills You Offer</span>
            </div>
            <a href="profile.php" class="metric-corner-link">+ Add</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--primary-glow); color: var(--primary);">🎯</div>
            <div class="metric-content">
                <span class="metric-number"><?= count($myWanted) ?></span>
                <span class="metric-label">Skills You Want</span>
            </div>
            <a href="profile.php" class="metric-corner-link">+ Add</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--warning-bg); color: var(--warning);">📥</div>
            <div class="metric-content">
                <span class="metric-number"><?= $pendingReqCount ?></span>
                <span class="metric-label">Pending Requests</span>
            </div>
            <a href="requests.php" class="metric-corner-link">View</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">🤝</div>
            <div class="metric-content">
                <span class="metric-number"><?= $activeSwapsCount ?></span>
                <span class="metric-label">Active Swaps</span>
            </div>
            <a href="messages.php" class="metric-corner-link">Chat</a>
        </div>
    </div>

    <!-- Quick Action Launch Bar -->
    <div class="quick-actions-bar">
        <span class="quick-actions-title">⚡ Quick Actions:</span>
        <div class="quick-actions-pills">
            <a href="matches.php" class="quick-pill primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Find AI Matches
            </a>
            <a href="discover.php" class="quick-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                Explore Skills Catalog
            </a>
            <a href="profile.php" class="quick-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add / Manage Skills
            </a>
            <a href="requests.php" class="quick-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                View Proposals
            </a>
        </div>
    </div>

    <!-- Dual Column Main Dashboard Content -->
    <div class="dash-two-column">
        <!-- Left: Matchmaker Highlights -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Reciprocal Matches</h3>
                <a href="matches.php" class="btn-link">View All Matches &rarr;</a>
            </div>
            <div id="dash-top-matches">
                <div style="text-align: center; padding: 2rem;">
                    <span class="spinner spinner-primary"></span>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">Finding your matches...</p>
                </div>
            </div>
        </div>

        <!-- Right: Incoming Swap Requests & Skill Snapshot -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Pending Requests -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Incoming Swap Proposals</h3>
                    <a href="requests.php" class="btn-link">All (<?= $pendingReqCount ?>)</a>
                </div>
                <div id="dash-pending-requests">
                    <div style="text-align: center; padding: 1.5rem;">
                        <span class="spinner spinner-primary"></span>
                    </div>
                </div>
            </div>

            <!-- Current Skills Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Skills Snapshot</h3>
                    <a href="profile.php" class="btn-link">Manage</a>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div class="skills-sec-label">Skills You Teach:</div>
                    <div class="skill-badge-group">
                        <?php if (empty($myOffered)): ?>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">No skills added. <a href="profile.php" class="text-primary font-bold">+ Add one now</a></span>
                        <?php else: ?>
                            <?php foreach ($myOffered as $s): ?>
                                <span class="skill-badge offer">
                                    <?= e($s['name']) ?>
                                    <span class="proficiency-pill proficiency-<?= e($s['proficiency']) ?>"><?= e($s['proficiency']) ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="skills-sec-label">Skills You Want:</div>
                    <div class="skill-badge-group">
                        <?php if (empty($myWanted)): ?>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">No learning wishlist. <a href="profile.php" class="text-primary font-bold">+ Add skills</a></span>
                        <?php else: ?>
                            <?php foreach ($myWanted as $s): ?>
                                <span class="skill-badge want"><?= e($s['name']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dash-wrapper {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
}

.dash-welcome-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
}

.dash-welcome-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.dash-user-avatar {
    width: 64px;
    height: 64px;
    border-radius: var(--radius-full);
    object-fit: cover;
    border: 2px solid var(--primary);
}

.dash-greeting {
    font-size: 1.6rem;
    margin-bottom: 2px;
}

.dash-subtext {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.profile-meter-box {
    width: 260px;
}

.progress-bar-track {
    width: 100%;
    height: 8px;
    background: var(--bg-surface-elevated);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: var(--gradient-brand);
    border-radius: var(--radius-full);
    transition: width 0.6s ease;
}

.dash-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
}

.metric-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    position: relative;
    transition: all var(--transition-normal);
}

.metric-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.metric-icon-box {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-md);
    display: grid;
    place-items: center;
    font-size: 1.5rem;
}

.metric-number {
    display: block;
    font-size: 1.75rem;
    font-family: var(--font-heading);
    font-weight: 800;
    line-height: 1.1;
}

.metric-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.metric-corner-link {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary);
}

.quick-actions-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 0.85rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    overflow-x: auto;
}

.quick-actions-title {
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    white-space: nowrap;
}

.quick-actions-pills {
    display: flex;
    gap: 0.75rem;
    white-space: nowrap;
}

.quick-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 6px 14px;
    border-radius: var(--radius-full);
    font-size: 0.85rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.quick-pill:hover {
    border-color: var(--primary);
    background: var(--bg-surface);
    color: var(--primary);
}

.quick-pill.primary {
    background: var(--gradient-brand);
    color: #ffffff;
    border: none;
}

.dash-two-column {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 900px) {
    .dash-welcome-card { flex-direction: column; align-items: flex-start; gap: 1.25rem; }
    .profile-meter-box { width: 100%; }
    .dash-two-column { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
