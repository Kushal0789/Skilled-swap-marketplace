<?php
/**
 * User Profile & Skill Management (Presentation Layer)
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

// 1. Fetch target user profile (Updated to include social media columns)
$uStmt = $pdo->prepare("SELECT id, name, username, email, bio, location, profile_image, role, status, created_at, discord, facebook, contact_email FROM users WHERE id = :id LIMIT 1");
$uStmt->execute([':id' => $viewUserId]);
$targetUser = $uStmt->fetch();

if (!$targetUser) {
    die('User profile not found.');
}

$isOwnProfile = ($viewUserId === $currentUid);

// Fetch offered skills
$offStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'OFFER'
    ORDER BY s.name ASC
");
$offStmt->execute([':id' => $viewUserId]);
$offeredSkills = $offStmt->fetchAll();

// Fetch wanted skills
$wantStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'WANT'
    ORDER BY s.name ASC
");
$wantStmt->execute([':id' => $viewUserId]);
$wantedSkills = $wantStmt->fetchAll();

// Fetch reviews
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
    <!-- Profile Header Hero -->
    <div class="card profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-wrapper">
                <img src="<?= e(get_avatar_url($targetUser['profile_image'])) ?>" alt="<?= e($targetUser['name']) ?>" id="profile-avatar-preview" class="profile-avatar-large">
                <?php if ($isOwnProfile): ?>
                    <label for="avatar-file-input" class="avatar-edit-badge" title="Change profile picture">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </label>
                <?php endif; ?>
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

                <!-- 2. Render Social Media Badges/Links -->
                <?php if (!empty($targetUser['discord']) || !empty($targetUser['facebook']) || !empty($targetUser['contact_email'])): ?>
                    <div class="profile-social-links" style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; align-items: center;">
                        <?php if (!empty($targetUser['discord'])): ?>
                            <span class="btn btn-sm btn-outline" style="cursor: default;" title="Discord Username">
                                💬 <?= e($targetUser['discord']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($targetUser['facebook'])): ?>
                            <a href="<?= e($targetUser['facebook']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary">
                                🌐 Facebook
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($targetUser['contact_email'])): ?>
                            <a href="mailto:<?= e($targetUser['contact_email']) ?>" class="btn btn-sm btn-outline">
                                ✉️ <?= e($targetUser['contact_email']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$isOwnProfile && is_logged_in()): ?>
                <div class="profile-hero-actions">
                    <a href="discover.php" class="btn btn-primary btn-lg">Propose Skill Swap</a>
                    <a href="messages.php?user_id=<?= $targetUser['id'] ?>" class="btn btn-secondary btn-lg">Send Message</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isOwnProfile): ?>
        <!-- Hidden avatar input handled via profile.js / profile-info-form -->
        <form id="profile-info-form" style="margin-bottom: 2rem;">
            <input type="file" name="profile_image" id="avatar-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;">
            
            <div class="card" style="padding: 1.75rem;">
                <div class="card-header">
                    <h3 class="card-title">Edit Personal Details</h3>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="name" class="form-input" value="<?= e($targetUser['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" value="<?= e($targetUser['location'] ?? '') ?>" placeholder="e.g. Seattle, WA or Remote">
                    </div>
                </div>

                <!-- 3. Social Media Form Input Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Discord Username</label>
                        <input type="text" name="discord" class="form-input" value="<?= e($targetUser['discord'] ?? '') ?>" placeholder="e.g. username#1234">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Facebook Profile URL</label>
                        <input type="url" name="facebook" class="form-input" value="<?= e($targetUser['facebook'] ?? '') ?>" placeholder="https://facebook.com/yourprofile">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Public Contact Email</label>
                        <input type="email" name="contact_email" class="form-input" value="<?= e($targetUser['contact_email'] ?? '') ?>" placeholder="your.email@example.com">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Bio & Learning Philosophy</label>
                    <textarea name="bio" class="form-textarea" placeholder="Tell members about yourself, what you love building, and what you want to learn..."><?= e($targetUser['bio'] ?? '') ?></textarea>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <!-- Two-Column Skills Grid: Offered vs Wanted -->
    <div class="skills-management-grid">
        <!-- Offered Skills -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Skills I Can Offer</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Skills you are capable of teaching others</span>
                </div>
                <?php if ($isOwnProfile): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-offered-skill">+ Add Skill</button>
                <?php endif; ?>
            </div>

            <div class="skills-card-list">
                <?php if (empty($offeredSkills)): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No skills offered yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;" onclick="document.getElementById('btn-add-offered-skill').click()">+ Add First Skill</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($offeredSkills as $s): ?>
                        <div class="skill-badge-item">
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
                            <?php if ($isOwnProfile): ?>
                                <div class="skill-actions-right">
                                    <button type="button" class="btn-icon btn-edit-skill" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-proficiency="<?= e($s['proficiency']) ?>" data-description="<?= e($s['description'] ?? '') ?>" title="Edit details">
                                        ✎
                                    </button>
                                    <button type="button" class="btn-icon btn-delete-skill text-danger" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" title="Remove skill">
                                        &times;
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Wanted Skills -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Skills I Want to Learn</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Subjects and topics you want guidance in</span>
                </div>
                <?php if ($isOwnProfile): ?>
                    <button type="button" class="btn btn-outline btn-sm" id="btn-add-wanted-skill">+ Wishlist</button>
                <?php endif; ?>
            </div>

            <div class="skills-card-list">
                <?php if (empty($wantedSkills)): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No learning wishlist items yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;" onclick="document.getElementById('btn-add-wanted-skill').click()">+ Add Desired Skill</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($wantedSkills as $s): ?>
                        <div class="skill-badge-item">
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
                            <?php if ($isOwnProfile): ?>
                                <div class="skill-actions-right">
                                    <button type="button" class="btn-icon btn-edit-skill" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-proficiency="<?= e($s['proficiency']) ?>" data-description="<?= e($s['description'] ?? '') ?>" title="Edit details">
                                        ✎
                                    </button>
                                    <button type="button" class="btn-icon btn-delete-skill text-danger" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" title="Remove skill">
                                        &times;
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3 class="card-title">Member Reviews & Endorsements (<?= $totalReviews ?>)</h3>
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

.avatar-edit-badge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 34px;
    height: 34px;
    border-radius: var(--radius-full);
    background: var(--primary);
    color: #ffffff;
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition-fast);
}

.avatar-edit-badge:hover {
    transform: scale(1.1);
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

.skills-management-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.skills-card-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.skill-badge-item {
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 0.85rem 1.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.skill-actions-right {
    display: flex;
    gap: 0.35rem;
}

.skill-actions-right .btn-icon {
    width: 28px;
    height: 28px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    display: grid;
    place-items: center;
    cursor: pointer;
}

@media (max-width: 768px) {
    .profile-hero-card { padding: 1.5rem; }
    .skills-management-grid { grid-template-columns: 1fr; }
    .profile-hero-actions { width: 100%; flex-direction: row; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>