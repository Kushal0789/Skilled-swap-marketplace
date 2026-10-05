<?php
/**
 * Administrator Console (Presentation Layer)
 * Skill Swap Marketplace
 *
 * Sections (via ?section=): dashboard | users | skills | swaps | reports
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
    'dashboard' => ['icon' => '📊', 'title' => 'Dashboard',         'desc' => 'Platform overview and recent activity'],
    'users'     => ['icon' => '👥', 'title' => 'User Accounts',     'desc' => 'Manage and moderate registered members'],
    'skills'    => ['icon' => '🎯', 'title' => 'Skills Catalog',    'desc' => 'Browse and manage the master skill taxonomy'],
    'swaps'     => ['icon' => '🔄', 'title' => 'Swap Requests',     'desc' => 'Monitor all peer-to-peer skill exchanges'],
    'reports'   => ['icon' => '🛡️', 'title' => 'Community Reports', 'desc' => 'Review flagged users and content'],
];
$currentMeta = $sectionMeta[$activeSection] ?? $sectionMeta['dashboard'];

$pageTitle = 'Admin Console · SkillSwap';
$pageScript = 'admin.js';
include __DIR__ . '/includes/header.php';
?>

<div class="ac-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <aside class="ac-sidebar">
        <div class="ac-sidebar-brand">
            <span class="ac-brand-icon">⚙️</span>
            <div>
                <div class="ac-brand-title">Control Center</div>
                <div class="ac-brand-sub">Administrator Panel</div>
            </div>
        </div>

        <nav class="ac-sidenav">
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
            </a>
            <a href="admin.php?section=skills" class="ac-nav-item <?= $activeSection === 'skills' ? 'active' : '' ?>" data-tab="skills">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </span>
                <span>Skills</span>
            </a>
            <a href="admin.php?section=swaps" class="ac-nav-item <?= $activeSection === 'swaps' ? 'active' : '' ?>" data-tab="swaps">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </span>
                <span>Swaps</span>
            </a>
            <a href="admin.php?section=reports" class="ac-nav-item <?= $activeSection === 'reports' ? 'active' : '' ?>" data-tab="reports">
                <span class="ac-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <span>Reports</span>
                <span class="ac-nav-badge hidden" id="sidebar-reports-badge">0</span>
            </a>
        </nav>

    </aside>

    <!-- ===================== MAIN CONTENT ===================== -->
    <main class="ac-main">

        <!-- Page Banner -->
        <div class="ac-banner" id="ac-page-banner">
            <div class="ac-banner-content">
                <div class="ac-banner-icon" id="ac-banner-icon"><?= $currentMeta['icon'] ?></div>
                <div>
                    <h1 class="ac-banner-title" id="ac-banner-title"><?= htmlspecialchars($currentMeta['title']) ?></h1>
                    <p class="ac-banner-desc" id="ac-banner-desc"><?= htmlspecialchars($currentMeta['desc']) ?></p>
                </div>
            </div>
            <div class="ac-banner-meta">
                <span class="ac-status-dot"></span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);">System Online</span>
            </div>
        </div>

        <!-- =========== DASHBOARD =========== -->
        <div class="ac-pane <?= $activeSection !== 'dashboard' ? 'hidden' : '' ?>" id="admin-tab-dashboard">

            <!-- Stat Cards -->
            <div class="ac-stats-grid" id="admin-stats-grid">
                <div class="ac-stat-card ac-stat--blue">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Total Users</span>
                        <span class="ac-stat-ico">👥</span>
                    </div>
                    <div class="ac-stat-val" id="stat-total-users">—</div>
                    <div class="ac-stat-sub">Registered accounts</div>
                </div>
                <div class="ac-stat-card ac-stat--green">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Active Users</span>
                        <span class="ac-stat-ico">✅</span>
                    </div>
                    <div class="ac-stat-val" id="stat-active-users">—</div>
                    <div class="ac-stat-sub">In good standing</div>
                </div>
                <div class="ac-stat-card ac-stat--purple">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Skills Catalog</span>
                        <span class="ac-stat-ico">🎯</span>
                    </div>
                    <div class="ac-stat-val" id="stat-total-skills">—</div>
                    <div class="ac-stat-sub">Master taxonomy</div>
                </div>
                <div class="ac-stat-card ac-stat--teal">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Total Swaps</span>
                        <span class="ac-stat-ico">🔄</span>
                    </div>
                    <div class="ac-stat-val" id="stat-total-swaps">—</div>
                    <div class="ac-stat-sub">All requests</div>
                </div>
                <div class="ac-stat-card ac-stat--amber">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Accepted Swaps</span>
                        <span class="ac-stat-ico">🤝</span>
                    </div>
                    <div class="ac-stat-val" id="stat-accepted-swaps">—</div>
                    <div class="ac-stat-sub">Connected pairs</div>
                </div>
                <div class="ac-stat-card ac-stat--red">
                    <div class="ac-stat-top">
                        <span class="ac-stat-label">Pending Reports</span>
                        <span class="ac-stat-ico">🚨</span>
                    </div>
                    <div class="ac-stat-val" id="stat-pending-reports">—</div>
                    <div class="ac-stat-sub">Awaiting review</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="ac-card" style="margin-top:1.75rem;">
                <div class="ac-card-header">
                    <div>
                        <h3 class="ac-card-title">Recent Activity</h3>
                        <p class="ac-card-sub">Latest platform events</p>
                    </div>
                </div>
                <div id="admin-activity-list">
                    <div class="ac-loading"><span class="spinner spinner-primary"></span></div>
                </div>
            </div>
        </div>

        <!-- =========== USERS =========== -->
        <div class="ac-pane <?= $activeSection !== 'users' ? 'hidden' : '' ?>" id="admin-tab-users">
            <div class="ac-card" style="overflow-x:auto;">
                <div class="ac-card-header">
                    <div>
                        <h3 class="ac-card-title">User Accounts</h3>
                        <p class="ac-card-sub">Manage registered members</p>
                    </div>
                    <div class="ac-search-wrap">
                        <svg class="ac-search-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="user-search-input" class="ac-search-input" placeholder="Search users…">
                    </div>
                </div>
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Skills</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="admin-users-table-body">
                        <tr><td colspan="8"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =========== SKILLS =========== -->
        <div class="ac-pane <?= $activeSection !== 'skills' ? 'hidden' : '' ?>" id="admin-tab-skills">
            <div class="ac-card" style="overflow-x:auto;">
                <div class="ac-card-header">
                    <div>
                        <h3 class="ac-card-title">Skills Catalog</h3>
                        <p class="ac-card-sub">Categorized skill taxonomy</p>
                    </div>
                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                        <div class="ac-search-wrap">
                            <svg class="ac-search-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="skill-search-input" class="ac-search-input" placeholder="Search skills…">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-admin-add-skill">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Add Skill
                        </button>
                    </div>
                </div>
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Usage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="admin-skills-table-body">
                        <tr><td colspan="6"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =========== SWAPS =========== -->
        <div class="ac-pane <?= $activeSection !== 'swaps' ? 'hidden' : '' ?>" id="admin-tab-swaps">
            <div class="ac-card" style="overflow-x:auto;">
                <div class="ac-card-header">
                    <div>
                        <h3 class="ac-card-title">Swap Requests</h3>
                        <p class="ac-card-sub">Monitor all skill exchange requests</p>
                    </div>
                    <div class="ac-filter-pills" id="swap-filter-pills">
                        <button type="button" class="swap-filter-btn active" data-status="all">All</button>
                        <button type="button" class="swap-filter-btn" data-status="pending">Pending</button>
                        <button type="button" class="swap-filter-btn" data-status="accepted">Accepted</button>
                        <button type="button" class="swap-filter-btn" data-status="rejected">Rejected</button>
                        <button type="button" class="swap-filter-btn" data-status="cancelled">Cancelled</button>
                    </div>
                </div>
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Requester</th>
                            <th>Receiver</th>
                            <th>Offered Skill</th>
                            <th>Requested Skill</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="admin-swaps-table-body">
                        <tr><td colspan="7"><div class="ac-loading"><span class="spinner spinner-primary"></span></div></td></tr>
                    </tbody>
                </table>
                <div id="admin-swaps-empty" class="empty-state hidden">
                    <div class="empty-state-icon">🔄</div>
                    <h3 class="empty-state-title">No swaps found</h3>
                    <p class="empty-state-desc">No swap requests match the selected filter.</p>
                </div>
            </div>
        </div>

        <!-- =========== REPORTS =========== -->
        <div class="ac-pane <?= $activeSection !== 'reports' ? 'hidden' : '' ?>" id="admin-tab-reports">
            <div class="ac-card">
                <div class="ac-card-header">
                    <div>
                        <h3 class="ac-card-title">Community Reports</h3>
                        <p class="ac-card-sub">Flagged users and content</p>
                    </div>
                    <div class="ac-filter-pills">
                        <button type="button" class="report-filter-btn active" data-status="all">All</button>
                        <button type="button" class="report-filter-btn" data-status="pending">Pending</button>
                        <button type="button" class="report-filter-btn" data-status="resolved">Resolved</button>
                        <button type="button" class="report-filter-btn" data-status="dismissed">Dismissed</button>
                    </div>
                </div>
                <div id="admin-reports-list">
                    <div class="ac-loading"><span class="spinner spinner-primary"></span></div>
                </div>
            </div>
        </div>

    </main><!-- /.ac-main -->
</div><!-- /.ac-layout -->

<style>
/* ============================================================
   Admin Console — Full UI (sidebar layout)
   ============================================================ */

/* Layout shell */
.ac-layout {
    display: flex;
    min-height: calc(100vh - 70px);
    max-width: 1440px;
    margin: 0 auto;
    gap: 0;
}

/* ── Sidebar ─────────────────────────────────── */
.ac-sidebar {
    width: 220px;
    flex-shrink: 0;
    background: var(--bg-surface);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0;
    position: sticky;
    top: 70px;
    height: calc(100vh - 70px);
    overflow-y: auto;
}

.ac-sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1rem;
}

.ac-brand-icon { font-size: 1.6rem; }
.ac-brand-title { font-weight: 800; font-size: 0.95rem; line-height: 1.2; }
.ac-brand-sub { font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.03em; }

.ac-sidenav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 0 0.75rem;
    flex: 1;
}

.ac-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.85rem;
    border: none;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: left;
    width: 100%;
    position: relative;
    text-decoration: none;
    box-sizing: border-box;
}

.ac-nav-item:hover {
    background: var(--bg-surface-elevated);
    color: var(--text-primary);
    text-decoration: none;
}

.ac-nav-item.active {
    background: var(--primary-glow, rgba(99,102,241,0.12));
    color: var(--primary);
    text-decoration: none;
}

.ac-nav-item.active .ac-nav-icon svg { stroke: var(--primary); }

.ac-nav-icon {
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    opacity: 0.75;
}
.ac-nav-item.active .ac-nav-icon { opacity: 1; }

.ac-nav-badge {
    margin-left: auto;
    background: var(--danger);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 999px;
    min-width: 18px;
    text-align: center;
}

.ac-sidebar-footer {
    padding: 1rem 1rem 0;
    border-top: 1px solid var(--border-color);
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ac-sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all var(--transition-fast);
}
.ac-sidebar-link:hover { background: var(--bg-surface-elevated); color: var(--text-primary); }
.ac-sidebar-link--danger { color: var(--danger); }
.ac-sidebar-link--danger:hover { background: rgba(239,68,68,0.08); color: var(--danger); }

/* ── Main area ───────────────────────────────── */
.ac-main {
    flex: 1;
    min-width: 0;
    padding: 2rem 2rem 3rem;
    overflow-x: hidden;
}

/* Banner */
.ac-banner {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary, #7c3aed) 100%);
    border-radius: var(--radius-xl);
    padding: 1.75rem 2rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 8px 30px rgba(99,102,241,0.25);
}

.ac-banner-content { display: flex; align-items: center; gap: 1.25rem; }
.ac-banner-icon { font-size: 2.5rem; line-height: 1; }
.ac-banner-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0 0 0.2rem; line-height: 1.2; }
.ac-banner-desc { font-size: 0.85rem; color: rgba(255,255,255,0.75); margin: 0; }

.ac-banner-meta { display: flex; align-items: center; gap: 0.5rem; }
.ac-status-dot {
    width: 8px; height: 8px;
    background: #4ade80;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(74,222,128,0.3);
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0%, 100% { box-shadow: 0 0 0 3px rgba(74,222,128,0.3); }
    50% { box-shadow: 0 0 0 6px rgba(74,222,128,0.1); }
}

/* Tab pane visibility */
.ac-pane { }
.ac-pane.hidden { display: none !important; }

/* ── Stat Cards ──────────────────────────────── */
.ac-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
    gap: 1.1rem;
    margin-bottom: 0;
}

.ac-stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.4rem 1.25rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: default;
}
.ac-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.ac-stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

.ac-stat--blue::before  { background: linear-gradient(90deg, #6366f1, #818cf8); }
.ac-stat--green::before { background: linear-gradient(90deg, #10b981, #34d399); }
.ac-stat--purple::before{ background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.ac-stat--teal::before  { background: linear-gradient(90deg, #0891b2, #22d3ee); }
.ac-stat--amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.ac-stat--red::before   { background: linear-gradient(90deg, #dc2626, #f87171); }

.ac-stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
.ac-stat-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }
.ac-stat-ico { font-size: 1.25rem; opacity: 0.75; }

.ac-stat-val {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.35rem;
    color: var(--text-primary);
    font-family: var(--font-heading, inherit);
}
.ac-stat-sub { font-size: 0.72rem; color: var(--text-muted); }

/* ── Card ────────────────────────────────────── */
.ac-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 0;
    overflow: hidden;
}

.ac-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--border-color);
}

.ac-card-title { font-size: 1.05rem; font-weight: 800; margin: 0 0 0.1rem; }
.ac-card-sub { font-size: 0.78rem; color: var(--text-muted); margin: 0; }

/* ── Table ───────────────────────────────────── */
.ac-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.ac-table th {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-muted);
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    background: var(--bg-surface-elevated);
    text-align: left;
}

.ac-table td {
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
    color: var(--text-primary);
}

.ac-table tbody tr:last-child td { border-bottom: none; }
.ac-table tbody tr { transition: background var(--transition-fast); }
.ac-table tbody tr:hover td { background: var(--bg-surface-elevated); }

/* ── Search ──────────────────────────────────── */
.ac-search-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 0 0.75rem;
}
.ac-search-ico { color: var(--text-muted); flex-shrink: 0; }
.ac-search-input {
    background: transparent;
    border: none;
    outline: none;
    font-size: 0.875rem;
    color: var(--text-primary);
    padding: 0.5rem 0;
    min-width: 180px;
}
.ac-search-input::placeholder { color: var(--text-muted); }

/* ── Filter Pills ────────────────────────────── */
.ac-filter-pills { display: flex; gap: 0.35rem; flex-wrap: wrap; }

.swap-filter-btn,
.report-filter-btn {
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    padding: 4px 13px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
}
.swap-filter-btn:hover,
.report-filter-btn:hover { border-color: var(--primary); color: var(--primary); }
.swap-filter-btn.active,
.report-filter-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }

/* ── Activity Feed ───────────────────────────── */
#admin-activity-list { padding: 0 1.75rem 1.25rem; }

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}
.activity-item:last-child { border-bottom: none; }

.activity-icon {
    flex-shrink: 0;
    width: 2.4rem; height: 2.4rem;
    background: var(--bg-surface-elevated);
    border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}

.activity-body { flex: 1; min-width: 0; }
.activity-label { font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.activity-detail { font-size: 0.88rem; color: var(--text-primary); margin: 0.15rem 0 0.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.activity-time { font-size: 0.73rem; color: var(--text-muted); }

/* Loading spinner cell */
.ac-loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2.5rem;
}

/* ── Reports cards ───────────────────────────── */
#admin-reports-list { padding: 1.25rem 1.75rem; }

.ac-report-card {
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    margin-bottom: 0.85rem;
    background: var(--bg-surface-elevated);
    transition: box-shadow 0.15s;
}
.ac-report-card:last-child { margin-bottom: 0; }
.ac-report-card:hover { box-shadow: var(--shadow-sm); }

.ac-report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}
.ac-report-meta {
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
    line-height: 1.7;
}
.ac-report-reason {
    background: var(--bg-surface);
    padding: 0.75rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.84rem;
    color: var(--text-secondary);
    border-left: 3px solid var(--border-color);
    margin-bottom: 0.75rem;
    font-style: italic;
}
.ac-report-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

/* ── Responsive ──────────────────────────────── */
@media (max-width: 900px) {
    .ac-layout { flex-direction: column; }
    .ac-sidebar {
        width: 100%;
        height: auto;
        position: static;
        flex-direction: row;
        flex-wrap: wrap;
        padding: 0.75rem;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }
    .ac-sidebar-brand { padding-bottom: 0; border-bottom: none; margin-bottom: 0; }
    .ac-sidenav { flex-direction: row; flex-wrap: wrap; padding: 0; gap: 4px; }
    .ac-sidebar-footer { flex-direction: row; border-top: none; padding: 0; margin-top: 0; }
    .ac-nav-item { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
    .ac-main { padding: 1.25rem 1rem 2rem; }
    .ac-table { font-size: 0.8rem; }
    .ac-table th, .ac-table td { padding: 0.65rem 0.75rem; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
