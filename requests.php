<?php
/**
 * Swap Requests Management (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$pageTitle = 'Swap Requests - Skill Swap Marketplace';
$pageScript = 'requests.js';
include __DIR__ . '/includes/header.php';
?>

<div class="requests-page-container">
    <div class="requests-header-card">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Skill Swap Proposals</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Review incoming offers from community members and track your sent requests.
            </p>
        </div>

        <div class="requests-tabs-nav">
            <button type="button" class="requests-tab-btn active" data-tab="incoming">
                Incoming Proposals
                <span class="badge-pill bg-primary hidden" id="incoming-badge-count">0</span>
            </button>
            <button type="button" class="requests-tab-btn" data-tab="outgoing">
                Sent Proposals
            </button>
        </div>
    </div>

    <!-- Tab 1: Incoming -->
    <div class="requests-tab-pane" id="tab-incoming">
        <div id="incoming-requests-list">
            <div style="text-align: center; padding: 3rem;">
                <span class="spinner spinner-primary"></span>
                <p style="margin-top: 0.75rem; color: var(--text-muted);">Loading proposals...</p>
            </div>
        </div>
    </div>

    <!-- Tab 2: Outgoing -->
    <div class="requests-tab-pane hidden" id="tab-outgoing">
        <div id="outgoing-requests-list">
            <div style="text-align: center; padding: 3rem;">
                <span class="spinner spinner-primary"></span>
            </div>
        </div>
    </div>
</div>

<style>
.requests-page-container {
    max-width: 900px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}

.requests-header-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.requests-tabs-nav {
    display: flex;
    background: var(--bg-surface-elevated);
    padding: 4px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.requests-tab-btn {
    background: transparent;
    border: none;
    padding: 8px 18px;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all var(--transition-fast);
}

.requests-tab-btn.active {
    background: var(--bg-surface);
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
