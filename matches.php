<?php
/**
 * Skill Matchmaking Interface (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$pageTitle = 'Skill Matchmaker - Reciprocal Exchanges - Skill Swap';
$pageScript = 'matches.js';
include __DIR__ . '/includes/header.php';
?>

<div class="matches-page-container">
    <div class="matches-header">
        <span class="badge-pill bg-primary" style="margin-bottom: 0.5rem; display: inline-block;">Matching Algorithm</span>
        <h1 class="matches-title">Skill Compatibility Engine</h1>
        <p class="matches-desc">
            We analyze your offered skills and wanted learning wishlist to discover peers where both parties benefit equally.
        </p>
    </div>

    <!-- Section 1: Strong Reciprocal Matches -->
    <div class="match-category-block">
        <div class="match-category-title-row">
            <div>
                <h2 style="font-size: 1.5rem; display: flex; align-items: center; gap: 0.65rem;">
                    <span>✨ Two-Way Reciprocal Matches</span>
                    <span class="badge-pill" style="background: var(--gradient-brand); color: #ffffff;" id="two-way-count">0</span>
                </h2>
                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-top: 2px;">
                    Users who offer skills you want to learn AND want to learn skills you offer. High swap success rate!
                </p>
            </div>
        </div>

        <div class="matches-grid" id="two-way-matches-grid">
            <div style="text-align: center; padding: 3rem;" class="col-span-full">
                <span class="spinner spinner-primary" style="width: 32px; height: 32px;"></span>
                <p style="margin-top: 1rem; color: var(--text-muted);">Analyzing database for 2-way matches...</p>
            </div>
        </div>
    </div>

    <!-- Section 2: One-Way Matches -->
    <div class="match-category-block" style="margin-top: 3.5rem;">
        <div class="match-category-title-row">
            <div>
                <h2 style="font-size: 1.35rem; display: flex; align-items: center; gap: 0.65rem;">
                    <span>💡 Mentors & One-Way Opportunities</span>
                    <span class="badge-pill" style="background: var(--bg-surface-elevated); color: var(--text-secondary);" id="one-way-count">0</span>
                </h2>
                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-top: 2px;">
                    Members who offer skills on your wishlist. You can propose teaching them one of your skills or explore options.
                </p>
            </div>
        </div>

        <div class="matches-grid" id="one-way-matches-grid">
            <!-- Dynamically populated -->
        </div>
    </div>
</div>

<style>
.matches-page-container {
    max-width: 1280px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}

.matches-header {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2.5rem;
    margin-bottom: 2.5rem;
    text-align: center;
}

.matches-title {
    font-size: 2.25rem;
    margin-bottom: 0.5rem;
}

.matches-desc {
    color: var(--text-secondary);
    font-size: 1.05rem;
    max-width: 650px;
    margin: 0 auto;
}

.match-category-title-row {
    margin-bottom: 1.5rem;
}

.matches-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.75rem;
}

@media (max-width: 768px) {
    .matches-header { padding: 1.5rem; }
    .matches-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
