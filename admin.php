<?php
/**
 * Administrator Console (Presentation Layer)
 * Skill Swap Marketplace
 *
 * Modernized UI/UX with Executive Dashboard, Live Telemetry,
 * Real-time Filters, and Streamlined Moderation.
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$allowedSections = ['dashboard', 'users', 'skills', 'swaps', 'reports'];
$activeSection = isset($_GET['section']) && in_array($_GET['section'], $allowedSections, true)
    ? $_GET['section']
    : 'dashboard';

$sectionMeta = [
    'dashboard' => ['icon' => '📊', 'title' => 'Dashboard Overview',  'desc' => 'Platform metrics, health telemetry, and recent events', 'breadcrumb' => 'Console / Overview'],
    'users'     => ['icon' => '👥', 'title' => 'User Management',     'desc' => 'Audit, moderate, and manage registered marketplace accounts', 'breadcrumb' => 'Console / Users'],
    'skills'    => ['icon' => '🎯', 'title' => 'Skills Taxonomy',     'desc' => 'Curate and organize the master skill exchange catalog', 'breadcrumb' => 'Console / Skills'],
    'swaps'     => ['icon' => '🔄', 'title' => 'Swap Requests Hub',   'desc' => 'Monitor reciprocal and one-way peer exchanges in real time', 'breadcrumb' => 'Console / Swaps'],
    'reports'   => ['icon' => '🛡️', 'title' => 'Moderation & Reports', 'desc' => 'Review flagged members and enforce community safety guidelines', 'breadcrumb' => 'Console / Moderation'],
];
$currentMeta = $sectionMeta[$activeSection] ?? $sectionMeta['dashboard'];

$pageTitle = 'Admin Console · SkillSwap Marketplace';
$pageScript = 'admin.js';
include __DIR__ . '/includes/header.php';
?>

<!-- Dedicated Admin Stylesheet -->
<link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">

<div class="ac-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <aside class="ac-sidebar" id="admin-sidebar">
        <div class="ac-sidebar-brand">
            <div class="ac-brand-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </div>
            <div>
                <div class="ac-brand-title">Admin Console</div>
                <div class="ac-brand-sub">Platform Control</div>
            </div>
        </div>

        <div class="ac-sidebar-section-title">Navigation</div>
        <nav class="ac-sidenav" id="admin-sidebar-nav">
            <a href="admin.php?section=dashboard" class="ac-nav-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>" data-tab="dashboard">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </span>
                <span>Dashboard</span>
            </a>
            <a href="admin.php?section=users" class="ac-nav-item <?= $activeSection === 'users' ? 'active' : '' ?>" data-tab="users">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                <span>Users</span>
                <span class="ac-nav-badge ac-nav-badge--neutral" id="sidebar-users-badge">—</span>
            </a>
            <a href="admin.php?section=skills" class="ac-nav-item <?= $activeSection === 'skills' ? 'active' : '' ?>" data-tab="skills">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </span>
                <span>Skills</span>
                <span class="ac-nav-badge ac-nav-badge--neutral" id="sidebar-skills-badge">—</span>
            </a>
            <a href="admin.php?section=swaps" class="ac-nav-item <?= $activeSection === 'swaps' ? 'active' : '' ?>" data-tab="swaps">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </span>
                <span>Swaps</span>
                <span class="ac-nav-badge ac-nav-badge--neutral" id="sidebar-swaps-badge">—</span>
            </a>
            <a href="admin.php?section=reports" class="ac-nav-item <?= $activeSection === 'reports' ? 'active' : '' ?>" data-tab="reports">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <span>Reports</span>
                <span class="ac-nav-badge ac-nav-badge--danger hidden" id="sidebar-reports-badge">0</span>
            </a>
        </nav>

        <div class="ac-sidebar-footer">
            <a href="index.php" class="ac-sidebar-link" title="Exit to marketplace">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Marketplace</span>
            </a>
            <a href="settings.php" class="ac-sidebar-link" title="Admin account settings">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="ac-sidebar-link ac-sidebar-link--danger" title="Sign out">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <!-- ===================== MAIN CONTENT ===================== -->
    <main class="ac-main">

        <!-- Executive Command Header -->
        <header class="ac-command-header" id="ac-command-header">
            <div class="ac-command-left">
                <div class="ac-command-icon-box" id="ac-banner-icon"><?= $currentMeta['icon'] ?></div>
                <div class="ac-command-titles">
                    <span class="ac-command-breadcrumb" id="ac-banner-breadcrumb"><?= htmlspecialchars($currentMeta['breadcrumb']) ?></span>
                    <h1 class="ac-command-title" id="ac-banner-title"><?= htmlspecialchars($currentMeta['title']) ?></h1>
                    <p class="ac-command-desc" id="ac-banner-desc"><?= htmlspecialchars($currentMeta['desc']) ?></p>
                </div>
            </div>
            <div class="ac-command-actions">
                <span class="ac-live-pill">
                    <span class="ac-live-dot"></span>
                    <span>System Active</span>
                </span>
                <button type="button" class="btn-ac-refresh" id="btn-admin-refresh" title="Reload live data">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    <span>Refresh</span>
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-quick-add-skill-top" title="Quickly add a skill to catalog">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>New Skill</span>
                </button>
            </div>
        </header>

        <!-- ========================================================
             1. DASHBOARD TAB
             ======================================================== -->
        <section class="ac-pane <?= $activeSection !== 'dashboard' ? 'hidden' : '' ?>" id="admin-tab-dashboard">

            <!-- Interactive Metric Cards -->
            <div class="ac-stats-grid" id="admin-stats-grid">
                <div class="ac-stat-card ac-stat--blue" data-jump-tab="users" title="Click to inspect users">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Total Users</span>
                        <div class="ac-stat-icon-wrap">👥</div>
                    </div>
                    <div class="ac-stat-val" id="stat-total-users">—</div>
                    <div class="ac-stat-sub-row">
                        <span>Registered accounts</span>
                        <span class="ac-stat-hint-pill">Manage &rarr;</span>
                    </div>
                </div>

                <div class="ac-stat-card ac-stat--green" data-jump-tab="users" title="Click to view active users">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Active Users</span>
                        <div class="ac-stat-icon-wrap">✅</div>
                    </div>
                    <div class="ac-stat-val" id="stat-active-users">—</div>
                    <div class="ac-stat-sub-row">
                        <span>In good standing</span>
                        <span class="ac-stat-hint-pill">Audit &rarr;</span>
                    </div>
                </div>

                <div class="ac-stat-card ac-stat--purple" data-jump-tab="skills" title="Click to view skills taxonomy">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Skills Catalog</span>
                        <div class="ac-stat-icon-wrap">🎯</div>
                    </div>
                    <div class="ac-stat-val" id="stat-total-skills">—</div>
                    <div class="ac-stat-sub-row">
                        <span>Master taxonomy</span>
                        <span class="ac-stat-hint-pill">Explore &rarr;</span>
                    </div>
                </div>

                <div class="ac-stat-card ac-stat--teal" data-jump-tab="swaps" title="Click to view all swaps">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Total Swaps</span>
                        <div class="ac-stat-icon-wrap">🔄</div>
                    </div>
                    <div class="ac-stat-val" id="stat-total-swaps">—</div>
                    <div class="ac-stat-sub-row">
                        <span>All exchange proposals</span>
                        <span class="ac-stat-hint-pill">Review &rarr;</span>
                    </div>
                </div>

                <div class="ac-stat-card ac-stat--amber" data-jump-tab="swaps" title="Click to view accepted swaps">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Accepted Swaps</span>
                        <div class="ac-stat-icon-wrap">🤝</div>
                    </div>
                    <div class="ac-stat-val" id="stat-accepted-swaps">—</div>
                    <div class="ac-stat-sub-row">
                        <span>Successful pairings</span>
                        <span class="ac-stat-hint-pill">Inspect &rarr;</span>
                    </div>
                </div>

                <div class="ac-stat-card ac-stat--red" data-jump-tab="reports" title="Click to resolve pending reports">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Pending Reports</span>
                        <div class="ac-stat-icon-wrap">🚨</div>
                    </div>
                    <div class="ac-stat-val" id="stat-pending-reports">—</div>
                    <div class="ac-stat-sub-row">
                        <span style="color:var(--danger);font-weight:700;">Requires attention</span>
                        <span class="ac-stat-hint-pill" style="color:var(--danger);">Resolve &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Timeline -->
            <div class="ac-card">
                <div class="ac-card-header">
                    <div class="ac-card-header-titles">
                        <h3 class="ac-card-title">Real-Time Event Stream</h3>
                        <p class="ac-card-sub">Chronological audit log of user signups, swap interactions, and taxonomy updates</p>
                    </div>
                    <div class="ac-filter-group" id="activity-filter-group">
                        <button type="button" class="ac-pill-btn active" data-filter="all">All Events</button>
                        <button type="button" class="ac-pill-btn" data-filter="user_registered">Registrations</button>
                        <button type="button" class="ac-pill-btn" data-filter="swap_request">Swaps</button>
                        <button type="button" class="ac-pill-btn" data-filter="skill_added">Skills</button>
                    </div>
                </div>

                <div class="ac-activity-container" id="admin-activity-list">
                    <div class="ac-loading"><span class="spinner spinner-primary"></span></div>
                </div>
            </div>
        </section>

        <!-- ========================================================
             2. USERS MANAGEMENT TAB
             ======================================================== -->
        <section class="ac-pane <?= $activeSection !== 'users' ? 'hidden' : '' ?>" id="admin-tab-users">
            <div class="ac-card">
                <div class="ac-card-header">
                    <div class="ac-card-header-titles">
                        <h3 class="ac-card-title">Registered Accounts</h3>
                        <p class="ac-card-sub">Audit user activity, manage roles, and enforce moderation</p>
                    </div>

                    <div class="ac-card-toolbar">
                        <!-- Search Box -->
                        <div class="ac-search-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="user-search-input" class="ac-search-input" placeholder="Search by name, @username, email…">
                            <button type="button" class="ac-search-clear" id="user-search-clear" title="Clear search">&times;</button>
                        </div>

                        <!-- Role & Status Filter Pills -->
                        <div class="ac-filter-group" id="user-filter-group">
                            <button type="button" class="ac-pill-btn active" data-filter="all">All <span class="ac-pill-count" id="count-users-all">0</span></button>
                            <button type="button" class="ac-pill-btn" data-filter="active">Active <span class="ac-pill-count" id="count-users-active">0</span></button>
                            <button type="button" class="ac-pill-btn" data-filter="suspended">Suspended <span class="ac-pill-count" id="count-users-suspended">0</span></button>
                            <button type="button" class="ac-pill-btn" data-filter="admin">Admins <span class="ac-pill-count" id="count-users-admin">0</span></button>
                        </div>
                    </div>
                </div>

                <div class="ac-table-status-bar">
                    <span id="users-result-counter">Loading accounts…</span>
                    <span style="font-size:0.75rem;">Showing live user accounts</span>
                </div>

                <div class="ac-table-container">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>User Profile</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Member Since</th>
                                <th>Skill Portfolios</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-users-table-body">
                            <tr><td colspan="8"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================================
             3. SKILLS TAXONOMY TAB
             ======================================================== -->
        <section class="ac-pane <?= $activeSection !== 'skills' ? 'hidden' : '' ?>" id="admin-tab-skills">
            <div class="ac-card">
                <div class="ac-card-header">
                    <div class="ac-card-header-titles">
                        <h3 class="ac-card-title">Skills Catalog & Taxonomy</h3>
                        <p class="ac-card-sub">Browse, categorize, and expand the master discipline taxonomy</p>
                    </div>

                    <div class="ac-card-toolbar">
                        <!-- Search Box -->
                        <div class="ac-search-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="skill-search-input" class="ac-search-input" placeholder="Search skills or categories…">
                            <button type="button" class="ac-search-clear" id="skill-search-clear" title="Clear search">&times;</button>
                        </div>

                        <!-- Add Skill Button -->
                        <button type="button" class="btn btn-primary btn-sm" id="btn-admin-add-skill">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Add New Skill</span>
                        </button>
                    </div>
                </div>

                <!-- Category Filter Pills Bar -->
                <div style="padding: 0.85rem 1.75rem; border-bottom: 1px solid var(--border-color); background: var(--bg-surface-elevated); display: flex; align-items: center; gap: 0.5rem; overflow-x: auto;">
                    <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-right:0.25rem;">Category:</span>
                    <div class="ac-filter-group" id="skill-category-filter-group">
                        <button type="button" class="ac-pill-btn active" data-category="all">All</button>
                        <button type="button" class="ac-pill-btn" data-category="Programming">Programming</button>
                        <button type="button" class="ac-pill-btn" data-category="Design">Design</button>
                        <button type="button" class="ac-pill-btn" data-category="Creative">Creative</button>
                        <button type="button" class="ac-pill-btn" data-category="Education">Education</button>
                        <button type="button" class="ac-pill-btn" data-category="Business">Business</button>
                    </div>
                </div>

                <div class="ac-table-status-bar">
                    <span id="skills-result-counter">Loading catalog…</span>
                    <span style="font-size:0.75rem;">Master Taxonomy Catalog</span>
                </div>

                <div class="ac-table-container">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Skill Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Market Demand</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-skills-table-body">
                            <tr><td colspan="6"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================================
             4. SWAPS MONITORING TAB
             ======================================================== -->
        <section class="ac-pane <?= $activeSection !== 'swaps' ? 'hidden' : '' ?>" id="admin-tab-swaps">
            <div class="ac-card">
                <div class="ac-card-header">
                    <div class="ac-card-header-titles">
                        <h3 class="ac-card-title">Skill Swap Requests</h3>
                        <p class="ac-card-sub">Audit reciprocal peer interactions and inspect proposal dialogues</p>
                    </div>

                    <div class="ac-card-toolbar">
                        <!-- Search Box for Swaps -->
                        <div class="ac-search-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="swap-search-input" class="ac-search-input" placeholder="Search by peer name or skill…">
                            <button type="button" class="ac-search-clear" id="swap-search-clear" title="Clear search">&times;</button>
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="ac-filter-group" id="swap-filter-pills">
                            <button type="button" class="ac-pill-btn active" data-status="all">All <span class="ac-pill-count" id="count-swaps-all">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="pending">Pending <span class="ac-pill-count" id="count-swaps-pending">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="accepted">Accepted <span class="ac-pill-count" id="count-swaps-accepted">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="rejected">Rejected <span class="ac-pill-count" id="count-swaps-rejected">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="cancelled">Cancelled <span class="ac-pill-count" id="count-swaps-cancelled">0</span></button>
                        </div>
                    </div>
                </div>

                <div class="ac-table-status-bar">
                    <span id="swaps-result-counter">Loading swaps…</span>
                    <span style="font-size:0.75rem;">Click 'View Proposal' to inspect message details</span>
                </div>

                <div class="ac-table-container">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Requester</th>
                                <th>Receiver</th>
                                <th>Skill Reciprocity</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                                <th style="text-align:right;">Proposal</th>
                            </tr>
                        </thead>
                        <tbody id="admin-swaps-table-body">
                            <tr><td colspan="7"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                        </tbody>
                    </table>
                </div>

                <div id="admin-swaps-empty" class="empty-state hidden">
                    <div class="empty-state-icon">🔄</div>
                    <h3 class="empty-state-title">No swap requests found</h3>
                    <p class="empty-state-desc">No skill exchanges match the specified search or filter criteria.</p>
                </div>
            </div>
        </section>

        <!-- ========================================================
             5. MODERATION & REPORTS TAB
             ======================================================== -->
        <section class="ac-pane <?= $activeSection !== 'reports' ? 'hidden' : '' ?>" id="admin-tab-reports">
            <div class="ac-card">
                <div class="ac-card-header">
                    <div class="ac-card-header-titles">
                        <h3 class="ac-card-title">Community Moderation Queue</h3>
                        <p class="ac-card-sub">Investigate reported accounts, review infractions, and enforce platform trust</p>
                    </div>

                    <div class="ac-card-toolbar">
                        <div class="ac-filter-group" id="report-filter-group">
                            <button type="button" class="ac-pill-btn active" data-status="all">All Reports <span class="ac-pill-count" id="count-reports-all">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="pending">Pending <span class="ac-pill-count" id="count-reports-pending">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="resolved">Resolved <span class="ac-pill-count" id="count-reports-resolved">0</span></button>
                            <button type="button" class="ac-pill-btn" data-status="dismissed">Dismissed <span class="ac-pill-count" id="count-reports-dismissed">0</span></button>
                        </div>
                    </div>
                </div>

                <div class="ac-table-status-bar">
                    <span id="reports-result-counter">Loading moderation queue…</span>
                    <span style="font-size:0.75rem;">Direct action buttons allow suspending users on-the-spot</span>
                </div>

                <div class="ac-reports-container" id="admin-reports-list">
                    <div class="ac-loading"><span class="spinner spinner-primary"></span></div>
                </div>
            </div>
        </section>

    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
