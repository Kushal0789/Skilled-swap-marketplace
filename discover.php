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

// Preload categories
$catStmt = $pdo->query("SELECT DISTINCT category FROM skills ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$initialCategory = trim($_GET['category'] ?? '');

$pageTitle = 'Discover Talented Members & Skills - Skill Swap';
$pageScript = 'discover.js';
include __DIR__ . '/includes/header.php';
?>

<div class="discover-page-container">
    <!-- Search & Filter Controls Hero -->
    <div class="discover-hero">
        <h1 class="discover-title">Discover Skills & Mentors</h1>
        <p class="discover-desc">Search through hundreds of passionate learners, developers, designers, and creatives.</p>

        <div class="discover-filter-bar">
            <!-- Search Input -->
            <div class="filter-search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="discover-search-input" class="filter-input-search" placeholder="Search by skill name, topic, or member username...">
            </div>

            <!-- Category Select -->
            <div class="filter-select-wrapper">
                <select id="category-filter" class="form-select filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= ($initialCategory === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Proficiency Select -->
            <div class="filter-select-wrapper">
                <select id="proficiency-filter" class="form-select filter-select">
                    <option value="">Any Proficiency</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Expert">Expert</option>
                </select>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
            <span id="results-count-text" style="font-size: 0.88rem; color: var(--text-secondary);">Loading members...</span>
            <span style="font-size: 0.82rem; color: var(--text-muted);">Results update automatically</span>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="discover-grid" id="discover-results-grid">
        <!-- Rendered dynamically by discover.js -->
    </div>
</div>

<style>
.discover-page-container {
    max-width: 1280px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}

.discover-hero {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2.5rem;
    margin-bottom: 2.5rem;
    box-shadow: var(--shadow-sm);
}

.discover-title {
    font-size: 2.25rem;
    margin-bottom: 0.4rem;
}

.discover-desc {
    color: var(--text-secondary);
    font-size: 1.05rem;
    margin-bottom: 2rem;
}

.discover-filter-bar {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-search-box {
    flex: 2;
    min-width: 280px;
    position: relative;
    display: flex;
    align-items: center;
}

.filter-search-box .search-icon {
    position: absolute;
    left: 14px;
    color: var(--text-muted);
}

.filter-input-search {
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 2.75rem;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 0.95rem;
    outline: none;
    transition: all var(--transition-fast);
}

.filter-input-search:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-glow);
}

.filter-select-wrapper {
    flex: 1;
    min-width: 170px;
}

.filter-select {
    padding: 0.85rem 1rem;
}

.discover-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.75rem;
}

@media (max-width: 768px) {
    .discover-hero { padding: 1.5rem; }
    .discover-filter-bar { flex-direction: column; }
    .discover-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
