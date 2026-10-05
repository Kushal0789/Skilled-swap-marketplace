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

// Contextual dynamic greeting based on hour of day
$currentHour = (int)date('G');
if ($currentHour < 12) {
    $timeGreeting = 'Good morning';
    $greetingEmoji = '☀️';
} elseif ($currentHour < 18) {
    $timeGreeting = 'Good afternoon';
    $greetingEmoji = '🌤️';
} else {
    $timeGreeting = 'Good evening';
    $greetingEmoji = '🌙';
}

$pageTitle = 'Dashboard - Skill Swap Marketplace';
$pageScript = 'dashboard.js';
include __DIR__ . '/includes/header.php';
?>

<div class="dash-wrapper">
    <!-- Welcome Header Strip -->
    <div class="dash-welcome-card">
        <div class="dash-welcome-left">
            <div style="position: relative;">
                <img src="<?= e(get_avatar_url($user['profile_image'])) ?>" alt="<?= e($user['name']) ?>" class="dash-user-avatar">
                <span class="avatar-online-dot" title="Online now"></span>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <h1 class="dash-greeting"><?= $timeGreeting ?>, <?= e(explode(' ', $user['name'])[0]) ?>! <?= $greetingEmoji ?></h1>
                    <?php if ($completion == 100): ?>
                        <span class="status-badge accepted" style="font-size: 0.72rem;">All-Star Profile</span>
                    <?php else: ?>
                        <span class="status-badge pending" style="font-size: 0.72rem;">Level 1 Member</span>
                    <?php endif; ?>
                </div>
                <p class="dash-subtext">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <?= e($user['location'] ? $user['location'] : "Location not set") ?> &bull; 
                    <a href="profile.php" style="color: var(--primary); text-decoration: underline; font-weight: 500;">Manage Profile & Skills</a>
                </p>
            </div>
        </div>

        <!-- Profile Completeness Bar -->
        <div class="profile-meter-box">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 6px;">
                <span style="font-weight: 600;">Profile Strength</span>
                <strong style="color: var(--primary); font-size: 0.95rem;"><?= $completion ?>%</strong>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width: <?= $completion ?>%;"></div>
            </div>
            <?php if ($completion < 100): ?>
                <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px; display: block;">
                    💡 Tip: Add bio & skills to boost reciprocal match score.
                </span>
            <?php else: ?>
                <span style="font-size: 0.78rem; color: var(--success); margin-top: 6px; display: block; font-weight: 600;">
                    ✓ Profile is fully optimized for matchmaking!
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="dash-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--success-bg); color: var(--success); box-shadow: 0 0 16px var(--success-border);">🎓</div>
            <div class="metric-content">
                <span class="metric-number"><?= count($myOffered) ?></span>
                <span class="metric-label">Skills You Offer</span>
            </div>
            <a href="profile.php" class="metric-corner-link">+ Add</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--primary-glow); color: var(--primary); box-shadow: 0 0 16px var(--primary-glow);">🎯</div>
            <div class="metric-content">
                <span class="metric-number"><?= count($myWanted) ?></span>
                <span class="metric-label">Skills You Want</span>
            </div>
            <a href="profile.php" class="metric-corner-link">+ Add</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: var(--warning-bg); color: var(--warning); box-shadow: 0 0 16px var(--warning-border);">📥</div>
            <div class="metric-content">
                <span class="metric-number"><?= $pendingReqCount ?></span>
                <span class="metric-label">Pending Requests</span>
            </div>
            <a href="requests.php" class="metric-corner-link">View</a>
        </div>

        <div class="metric-card">
            <div class="metric-icon-box" style="background: rgba(59, 130, 246, 0.16); color: #3b82f6; box-shadow: 0 0 16px var(--info-border);">🤝</div>
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
    margin: 2.5rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.dash-welcome-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2rem 2.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.dash-welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: var(--gradient-brand);
}

.dash-welcome-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.dash-user-avatar {
    width: 68px;
    height: 68px;
    border-radius: var(--radius-full);
    object-fit: cover;
    border: 2.5px solid var(--primary);
    box-shadow: 0 4px 16px var(--primary-glow);
}

.avatar-online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background: var(--success);
    border: 2px solid var(--bg-surface);
    border-radius: 50%;
    box-shadow: 0 0 8px var(--success);
}

.dash-greeting {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 3px;
    letter-spacing: -0.02em;
}

.dash-subtext {
    font-size: 0.92rem;
    color: var(--text-secondary);
}

.profile-meter-box {
    width: 280px;
    background: var(--bg-surface-elevated);
    padding: 1rem 1.25rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
}

.progress-bar-track {
    width: 100%;
    height: 9px;
    background: var(--bg-surface);
    border-radius: var(--radius-full);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.progress-bar-fill {
    height: 100%;
    background: var(--gradient-brand);
    border-radius: var(--radius-full);
    transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 0 10px var(--primary-glow);
}

.dash-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1.5rem;
}

.metric-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 1.5rem 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.35rem;
    position: relative;
    transition: all var(--transition-normal);
}

.metric-card:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md), 0 0 20px var(--primary-glow);
}

.metric-icon-box {
    width: 54px;
    height: 54px;
    border-radius: var(--radius-lg);
    display: grid;
    place-items: center;
    font-size: 1.6rem;
    transition: transform var(--transition-fast);
}

.metric-card:hover .metric-icon-box {
    transform: scale(1.08);
}

.metric-number {
    display: block;
    font-size: 2rem;
    font-family: var(--font-heading);
    font-weight: 800;
    line-height: 1.1;
    color: var(--text-primary);
}

.metric-label {
    font-size: 0.84rem;
    color: var(--text-muted);
    font-weight: 600;
}

.metric-corner-link {
    position: absolute;
    top: 14px;
    right: 16px;
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--primary);
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    transition: all var(--transition-fast);
}

.metric-corner-link:hover {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
}

.quick-actions-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    overflow-x: auto;
    box-shadow: var(--shadow-xs);
}

.quick-actions-title {
    font-size: 0.84rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    white-space: nowrap;
}

.quick-actions-pills {
    display: flex;
    gap: 0.85rem;
    white-space: nowrap;
}

.quick-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 8px 18px;
    border-radius: var(--radius-full);
    font-size: 0.88rem;
    font-weight: 600;
    transition: all var(--transition-fast);
}

.quick-pill:hover {
    border-color: var(--primary);
    background: var(--bg-surface);
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.quick-pill.primary {
    background: var(--gradient-brand);
    color: #ffffff;
    border: none;
    box-shadow: 0 2px 10px var(--primary-glow);
}

.quick-pill.primary:hover {
    color: #ffffff;
    box-shadow: 0 6px 18px var(--primary-glow);
    filter: brightness(1.08);
}

.dash-two-column {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 2rem;
}

@media (max-width: 900px) {
    .dash-welcome-card { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
    .profile-meter-box { width: 100%; }
    .dash-two-column { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
