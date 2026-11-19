<?php
/**
 * User Profile (Presentation Layer - Read-Only Default View)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$currentUid = current_user_id();
$viewUserId = !empty($_GET['id']) ? (int)$_GET['id'] : $currentUid;

if (!$viewUserId) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// 1. Fetch target user profile
$uStmt = $pdo->prepare("
    SELECT id, name, username, email, bio, location, profile_image, role, status, created_at,
           discord, facebook, contact_email, github, linkedin, twitter, instagram, whatsapp, website
    FROM users WHERE id = :id LIMIT 1
");
$uStmt->execute([':id' => $viewUserId]);
$targetUser = $uStmt->fetch();

if (!$targetUser) {
    die('User profile not found.');
}

$isOwnProfile = ($viewUserId === $currentUid);
$allPlatforms = get_social_platforms_meta();
$activeSocialLinks = get_user_social_links($targetUser);

// 2. Fetch skills for public profile view (both offered and wanted)
$offStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'OFFER'
    ORDER BY s.name ASC
");
$offStmt->execute([':id' => $viewUserId]);
$offeredSkills = $offStmt->fetchAll();

$wantStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'WANT'
    ORDER BY s.name ASC
");
$wantStmt->execute([':id' => $viewUserId]);
$wantedSkills = $wantStmt->fetchAll();

// 3. Fetch reviews
$revStmt = $pdo->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at,
           u.id as reviewer_id, u.name as reviewer_name, u.profile_image as reviewer_avatar
    FROM reviews r
    INNER JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewed_user_id = :id
    ORDER BY r.created_at DESC
");
$revStmt->execute([':id' => $viewUserId]);
$reviews = $revStmt->fetchAll();

$totalReviews = count($reviews);
$avgRating = $totalReviews > 0 ? round(array_sum(array_column($reviews, 'rating')) / $totalReviews, 1) : 0;

$pageTitle = e($targetUser['name']) . ' - Profile - Skill Swap';
$pageScript = 'profile.js';
include __DIR__ . '/includes/header.php';
?>

<div class="profile-container">
    <!-- Profile Header Hero Card (Read-Only) -->
    <div class="card profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-wrapper">
                <img src="<?= e(get_avatar_url($targetUser['profile_image'])) ?>" alt="<?= e($targetUser['name']) ?>" id="profile-avatar-preview" class="profile-avatar-large">
            </div>

            <div class="profile-hero-details">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <h1 class="profile-name"><?= e($targetUser['name']) ?></h1>
                    <span class="profile-username">@<?= e($targetUser['username']) ?></span>
                </div>

                <div class="profile-meta-row">
                    <span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?= e($targetUser['location'] ?: 'Location not specified') ?>
                    </span>
                    <span>&bull;</span>
                    <span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Member since <?= date('F Y', strtotime($targetUser['created_at'])) ?>
                    </span>
                    <span>&bull;</span>
                    <span>
                        <span style="color: #f59e0b; font-weight: 700;">★ <?= $avgRating ?></span>
                        <span style="color: var(--text-muted);"> (<?= $totalReviews ?> <?= $totalReviews === 1 ? 'review' : 'reviews' ?>)</span>
                    </span>
                </div>

                <p class="profile-bio-text">
                    <?= !empty($targetUser['bio']) ? nl2br(e($targetUser['bio'])) : '<span style="color: var(--text-muted); font-style: italic;">No bio written yet.</span>' ?>
                </p>

                <!-- Render Social Media Badges / Direct Chat Links -->
                <div class="profile-social-links" id="profile-social-links-bar" data-user-id="<?= $targetUser['id'] ?>">
                    <?php if (!empty($activeSocialLinks)): ?>
                        <?php foreach ($activeSocialLinks as $link): ?>
                            <?php if ($link['is_copy']): ?>
                                <button type="button" class="social-link-btn social-link-copy" data-copy="<?= e($link['copy_text']) ?>" title="Discord handle: <?= e($link['copy_text']) ?> (Click to copy)">
                                    <span class="social-icon" style="color: <?= $link['color'] ?>;"><?= $link['icon'] ?></span>
                                    <span class="social-name"><?= e($link['name']) ?></span>
                                    <span class="social-val-preview"><?= e($link['copy_text']) ?></span>
                                    <span class="social-copy-badge">Copy</span>
                                </button>
                            <?php else: ?>
                                <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="social-link-btn <?= $link['direct_chat'] ? 'social-link-chat' : '' ?>" title="<?= e($link['name']) ?>: <?= e($link['value']) ?>">
                                    <span class="social-icon" style="color: <?= $link['color'] ?>;"><?= $link['icon'] ?></span>
                                    <span class="social-name"><?= e($link['name']) ?></span>
                                    <?php if ($link['direct_chat']): ?>
                                        <span class="social-chat-badge" title="Direct Online Chat">
                                            <span class="pulse-dot"></span> Chat
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php elseif ($isOwnProfile): ?>
                        <div class="empty-social-callout" id="empty-social-callout">
                            <span class="empty-social-text">💬 Connect your social accounts &amp; direct chat channels so peers can message you.</span>
                            <a href="profile-edit.php?tab=social" class="btn btn-sm btn-outline" id="btn-add-social-shortcut">+ Add Links</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Hero Action: Edit Profile & Skills button for owner, Propose Swap for visitors -->
            <div class="profile-hero-actions">
                <?php if ($isOwnProfile): ?>
                    <a href="profile-edit.php" class="btn btn-outline" id="btn-toggle-edit-mode">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        Edit Profile &amp; Skills
                    </a>
                <?php else: ?>
                    <a href="<?= is_logged_in() ? 'discover.php' : 'login.php' ?>" class="btn btn-primary btn-lg">Propose Skill Swap</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Two-Column Skills Grid: Read-Only View of Offered vs Wanted -->
    <div class="skills-management-grid">
        <!-- Offered Skills -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Skills <?= $isOwnProfile ? 'I Can Offer' : e($targetUser['name']) . ' Can Offer' ?></h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Skills capable of teaching others</span>
                </div>
            </div>

            <div class="skills-card-list" id="offered-skills-list">
                <?php if (empty($offeredSkills)): ?>
                    <div class="empty-state" style="padding: 2.25rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No skills offered yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <a href="profile-edit.php?tab=skills" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;">+ Add Offered Skills</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($offeredSkills as $s): ?>
                        <div class="skill-badge-item" id="user-skill-<?= $s['id'] ?>">
                            <div class="skill-info-left">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <strong style="font-size: 1rem;"><?= e($s['name']) ?></strong>
                                    <span class="proficiency-pill proficiency-<?= e($s['proficiency']) ?>"><?= e($s['proficiency']) ?></span>
                                </div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 2px;">
                                    Category: <?= e($s['category']) ?>
                                    <?= !empty($s['description']) ? ' &bull; ' . e($s['description']) : '' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Wanted Skills -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Skills <?= $isOwnProfile ? 'I Want to Learn' : e($targetUser['name']) . ' Wants to Learn' ?></h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Subjects and topics sought for guidance</span>
                </div>
            </div>

            <div class="skills-card-list" id="wanted-skills-list">
                <?php if (empty($wantedSkills)): ?>
                    <div class="empty-state" style="padding: 2.25rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No learning wishlist items yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <a href="profile-edit.php?tab=skills" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;">+ Add Desired Skills</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($wantedSkills as $s): ?>
                        <div class="skill-badge-item" id="user-skill-<?= $s['id'] ?>">
                            <div class="skill-info-left">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <strong style="font-size: 1rem; color: var(--primary);"><?= e($s['name']) ?></strong>
                                    <span class="proficiency-pill proficiency-<?= e($s['proficiency']) ?>">Target: <?= e($s['proficiency']) ?></span>
                                </div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 2px;">
                                    Category: <?= e($s['category']) ?>
                                    <?= !empty($s['description']) ? ' &bull; ' . e($s['description']) : '' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3 class="card-title">Member Reviews &amp; Endorsements (<?= $totalReviews ?>)</h3>
            <?php if ($avgRating > 0): ?>
                <div style="font-weight: 700; color: #f59e0b;">
                    ★ <?= $avgRating ?> / 5.0
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <div class="empty-state-icon">⭐</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.4rem;">No reviews yet</h4>
                <p style="font-size: 0.88rem; color: var(--text-secondary);">After completing a skill exchange, swap partners can leave a rating and review here.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="background: var(--bg-surface-elevated); padding: 1.25rem; border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?= e(get_avatar_url($rev['reviewer_avatar'])) ?>" alt="<?= e($rev['reviewer_name']) ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <a href="profile.php?id=<?= $rev['reviewer_id'] ?>" style="font-weight: 600; font-size: 0.95rem;"><?= e($rev['reviewer_name']) ?></a>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); display: block;"><?= time_ago($rev['created_at']) ?></span>
                                </div>
                            </div>
                            <div style="color: #f59e0b; font-size: 1.1rem;">
                                <?= str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5 - (int)$rev['rating']) ?>
                            </div>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5;">
                            <?= nl2br(e($rev['comment'])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}

.profile-hero-card {
    padding: 2.5rem;
    margin-bottom: 2rem;
    background: var(--gradient-card);
}

.profile-hero-content {
    display: flex;
    align-items: flex-start;
    gap: 2rem;
    flex-wrap: wrap;
}

.profile-avatar-wrapper {
    position: relative;
    width: 110px;
    height: 110px;
}

.profile-avatar-large {
    width: 110px;
    height: 110px;
    border-radius: var(--radius-full);
    object-fit: cover;
    border: 3px solid var(--primary);
    box-shadow: 0 4px 20px var(--primary-glow);
}

.profile-hero-details {
    flex: 1;
    min-width: 280px;
}

.profile-name {
    font-size: 2rem;
    margin-bottom: 2px;
}

.profile-username {
    font-size: 1rem;
    color: var(--text-muted);
}

.profile-meta-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.88rem;
    color: var(--text-secondary);
    margin: 0.6rem 0 1rem;
    flex-wrap: wrap;
}

.profile-meta-row span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.profile-bio-text {
    font-size: 0.95rem;
    color: var(--text-secondary);
    line-height: 1.6;
    max-width: 720px;
}

.profile-hero-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.profile-social-links {
    display: flex;
    gap: 0.6rem;
    margin-top: 1.15rem;
    flex-wrap: wrap;
    align-items: center;
}

.social-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.42rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.84rem;
    font-weight: 500;
    text-decoration: none;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    transition: all var(--transition-fast);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    cursor: pointer;
    line-height: 1.2;
}

.social-link-btn:hover {
    transform: translateY(-2px);
    border-color: var(--primary);
    box-shadow: 0 4px 14px var(--primary-glow);
    color: var(--text-primary);
}

.social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.social-val-preview {
    font-size: 0.76rem;
    color: var(--text-muted);
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.social-copy-badge {
    font-size: 0.68rem;
    padding: 0.12rem 0.4rem;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 9999px;
    color: var(--text-muted);
    font-weight: 600;
}

.social-copy-badge.badge-copied-active {
    background: #10b981;
    color: #ffffff;
}

.social-link-chat {
    border-color: rgba(16, 185, 129, 0.35);
}

.social-chat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: #10b981;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-left: 0.2rem;
}

.pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px #10b981;
    animation: pulseGlow 1.8s infinite;
}

@keyframes pulseGlow {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.3); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.8; }
}

.empty-social-callout {
    display: inline-flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.6rem 1rem;
    background: var(--bg-surface);
    border: 1px dashed var(--border-color);
    border-radius: var(--radius-md);
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

.empty-social-text {
    font-size: 0.84rem;
    color: var(--text-secondary);
}

.skills-management-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.skills-card-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1.25rem;
}

.skill-badge-item {
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 0.85rem 1.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.25s ease;
}

.proficiency-pill {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.proficiency-Beginner { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.proficiency-Intermediate { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.proficiency-Advanced { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.proficiency-Expert { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

@media (max-width: 768px) {
    .profile-hero-card { padding: 1.5rem; }
    .skills-management-grid { grid-template-columns: 1fr; }
    .profile-hero-actions { width: 100%; flex-direction: row; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
