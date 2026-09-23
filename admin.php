<?php
/**
 * Administrator Console (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$pageTitle = 'Admin Console - Skill Swap Marketplace';
$pageScript = 'admin.js';
include __DIR__ . '/includes/header.php';
?>

<div class="admin-page-container">
    <div class="admin-header-card">
        <div>
            <span class="badge-pill bg-danger" style="margin-bottom: 0.5rem; display: inline-block;">Administrator Access</span>
            <h1 style="font-size: 2.25rem; margin-bottom: 0.25rem;">Platform Control Center</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Manage registered users, master skill taxonomy, and community moderation reports.
            </p>
        </div>

        <div class="admin-tabs-nav">
            <button type="button" class="admin-tab-btn active" data-tab="stats">System Metrics</button>
            <button type="button" class="admin-tab-btn" data-tab="users">Users Management</button>
            <button type="button" class="admin-tab-btn" data-tab="skills">Skills Taxonomy</button>
            <button type="button" class="admin-tab-btn" data-tab="reports">Reports & Moderation</button>
        </div>
    </div>

    <!-- Tab 1: Stats -->
    <div class="admin-tab-pane" id="admin-tab-stats">
        <div class="admin-stats-grid">
            <div class="card stat-card">
                <span class="stat-card-title">Total Users</span>
                <span class="stat-card-val" id="stat-total-users">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Registered accounts</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card-title">Active Users</span>
                <span class="stat-card-val" id="stat-active-users" style="color: var(--success);">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">In good standing</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card-title">Catalog Skills</span>
                <span class="stat-card-val" id="stat-total-skills" style="color: var(--primary);">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Taxonomy count</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card-title">Total Swaps</span>
                <span class="stat-card-val" id="stat-total-swaps">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Proposed requests</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card-title">Accepted Swaps</span>
                <span class="stat-card-val" id="stat-accepted-swaps" style="color: var(--secondary);">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Connected pairs</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card-title">Pending Reports</span>
                <span class="stat-card-val" id="stat-pending-reports" style="color: var(--danger);">-</span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Flagged for review</span>
            </div>
        </div>
    </div>

    <!-- Tab 2: Users -->
    <div class="admin-tab-pane hidden" id="admin-tab-users">
        <div class="card" style="padding: 1.5rem; overflow-x: auto;">
            <div class="card-header">
                <h3 class="card-title">User Accounts</h3>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Skills</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admin-users-table-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;"><span class="spinner spinner-primary"></span></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 3: Skills -->
    <div class="admin-tab-pane hidden" id="admin-tab-skills">
        <div class="card" style="padding: 1.5rem; overflow-x: auto;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Master Skills Taxonomy</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Categorized database of skills available to members</span>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btn-admin-add-skill">+ Add Master Skill</button>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Skill Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Usage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admin-skills-table-body">
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;"><span class="spinner spinner-primary"></span></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 4: Reports -->
    <div class="admin-tab-pane hidden" id="admin-tab-reports">
        <div class="card" style="padding: 1.75rem;">
            <div class="card-header">
                <h3 class="card-title">Content & Member Reports</h3>
            </div>
            <div id="admin-reports-list">
                <div style="text-align: center; padding: 2rem;"><span class="spinner spinner-primary"></span></div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-page-container {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.admin-header-card {
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

.admin-tabs-nav {
    display: flex;
    background: var(--bg-surface-elevated);
    padding: 4px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 4px;
}

.admin-tab-btn {
    background: transparent;
    border: none;
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.admin-tab-btn.active {
    background: var(--bg-surface);
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
}

.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
}

.stat-card {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
}

.stat-card-title {
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 700;
}

.stat-card-val {
    font-family: var(--font-heading);
    font-size: 2.25rem;
    font-weight: 800;
    margin: 0.35rem 0;
}

/* Admin Table Styles */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    text-align: left;
}

.admin-table th {
    padding: 0.85rem 1rem;
    border-bottom: 2px solid var(--border-color);
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.admin-table td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.admin-table tr:hover td {
    background: var(--bg-surface-elevated);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
