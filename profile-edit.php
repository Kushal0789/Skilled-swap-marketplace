<?php
/**
 * Edit Profile & Skills - Dedicated Management Interface
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Authentication required to access edit mode
require_auth();

$currentUid = current_user_id();
$pdo = get_db();

// 1. Fetch current user profile data
$uStmt = $pdo->prepare("
    SELECT id, name, username, email, bio, location, profile_image, role, status, created_at,
           discord, facebook, contact_email, github, linkedin, twitter, instagram, whatsapp, website
    FROM users WHERE id = :id LIMIT 1
");
$uStmt->execute([':id' => $currentUid]);
$targetUser = $uStmt->fetch();

if (!$targetUser) {
    header('Location: login.php');
    exit;
}

$allPlatforms = get_social_platforms_meta();

// 2. Fetch current offered skills
$offStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'OFFER'
    ORDER BY s.name ASC
");
$offStmt->execute([':id' => $currentUid]);
$offeredSkills = $offStmt->fetchAll();

// 3. Fetch current wanted skills
$wantStmt = $pdo->prepare("
    SELECT us.id, us.skill_id, us.proficiency, us.description, s.name, s.category
    FROM user_skills us
    INNER JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = :id AND us.skill_type = 'WANT'
    ORDER BY s.name ASC
");
$wantStmt->execute([':id' => $currentUid]);
$wantedSkills = $wantStmt->fetchAll();

$pageTitle = 'Edit Profile & Skills - Skill Swap';
$pageScript = 'profile-edit.js';
include __DIR__ . '/includes/header.php';
?>

<div class="profile-edit-container">
    <!-- Top Navigation / Action Bar -->
    <div class="edit-page-header">
        <div>
            <a href="profile.php" class="edit-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Profile
            </a>
            <h1 class="edit-page-title">Edit Profile &amp; Skills</h1>
            <p class="edit-page-subtitle">Update your personal details, direct chat channels, and skills in one dedicated space.</p>
        </div>

        <div class="edit-header-actions">
            <a href="availability.php" class="btn btn-ghost btn-sm" title="Manage your weekly swap availability">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Availability
            </a>
            <a href="profile.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" form="profile-edit-form" class="btn btn-primary" id="btn-save-header">
                💾 Save Changes
            </button>
        </div>
    </div>

    <!-- Avatar & Identity Quick Banner -->
    <div class="card edit-avatar-card">
        <div class="edit-avatar-content">
            <div class="profile-avatar-wrapper">
                <img src="<?= e(get_avatar_url($targetUser['profile_image'])) ?>" alt="<?= e($targetUser['name']) ?>" id="profile-avatar-preview" class="profile-avatar-large">
                <!-- Camera badge button -->
                <button type="button" id="avatarMenuBtn" class="avatar-edit-badge" title="Change profile picture" aria-haspopup="true" aria-expanded="false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                </button>
                <!-- Avatar Dropdown menu -->
                <div id="avatarDropdown" class="avatar-dropdown" role="menu" aria-label="Profile photo options" style="display: none;">
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
            </div>

            <div class="edit-avatar-text">
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <h2 style="font-size: 1.35rem; margin: 0;"><?= e($targetUser['name']) ?></h2>
                    <span style="color: var(--text-muted); font-size: 0.95rem;">@<?= e($targetUser['username']) ?></span>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.35rem 0 0;">
                    Click the camera icon on your avatar to upload a custom profile picture (JPG, PNG, or WebP up to 2MB) or remove it.
                </p>
            </div>
        </div>
    </div>

    <!-- Main Profile & Skills Edit Management Panel -->
    <div class="card profile-manager-card" id="profile-manager-section">
        <div class="card-header manager-header">
            <div>
                <h2 class="card-title manager-title">
                    <span>⚙️</span> Profile Management
                </h2>
                <p class="manager-subtitle">
                    Switch between sections to edit your personal bio, contact links, and skills.
                </p>
            </div>

            <!-- Navigation Tabs -->
            <div class="manager-tab-nav" role="tablist">
                <button type="button" class="manager-tab-btn active" data-tab="tab-details">
                    👤 Personal Details
                </button>
                <button type="button" class="manager-tab-btn" data-tab="tab-social">
                    🌐 Social &amp; Chat Links
                </button>
                <button type="button" class="manager-tab-btn" data-tab="tab-skills">
                    ⚡ Quick Skill Manager
                </button>
            </div>
        </div>

        <!-- Unified Profile Form -->
        <form id="profile-edit-form">
            <!-- TAB 1: PERSONAL DETAILS -->
            <div class="manager-tab-pane active" id="tab-details">
                <div class="tab-pane-intro">
                    <div>
                        <h3 style="font-size: 1.08rem; margin-bottom: 0.2rem;">Personal Information</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">
                            These details appear at the top of your public profile card for other members to view.
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Display Name *</label>
                        <input type="text" name="name" class="form-input" value="<?= e($targetUser['name']) ?>" required placeholder="Your full name or display name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" value="<?= e($targetUser['location'] ?? '') ?>" placeholder="e.g. Seattle, WA or Remote">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Bio &amp; Learning Philosophy</label>
                    <textarea name="bio" class="form-textarea" rows="5" placeholder="Tell members about yourself, what you love building, and what you want to learn..."><?= e($targetUser['bio'] ?? '') ?></textarea>
                    <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 4px;">Brief introduction highlighting your experience and passions.</span>
                </div>
            </div>

            <!-- TAB 2: SOCIAL & CHAT LINKS -->
            <div class="manager-tab-pane" id="tab-social" style="display: none;">
                <div class="tab-pane-intro">
                    <div>
                        <h3 style="font-size: 1.08rem; margin-bottom: 0.2rem;">Direct Online Chat &amp; Social Channels</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">
                            Connect channels where other users can chat with you directly online. Channels left empty remain hidden from your public profile.
                        </p>
                    </div>
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
                        <span style="font-size: 0.78rem; color: var(--text-muted);">Real-time reflection of active links</span>
                    </div>
                    <div class="profile-social-links" id="live-social-preview-bar">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>
        </form>

        <!-- TAB 3: QUICK SKILL MANAGER -->
        <div class="manager-tab-pane" id="tab-skills" style="display: none;">
            <div class="tab-pane-intro">
                <div>
                    <h3 style="font-size: 1.08rem; margin-bottom: 0.2rem;">Quick Skill Manager</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">
                        Add skills you can offer or wishlist items you want to learn. Applied dynamically to your profile.
                    </p>
                </div>
            </div>

            <!-- Dynamic Skill Adder Form -->
            <div class="quick-skill-adder-card">
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

                    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" id="btn-submit-quick-skill">
                            + Add Skill Dynamically
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Skills Dynamic Overview Summary Pills -->
            <div style="margin-top: 1.5rem;">
                <h4 style="font-size: 0.92rem; margin-bottom: 0.65rem; color: var(--text-secondary);">Your Skills Overview (Click &times; to delete dynamically):</h4>
                <div class="dynamic-skills-summary-container" id="dynamic-skills-summary">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Full Skills Grid with Editing Controls in Edit Mode -->
            <div class="skills-management-grid" style="margin-top: 2rem;">
                <!-- Offered Skills -->
                <div class="card" style="background: var(--bg-surface);">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title" style="font-size: 1.05rem;">Skills I Can Offer</h3>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">Skills you are capable of teaching others</span>
                        </div>
                    </div>

                    <div class="skills-card-list" id="offered-skills-list">
                        <?php if (empty($offeredSkills)): ?>
                            <div class="empty-state" style="padding: 1.75rem 1rem;">
                                <p style="font-size: 0.88rem; color: var(--text-muted);">No skills offered yet. Add your first skill using the form above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($offeredSkills as $s): ?>
                                <div class="skill-badge-item" id="user-skill-<?= $s['id'] ?>">
                                    <div class="skill-info-left">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <strong style="font-size: 0.95rem;"><?= e($s['name']) ?></strong>
                                            <span class="proficiency-pill proficiency-<?= e($s['proficiency']) ?>"><?= e($s['proficiency']) ?></span>
                                        </div>
                                        <span style="font-size: 0.76rem; color: var(--text-muted); display: block; margin-top: 2px;">
                                            Category: <?= e($s['category']) ?>
                                            <?= !empty($s['description']) ? ' &bull; ' . e($s['description']) : '' ?>
                                        </span>
                                    </div>
                                    <div class="skill-actions-right">
                                        <button type="button" class="btn-icon btn-edit-skill" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-proficiency="<?= e($s['proficiency']) ?>" data-description="<?= e($s['description'] ?? '') ?>" title="Edit details">
                                            ✎
                                        </button>
                                        <button type="button" class="btn-icon btn-delete-skill text-danger" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" title="Remove skill">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Wanted Skills -->
                <div class="card" style="background: var(--bg-surface);">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title" style="font-size: 1.05rem;">Skills I Want to Learn</h3>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">Subjects and topics you want guidance in</span>
                        </div>
                    </div>

                    <div class="skills-card-list" id="wanted-skills-list">
                        <?php if (empty($wantedSkills)): ?>
                            <div class="empty-state" style="padding: 1.75rem 1rem;">
                                <p style="font-size: 0.88rem; color: var(--text-muted);">No learning wishlist items yet. Add desired skills using the form above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($wantedSkills as $s): ?>
                                <div class="skill-badge-item" id="user-skill-<?= $s['id'] ?>">
                                    <div class="skill-info-left">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <strong style="font-size: 0.95rem; color: var(--primary);"><?= e($s['name']) ?></strong>
                                            <span class="proficiency-pill proficiency-<?= e($s['proficiency']) ?>">Target: <?= e($s['proficiency']) ?></span>
                                        </div>
                                        <span style="font-size: 0.76rem; color: var(--text-muted); display: block; margin-top: 2px;">
                                            Category: <?= e($s['category']) ?>
                                            <?= !empty($s['description']) ? ' &bull; ' . e($s['description']) : '' ?>
                                        </span>
                                    </div>
                                    <div class="skill-actions-right">
                                        <button type="button" class="btn-icon btn-edit-skill" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-proficiency="<?= e($s['proficiency']) ?>" data-description="<?= e($s['description'] ?? '') ?>" title="Edit details">
                                            ✎
                                        </button>
                                        <button type="button" class="btn-icon btn-delete-skill text-danger" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" title="Remove skill">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar with Clear Save Changes and Cancel Buttons -->
        <div class="edit-bottom-bar">
            <a href="profile.php" class="btn btn-secondary btn-lg" id="btn-cancel-bottom">
                Cancel
            </a>
            <button type="submit" form="profile-edit-form" class="btn btn-primary btn-lg" id="btn-save-bottom">
                💾 Save Changes
            </button>
        </div>
    </div>
</div>

<style>
.profile-edit-container {
    max-width: 1100px;
    margin: 2rem auto 4rem;
    padding: 0 1.5rem;
}

.edit-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
    gap: 1.25rem;
}

.edit-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    color: var(--text-muted);
    text-decoration: none;
    margin-bottom: 0.5rem;
    transition: color var(--transition-fast);
}

.edit-back-link:hover {
    color: var(--primary);
}

.edit-page-title {
    font-size: 1.85rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.edit-page-subtitle {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin: 0.35rem 0 0;
}

.edit-header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.edit-avatar-card {
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.75rem;
    background: var(--gradient-card);
}

.edit-avatar-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.profile-avatar-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
}

.profile-avatar-large {
    width: 90px;
    height: 90px;
    border-radius: var(--radius-full);
    object-fit: cover;
    border: 3px solid var(--primary);
    box-shadow: 0 4px 18px var(--primary-glow);
}

.avatar-edit-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 32px;
    height: 32px;
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
}

.avatar-edit-badge:hover {
    transform: scale(1.1);
}

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

.profile-manager-card {
    padding: 2rem;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
}

.manager-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1.25rem;
    margin-bottom: 1.75rem;
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
    padding: 0.5rem 1rem;
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

.profile-social-links {
    display: flex;
    gap: 0.6rem;
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
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    line-height: 1.2;
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

/* Bottom Action Bar */
.edit-bottom-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
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
    .skills-management-grid { grid-template-columns: 1fr; }
    .quick-skill-form-grid { grid-template-columns: 1fr; }
    .edit-page-header { flex-direction: column; align-items: flex-start; }
    .edit-header-actions { width: 100%; justify-content: flex-end; }
    .edit-bottom-bar { justify-content: stretch; }
    .edit-bottom-bar .btn { flex: 1; text-align: center; justify-content: center; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
