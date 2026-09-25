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

// 1. Fetch target user profile (including all social media and direct chat channels)
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

$offeredSkills = [];
$wantedSkills = [];

// Only query skills if viewing own profile (skills are completely removed for visitors)
if ($isOwnProfile) {
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
}

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
                    <!-- Camera icon button -->
                    <button type="button" id="avatarMenuBtn" class="avatar-edit-badge" title="Change profile picture" aria-haspopup="true" aria-expanded="false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="avatarDropdown" class="avatar-dropdown" role="menu" aria-label="Profile photo options">
                        <button type="button" id="uploadTriggerBtn" class="avatar-dropdown-item" role="menuitem">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload New Photo
                        </button>
                        <button type="button" id="removeAvatarBtn" class="avatar-dropdown-item avatar-dropdown-danger" role="menuitem"
                            <?php if (empty($targetUser['profile_image']) || $targetUser['profile_image'] === 'default-avatar.svg'): ?>style="display:none;"<?php endif; ?>>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Remove Photo
                        </button>
                    </div>
                    <!-- Hidden file input for upload -->
                    <input type="file" id="avatarUploadInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
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
                            <span class="empty-social-text">💬 Connect your social accounts & direct chat channels so peers can message you.</span>
                            <button type="button" class="btn btn-sm btn-outline" id="btn-add-social-shortcut">+ Add Links</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-hero-actions">
                <?php if ($isOwnProfile): ?>
                    <button type="button" class="btn btn-outline" id="btn-toggle-edit-mode">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        Edit Profile & Skills
                    </button>
                <?php else: ?>
                    <a href="<?= is_logged_in() ? 'discover.php' : 'login.php' ?>" class="btn btn-primary btn-lg">Propose Skill Swap</a>
                    <a href="<?= is_logged_in() ? 'messages.php?user_id=' . (int)$targetUser['id'] : 'login.php' ?>" class="btn btn-secondary btn-lg">Send Message</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isOwnProfile): ?>
        <!-- Owner Profile, Social Links & Skills Management Panel -->
        <section class="card profile-manager-card" id="profile-manager-section" style="margin-bottom: 2rem;">
            <div class="card-header manager-header">
                <div>
                    <h2 class="card-title manager-title">
                        <span>⚙️</span> Manage Profile, Socials & Skills
                    </h2>
                    <p class="manager-subtitle">
                        Add or remove direct chat channels and manage your skills in real time.
                    </p>
                </div>

                <!-- Navigation Tabs -->
                <div class="manager-tab-nav" role="tablist">
                    <button type="button" class="manager-tab-btn active" data-tab="tab-social">
                        🌐 Social & Chat Links
                    </button>
                    <button type="button" class="manager-tab-btn" data-tab="tab-details">
                        👤 Personal Details
                    </button>
                    <button type="button" class="manager-tab-btn" data-tab="tab-skills">
                        ⚡ Quick Skill Manager
                    </button>
                </div>
            </div>

            <!-- Profile & Socials Form -->
            <form id="profile-info-form">
                <!-- avatar-file-input retained as alias for legacy references (avatarUploadInput is used above) -->

                <!-- TAB 1: SOCIAL & CHAT LINKS -->
                <div class="manager-tab-pane active" id="tab-social">
                    <div class="tab-pane-intro">
                        <div>
                            <h3 style="font-size: 1.08rem; margin-bottom: 0.2rem;">Direct Online Chat & Social Channels</h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted);">
                                Connect channels where other users can chat with you directly online. Empty channels are hidden from your public profile.
                            </p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-save-social">
                            💾 Save Social Links
                        </button>
                    </div>

                    <!-- Channel Quick-Jump Chips -->
                    <div class="social-quick-chips">
                        <span class="chips-label">Quick Jump:</span>
                        <?php foreach ($allPlatforms as $k => $p): 
                            $hasVal = !empty(trim($targetUser[$k] ?? ''));
                        ?>
                            <button type="button" class="social-chip-jump <?= $hasVal ? 'is-active' : '' ?>" data-channel="<?= $k ?>">
                                <span class="chip-dot <?= $hasVal ? 'dot-active' : '' ?>"></span>
                                <?= e($p['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Social Channels Input Grid -->
                    <div class="social-inputs-grid">
                        <?php foreach ($allPlatforms as $k => $p): 
                            $curVal = $targetUser[$k] ?? '';
                            $hasVal = !empty(trim($curVal));
                        ?>
                            <div class="social-input-row" id="channel-row-<?= $k ?>">
                                <div class="social-row-left">
                                    <span class="social-input-icon" style="color: <?= $p['color'] ?>;">
                                        <?= $p['icon'] ?>
                                    </span>
                                    <div>
                                        <div class="social-input-name">
                                            <?= e($p['name']) ?>
                                            <?php if (!empty($p['badge'])): ?>
                                                <span class="badge-mini"><?= e($p['badge']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="social-input-hint"><?= e($p['hint']) ?></div>
                                    </div>
                                </div>

                                <div class="social-row-center">
                                    <input type="text" 
                                           name="<?= $k ?>" 
                                           id="social-input-<?= $k ?>"
                                           class="form-input social-channel-input" 
                                           value="<?= e($curVal) ?>" 
                                           placeholder="<?= e($p['placeholder']) ?>"
                                           autocomplete="off">
                                </div>

                                <div class="social-row-right">
                                    <button type="button" 
                                            class="btn btn-ghost btn-sm btn-remove-social" 
                                            data-channel="<?= $k ?>" 
                                            title="Clear and remove this link"
                                            style="<?= !$hasVal ? 'display: none;' : '' ?>">
                                        &times; Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Live Public Profile Card Preview -->
                    <div class="social-preview-box">
                        <div class="preview-box-header">
                            <span class="preview-box-title">Live Public Profile Card Preview:</span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">Real-time reflection of your active links</span>
                        </div>
                        <div class="profile-social-links" id="live-social-preview-bar">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
                        <button type="submit" class="btn btn-primary">Save Social & Chat Links</button>
                    </div>
                </div>

                <!-- TAB 2: PERSONAL INFO & BIO -->
                <div class="manager-tab-pane" id="tab-details" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Display Name *</label>
                            <input type="text" name="name" class="form-input" value="<?= e($targetUser['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-input" value="<?= e($targetUser['location'] ?? '') ?>" placeholder="e.g. Seattle, WA or Remote">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio & Learning Philosophy</label>
                        <textarea name="bio" class="form-textarea" rows="4" placeholder="Tell members about yourself, what you love building, and what you want to learn..."><?= e($targetUser['bio'] ?? '') ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Save Personal Info</button>
                    </div>
                </div>
            </form>

            <!-- TAB 3: QUICK DYNAMIC SKILL MANAGER -->
            <div class="manager-tab-pane" id="tab-skills" style="display: none;">
                <div class="quick-skill-adder-card">
                    <h3 style="font-size: 1.05rem; margin-bottom: 0.3rem;">Add a Skill Dynamically</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Add skills you can offer to teach or wishlist items you want to learn. Applied instantly without page reload.
                    </p>

                    <form id="quick-add-skill-form">
                        <div class="quick-skill-form-grid">
                            <div class="form-group">
                                <label class="form-label">Skill Type</label>
                                <div class="skill-type-toggle">
                                    <label class="type-pill">
                                        <input type="radio" name="quick_skill_type" value="OFFER" checked>
                                        <span>🎁 I Can Offer (Teach)</span>
                                    </label>
                                    <label class="type-pill">
                                        <input type="radio" name="quick_skill_type" value="WANT">
                                        <span>🎯 I Want to Learn</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Skill Name</label>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <select name="quick_skill_id" id="quick-skill-select" class="form-select">
                                        <option value="">-- Choose from Catalog --</option>
                                    </select>
                                    <input type="text" name="quick_new_skill_name" id="quick-new-skill-input" class="form-input" placeholder="Or type a new custom skill...">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Proficiency Level</label>
                                <select name="quick_proficiency" class="form-select" required>
                                    <option value="Beginner">Beginner - Basic understanding</option>
                                    <option value="Intermediate" selected>Intermediate - Practical experience</option>
                                    <option value="Advanced">Advanced - In-depth expertise</option>
                                    <option value="Expert">Expert - Professional / Master</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="quick_category" id="quick-category-select" class="form-select">
                                    <option value="General">General</option>
                                    <option value="Programming">Programming</option>
                                    <option value="Design">Design</option>
                                    <option value="Language">Language</option>
                                    <option value="Music">Music</option>
                                    <option value="Business">Business</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description / Topics Covered (Optional)</label>
                            <input type="text" name="quick_description" class="form-input" placeholder="e.g. Can mentor on fullstack web development, API design, and testing...">
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" id="btn-submit-quick-skill">
                                + Add Skill Dynamically
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Current Skills Dynamic Summary List -->
                <div style="margin-top: 1.5rem;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--text-secondary);">Your Skills Overview (Click &times; to delete dynamically):</h4>
                    <div class="dynamic-skills-summary-container" id="dynamic-skills-summary">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($isOwnProfile): ?>
    <!-- Two-Column Skills Grid: Offered vs Wanted (Owner Only) -->
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

            <div class="skills-card-list" id="offered-skills-list">
                <?php if (empty($offeredSkills)): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No skills offered yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;" onclick="document.getElementById('btn-add-offered-skill').click()">+ Add First Skill</button>
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

            <div class="skills-card-list" id="wanted-skills-list">
                <?php if (empty($wantedSkills)): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted);">No learning wishlist items yet.</p>
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;" onclick="document.getElementById('btn-add-wanted-skill').click()">+ Add Desired Skill</button>
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
    <?php endif; ?>

    <!-- Reviews Section -->
    <div class="card" style="margin-top: <?= $isOwnProfile ? '2rem' : '0' ?>;">
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
    border: none;
    padding: 0;
    outline-offset: 2px;
}

.avatar-edit-badge:hover {
    transform: scale(1.1);
}

/* Avatar dropdown menu */
.avatar-dropdown {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    min-width: 180px;
    background: var(--bg-surface, #1e2130);
    border: 1px solid var(--border-color, rgba(255,255,255,0.1));
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    z-index: 200;
    overflow: hidden;
    animation: avatarDropdownIn 0.15s ease;
}

@keyframes avatarDropdownIn {
    from { opacity: 0; transform: translateX(-50%) translateY(6px); }
    to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}

.avatar-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
    padding: 0.65rem 1rem;
    background: none;
    border: none;
    color: var(--text-primary, #e2e8f0);
    font-size: 0.88rem;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s;
    white-space: nowrap;
}

.avatar-dropdown-item:hover {
    background: var(--bg-hover, rgba(255,255,255,0.07));
}

.avatar-dropdown-item + .avatar-dropdown-item {
    border-top: 1px solid var(--border-color, rgba(255,255,255,0.08));
}

.avatar-dropdown-danger {
    color: var(--danger, #f87171);
}

.avatar-dropdown-danger:hover {
    background: rgba(248, 113, 113, 0.10);
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
    flex-shrink: 0;
}

.social-name {
    font-weight: 600;
}

.social-val-preview {
    font-size: 0.76rem;
    color: var(--text-muted);
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.social-copy-badge {
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
}

.social-link-chat {
    border-color: rgba(37, 211, 102, 0.4);
}

.social-link-chat:hover {
    border-color: #25D366;
    box-shadow: 0 4px 14px rgba(37, 211, 102, 0.3);
}

.social-chat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #10b981;
    background: rgba(16, 185, 129, 0.12);
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
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

/* Manager Panel */
.profile-manager-card {
    padding: 1.75rem;
    border: 1px solid var(--border-color);
    background: var(--bg-surface-elevated);
    box-shadow: var(--shadow-md);
}

.manager-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.manager-title {
    font-size: 1.35rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.manager-subtitle {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 0.25rem 0 0;
}

.manager-tab-nav {
    display: inline-flex;
    background: var(--bg-surface);
    padding: 4px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    gap: 4px;
}

.manager-tab-btn {
    border: none;
    background: transparent;
    padding: 0.45rem 0.95rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-secondary);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.manager-tab-btn:hover {
    color: var(--text-primary);
}

.manager-tab-btn.active {
    background: var(--primary);
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 2px 8px var(--primary-glow);
}

.tab-pane-intro {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

/* Quick Jump Chips */
.social-quick-chips {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    padding: 0.65rem 0.85rem;
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.chips-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-right: 0.25rem;
}

.social-chip-jump {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    font-size: 0.78rem;
    border-radius: 9999px;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.social-chip-jump:hover {
    border-color: var(--primary);
    color: var(--text-primary);
}

.social-chip-jump.is-active {
    border-color: rgba(16, 185, 129, 0.5);
    color: var(--text-primary);
}

.chip-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--text-muted);
}

.chip-dot.dot-active {
    background: #10b981;
    box-shadow: 0 0 6px #10b981;
}

/* Social Inputs Grid */
.social-inputs-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.social-input-row {
    display: grid;
    grid-template-columns: 240px 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.social-input-row:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 1px var(--primary);
}

.social-row-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.social-input-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    background: var(--bg-surface-elevated);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.social-input-name {
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.badge-mini {
    font-size: 0.65rem;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    background: rgba(99, 102, 241, 0.12);
    color: var(--primary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.social-input-hint {
    font-size: 0.73rem;
    color: var(--text-muted);
    margin-top: 1px;
}

.social-row-center {
    width: 100%;
}

.social-row-right {
    min-width: 80px;
    text-align: right;
}

.btn-remove-social {
    color: var(--danger) !important;
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
}

.btn-remove-social:hover {
    background: rgba(239, 68, 68, 0.1) !important;
}

/* Preview Box */
.social-preview-box {
    margin-top: 1.5rem;
    padding: 1.25rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
}

.preview-box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.preview-box-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Quick Skill Adder */
.quick-skill-adder-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 1.25rem;
}

.quick-skill-form-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.skill-type-toggle {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.type-pill {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.84rem;
    font-weight: 500;
    padding: 0.4rem 0.6rem;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    cursor: pointer;
}

.type-pill input:checked + span {
    color: var(--primary);
    font-weight: 700;
}

.dynamic-skills-summary-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.dynamic-summary-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.82rem;
    font-weight: 500;
}

.dynamic-summary-pill .btn-delete-summary {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 1rem;
    padding: 0 2px;
    line-height: 1;
}

.dynamic-summary-pill .btn-delete-summary:hover {
    color: var(--danger);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    transition: all var(--transition-fast);
}

.skill-actions-right .btn-icon:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.skill-actions-right .btn-icon.text-danger:hover {
    border-color: var(--danger);
    color: var(--danger);
}

@media (max-width: 900px) {
    .social-input-row {
        grid-template-columns: 1fr auto;
    }
    .social-row-left {
        grid-column: 1 / -1;
    }
    .quick-skill-form-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .profile-hero-card { padding: 1.5rem; }
    .skills-management-grid { grid-template-columns: 1fr; }
    .profile-hero-actions { width: 100%; flex-direction: row; }
    .quick-skill-form-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>