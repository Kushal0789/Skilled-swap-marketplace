<?php
/**
 * Discover & Search Portal (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db();

// Preload categories and quick community stats
$catStmt = $pdo->query("SELECT DISTINCT category FROM skills ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$currentUid = current_user_id() ?? 0;
$activeMembersCount = 0;
$totalSkillsCount = 0;
try {
    $mStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active' AND role != 'admin' AND id != :uid");
    $mStmt->execute([':uid' => $currentUid]);
    $activeMembersCount = (int)$mStmt->fetchColumn();
    $totalSkillsCount = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
} catch (Exception $e) {
    // Silent fallback
}

$initialCategory = trim($_GET['category'] ?? '');
$initialQuery = trim($_GET['q'] ?? '');

$pageTitle = 'Discover Talented Members & Skills - Skill Swap';
$pageDesc = 'Search and filter Skill Swap members by skill, category, proficiency, and location to propose reciprocal skill exchanges.';
$pageCss = 'discover.css';
$pageScript = 'discover.js';
include __DIR__ . '/includes/header.php';
?>

<div class="discover-page-container" data-logged-in="<?= is_logged_in() ? '1' : '0' ?>">
    <!-- Search & Filter Hero -->
    <section class="discover-hero" aria-label="Discover skills search and filters">
        <div class="discover-hero-inner">
            <div class="discover-hero-top">
                <div>
                    <span class="discover-eyebrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Skill Marketplace Directory
                    </span>
                    <h1 class="discover-title">
                        Discover <span class="discover-title-accent">Skills &amp; Mentors</span>
                    </h1>
                    <p class="discover-desc">
                        Explore peers ready to teach what they know and learn what you offer — filter by skill, expertise level, or location.
                    </p>
                </div>

                <div class="discover-hero-stats">
                    <div class="hero-stat-pill">
                        <div>
                            <span class="hero-stat-value"><?= $activeMembersCount ?></span>
                            <span class="hero-stat-label">Active Members</span>
                        </div>
                    </div>
                    <div class="hero-stat-pill">
                        <div>
                            <span class="hero-stat-value"><?= $totalSkillsCount ?></span>
                            <span class="hero-stat-label">Catalog Skills</span>
                        </div>
                    </div>
                    <?php if (is_logged_in()): ?>
                        <a href="matches.php" class="hero-matchmaker-link" title="View automatic two-way skill matches">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            2-Way Matches &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Primary Search + Intent Toggle Row -->
            <div class="discover-search-row">
                <div class="filter-search-box">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text"
                           id="discover-search-input"
                           class="filter-input-search"
                           value="<?= e($initialQuery) ?>"
                           placeholder="Search skill (e.g. Python, UI Design, Guitar), name, or @username..."
                           autocomplete="off">
                    <kbd class="search-kbd-hint" id="search-kbd-hint" title="Press / to focus">/</kbd>
                    <button type="button" id="clear-search-btn" class="clear-search-btn <?= empty($initialQuery) ? 'hidden' : '' ?>" aria-label="Clear search">&times;</button>
                </div>

                <!-- Segmented Skill Intent Filter -->
                <div class="intent-segmented-control" role="group" aria-label="Filter by skill intent">
                    <button type="button" class="intent-btn active" data-type="">All</button>
                    <button type="button" class="intent-btn" data-type="OFFER">🎁 Teaching</button>
                    <button type="button" class="intent-btn" data-type="WANT">🎯 Learning</button>
                </div>
            </div>

            <!-- Secondary Dropdowns & Location Row -->
            <div class="discover-filter-row">
                <div class="filter-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="filter-field-icon"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    <select id="category-filter" class="filter-select-padded" aria-label="Filter by category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= ($initialCategory === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="filter-field-icon"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <select id="proficiency-filter" class="filter-select-padded" aria-label="Filter by proficiency">
                        <option value="">Any Proficiency</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>

                <div class="filter-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="filter-field-icon"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <input type="text" id="location-filter" class="filter-location-input" placeholder="Filter by location (e.g. Remote, NYC)..." autocomplete="off">
                </div>
            </div>

            <!-- Quick Filter Category Chips -->
            <div class="category-quick-chips" role="group" aria-label="Quick category filters">
                <span class="chips-prefix">Disciplines:</span>
                <button type="button" class="cat-chip-btn <?= empty($initialCategory) ? 'active' : '' ?>" data-cat="">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="cat-chip-btn <?= ($initialCategory === $cat) ? 'active' : '' ?>" data-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Results Toolbar -->
    <div class="discover-toolbar">
        <div class="results-summary">
            <span id="results-count-text" class="results-count-text">Loading members...</span>
        </div>

        <div class="toolbar-controls">
            <div class="sort-control">
                <span class="sort-label">Sort:</span>
                <select id="discover-sort" class="sort-select" aria-label="Sort members">
                    <option value="newest">Newest Members</option>
                    <option value="rating">Highest Rated</option>
                    <option value="reviews">Most Reviewed</option>
                    <option value="skills">Most Skills</option>
                </select>
            </div>

            <div class="view-mode-toggle" role="group" aria-label="Toggle layout view">
                <button type="button" class="view-toggle-btn active" id="view-grid-btn" data-view="grid" title="Grid view" aria-label="Grid view">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </button>
                <button type="button" class="view-toggle-btn" id="view-list-btn" data-view="list" title="List view" aria-label="List view">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Active Filter Tags -->
    <div class="active-filters-bar hidden" id="active-filters-bar"></div>

    <!-- Results Grid -->
    <div class="discover-grid" id="discover-results-grid">
        <!-- Rendered dynamically by discover.js -->
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
