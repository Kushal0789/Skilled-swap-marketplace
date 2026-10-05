/**
 * Administrator Panel Client Controller
 * Skill Swap Marketplace
 *
 * Handles: Tab switching (with URL sync), Stats, Activity Feed,
 *          Users (with search), Skills (with search + add),
 *          Swaps (with status filter), Reports (with status filter)
 */

function initAdminConsole() {

    // ----------------------------------------------------------------
    // 1. TAB SWITCHING — sidebar nav + URL sync + banner update
    // ----------------------------------------------------------------
    const navItems = document.querySelectorAll('.ac-nav-item');
    const tabPanes = document.querySelectorAll('.ac-pane');

    const bannerMeta = {
        dashboard: { icon: '📊', title: 'Dashboard',         desc: 'Platform overview and recent activity' },
        users:     { icon: '👥', title: 'User Accounts',     desc: 'Manage and moderate registered members' },
        skills:    { icon: '🎯', title: 'Skills Catalog',    desc: 'Browse and manage the master skill taxonomy' },
        swaps:     { icon: '🔄', title: 'Swap Requests',     desc: 'Monitor all peer-to-peer skill exchanges' },
        reports:   { icon: '🛡️', title: 'Community Reports', desc: 'Review flagged users and content' },
    };

    function activateTab(section) {
        if (!bannerMeta[section]) section = 'dashboard';

        navItems.forEach(b => {
            const matches = b.getAttribute('data-tab') === section;
            b.classList.toggle('active', matches);
        });

        tabPanes.forEach(p => {
            const matches = p.id === `admin-tab-${section}`;
            p.classList.toggle('hidden', !matches);
        });

        // Update banner
        const meta = bannerMeta[section] || bannerMeta.dashboard;
        const bannerIcon  = document.getElementById('ac-banner-icon');
        const bannerTitle = document.getElementById('ac-banner-title');
        const bannerDesc  = document.getElementById('ac-banner-desc');
        if (bannerIcon)  bannerIcon.textContent  = meta.icon;
        if (bannerTitle) bannerTitle.textContent  = meta.title;
        if (bannerDesc)  bannerDesc.textContent   = meta.desc;
    }

    navItems.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const section = btn.getAttribute('data-tab');
            if (!section) return;
            activateTab(section);
            const url = new URL(window.location.href);
            url.searchParams.set('section', section);
            history.pushState({ section }, '', url);
        });
    });

    window.addEventListener('popstate', (e) => {
        const section = (e.state && e.state.section) ||
            new URLSearchParams(window.location.search).get('section') ||
            'dashboard';
        activateTab(section);
    });

    const urlSection = new URLSearchParams(window.location.search).get('section') || 'dashboard';
    activateTab(urlSection);


    // ----------------------------------------------------------------
    // 2. LOAD STATS
    // ----------------------------------------------------------------
    async function loadAdminStats() {
        const res = await window.apiFetch('api/admin/stats.php');
        if (res.success && res.data) {
            const d = res.data;
            setStat('stat-total-users',    d.total_users);
            setStat('stat-active-users',   d.active_users);
            setStat('stat-total-skills',   d.total_skills);
            setStat('stat-total-swaps',    d.total_swaps);
            setStat('stat-accepted-swaps', d.accepted_swaps);
            setStat('stat-pending-reports',d.pending_reports);
        }
    }

    function setStat(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = (val !== undefined && val !== null) ? val : '0';
    }


    // ----------------------------------------------------------------
    // 3. RECENT ACTIVITY FEED
    // ----------------------------------------------------------------
    async function loadRecentActivity() {
        const container = document.getElementById('admin-activity-list');
        if (!container) return;

        const res = await window.apiFetch('api/admin/activity.php');
        if (!res.success || !res.data || res.data.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3 class="empty-state-title">No recent activity</h3>
                    <p class="empty-state-desc">Platform events will appear here once users start joining and swapping.</p>
                </div>`;
            return;
        }

        const typeColor = {
            user_registered: 'var(--primary)',
            swap_request:    'var(--secondary)',
            skill_added:     'var(--accent)'
        };

        container.innerHTML = res.data.map(a => `
            <div class="activity-item">
                <div class="activity-icon">${escapeHtml(a.icon)}</div>
                <div class="activity-body">
                    <div class="activity-label">${escapeHtml(a.label)}</div>
                    <div class="activity-detail">${escapeHtml(a.detail)}</div>
                    <div class="activity-time">${escapeHtml(a.time_ago)}</div>
                </div>
            </div>
        `).join('');
    }


    // ----------------------------------------------------------------
    // 4. USERS TABLE (with client-side search)
    // ----------------------------------------------------------------
    let allUsers = [];

    async function loadAdminUsers() {
        const tableBody = document.getElementById('admin-users-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/users.php');
        if (!res.success) {
            tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--danger);">Failed to load users.</td></tr>`;
            return;
        }

        allUsers = res.data || [];
        renderUsersTable(allUsers);
    }

    function renderUsersTable(users) {
        const tableBody = document.getElementById('admin-users-table-body');
        if (!tableBody) return;

        if (users.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No users found.</td></tr>`;
            return;
        }

        tableBody.innerHTML = users.map(u => `
            <tr data-user-id="${u.id}">
                <td><strong>#${u.id}</strong></td>
                <td>
                    <div style="font-weight:600;">${escapeHtml(u.name)}</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">@${escapeHtml(u.username)}</div>
                </td>
                <td style="font-size:0.85rem;">${escapeHtml(u.email)}</td>
                <td>
                    <span class="status-badge ${u.role === 'admin' ? 'accepted' : 'pending'}">${escapeHtml(u.role)}</span>
                </td>
                <td>
                    <span class="status-badge ${u.status === 'active' ? 'accepted' : 'rejected'}">${escapeHtml(u.status)}</span>
                </td>
                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">${escapeHtml(u.created_formatted || '—')}</td>
                <td style="font-size:0.8rem;">
                    <span style="color:var(--text-secondary);">Offers: <strong>${u.offered_count}</strong></span><br>
                    <span style="color:var(--text-secondary);">Wants: <strong>${u.wanted_count}</strong></span>
                </td>
                <td>
                    ${u.role !== 'admin' ? `
                        <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                            <button class="btn btn-secondary btn-sm btn-admin-toggle-status" data-id="${u.id}">
                                ${u.status === 'active' ? 'Suspend' : 'Activate'}
                            </button>
                            <button class="btn btn-danger btn-sm btn-admin-del-user" data-id="${u.id}" data-name="${escapeHtml(u.name)}">
                                Delete
                            </button>
                        </div>
                    ` : '<span style="font-size:0.75rem;color:var(--text-muted);">Protected</span>'}
                </td>
            </tr>
        `).join('');

        // Suspend / Activate
        tableBody.querySelectorAll('.btn-admin-toggle-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'toggle_status', user_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminUsers();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Operation failed.', 'error');
                }
            });
        });

        // Delete with confirmation
        tableBody.querySelectorAll('.btn-admin-del-user').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id   = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                if (!confirm(`Are you sure you want to permanently delete user "${name}"?\n\nThis cannot be undone.`)) return;

                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'delete', user_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminUsers();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Delete failed.', 'error');
                }
            });
        });
    }

    // Client-side user search
    const userSearchInput = document.getElementById('user-search-input');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', () => {
            const q = userSearchInput.value.trim().toLowerCase();
            if (!q) {
                renderUsersTable(allUsers);
                return;
            }
            const filtered = allUsers.filter(u =>
                u.name.toLowerCase().includes(q) ||
                u.username.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q)
            );
            renderUsersTable(filtered);
        });
    }


    // ----------------------------------------------------------------
    // 5. SKILLS TABLE (with client-side search + add)
    // ----------------------------------------------------------------
    let allSkills = [];

    async function loadAdminSkills() {
        const tableBody = document.getElementById('admin-skills-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/skills.php');
        if (!res.success) return;

        allSkills = res.data || [];
        renderSkillsTable(allSkills);
    }

    function renderSkillsTable(skills) {
        const tableBody = document.getElementById('admin-skills-table-body');
        if (!tableBody) return;

        if (skills.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No skills found.</td></tr>`;
            return;
        }

        tableBody.innerHTML = skills.map(s => `
            <tr>
                <td><strong>#${s.id}</strong></td>
                <td><strong>${escapeHtml(s.name)}</strong></td>
                <td><span class="skill-badge">${escapeHtml(s.category)}</span></td>
                <td style="font-size:0.82rem;color:var(--text-secondary);max-width:200px;">${escapeHtml(s.description || '—')}</td>
                <td style="font-size:0.8rem;white-space:nowrap;">
                    Offered: <strong>${s.offered_by}</strong><br>
                    Wanted: <strong>${s.wanted_by}</strong>
                </td>
                <td>
                    <button class="btn btn-danger btn-sm btn-admin-del-skill"
                            data-id="${s.id}" data-name="${escapeHtml(s.name)}">
                        Delete
                    </button>
                </td>
            </tr>
        `).join('');

        tableBody.querySelectorAll('.btn-admin-del-skill').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id   = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                if (!confirm(`Delete skill "${name}" from the master catalog?\n\nThis will also remove it from all user profiles.`)) return;

                const res = await window.apiFetch('api/admin/skills.php', {
                    method: 'POST',
                    body: { action: 'delete', skill_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminSkills();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Delete failed.', 'error');
                }
            });
        });
    }

    // Client-side skill search
    const skillSearchInput = document.getElementById('skill-search-input');
    if (skillSearchInput) {
        skillSearchInput.addEventListener('input', () => {
            const q = skillSearchInput.value.trim().toLowerCase();
            if (!q) {
                renderSkillsTable(allSkills);
                return;
            }
            const filtered = allSkills.filter(s =>
                s.name.toLowerCase().includes(q) ||
                s.category.toLowerCase().includes(q)
            );
            renderSkillsTable(filtered);
        });
    }

    // Add Skill Modal
    const addSkillBtn = document.getElementById('btn-admin-add-skill');
    if (addSkillBtn) {
        addSkillBtn.addEventListener('click', () => {
            const modalHtml = `
                <form id="admin-add-skill-form">
                    <div class="form-group">
                        <label class="form-label">Skill Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. Flutter, Rust, Watercolour">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="Programming">Programming</option>
                            <option value="Design">Design</option>
                            <option value="Creative">Creative</option>
                            <option value="Education">Education</option>
                            <option value="Business">Business</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" rows="3" placeholder="Brief description of what this skill covers…"></textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                        <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Skill</button>
                    </div>
                </form>
            `;
            window.openModal('Add Skill to Catalog', modalHtml);

            document.getElementById('admin-add-skill-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                const res = await window.apiFetch('api/admin/skills.php', {
                    method: 'POST',
                    body: {
                        action: 'add',
                        name: form.querySelector('[name="name"]').value.trim(),
                        category: form.querySelector('[name="category"]').value,
                        description: form.querySelector('[name="description"]').value.trim()
                    }
                });
                if (res.success) {
                    window.closeModal();
                    window.showToast(res.message, 'success');
                    await loadAdminSkills();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Failed to add skill.', 'error');
                }
            });
        });
    }


    // ----------------------------------------------------------------
    // 6. SWAPS TABLE (with status filter)
    // ----------------------------------------------------------------
    let allSwaps      = [];
    let currentSwapFilter = 'all';

    async function loadAdminSwaps() {
        const tableBody = document.getElementById('admin-swaps-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/swaps.php');
        if (!res.success) {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--danger);">Failed to load swaps.</td></tr>`;
            return;
        }
        allSwaps = res.data || [];
        renderSwapsTable(currentSwapFilter);
    }

    function renderSwapsTable(filterStatus) {
        const tableBody = document.getElementById('admin-swaps-table-body');
        const emptyBox  = document.getElementById('admin-swaps-empty');
        if (!tableBody) return;

        const swaps = filterStatus === 'all'
            ? allSwaps
            : allSwaps.filter(s => s.status === filterStatus);

        if (swaps.length === 0) {
            tableBody.innerHTML = '';
            if (emptyBox) emptyBox.classList.remove('hidden');
            return;
        }
        if (emptyBox) emptyBox.classList.add('hidden');

        const statusClass = {
            pending:   'pending',
            accepted:  'accepted',
            rejected:  'rejected',
            cancelled: 'cancelled'
        };

        tableBody.innerHTML = swaps.map(s => `
            <tr>
                <td><strong>#${s.id}</strong></td>
                <td>
                    <div style="font-weight:600;">${escapeHtml(s.sender_name)}</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">@${escapeHtml(s.sender_username)}</div>
                </td>
                <td>
                    <div style="font-weight:600;">${escapeHtml(s.receiver_name)}</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">@${escapeHtml(s.receiver_username)}</div>
                </td>
                <td><span class="skill-badge">${escapeHtml(s.offered_skill)}</span></td>
                <td><span class="skill-badge" style="background:var(--bg-surface-elevated);">${escapeHtml(s.requested_skill)}</span></td>
                <td>
                    <span class="status-badge ${statusClass[s.status] || ''}">${escapeHtml(s.status)}</span>
                </td>
                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">${escapeHtml(s.time_ago)}</td>
            </tr>
        `).join('');
    }

    // Swap filter buttons
    document.querySelectorAll('.swap-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.swap-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSwapFilter = btn.getAttribute('data-status');
            renderSwapsTable(currentSwapFilter);
        });
    });


    // ----------------------------------------------------------------
    // 7. REPORTS (with status filter)
    // ----------------------------------------------------------------
    let allReports         = [];
    let currentReportFilter = 'all';

    async function loadAdminReports() {
        const container = document.getElementById('admin-reports-list');
        if (!container) return;

        const res = await window.apiFetch('api/admin/reports.php');
        if (!res.success) {
            container.innerHTML = `<p style="color:var(--danger);">Failed to load reports.</p>`;
            return;
        }
        allReports = res.data || [];
        renderReports(currentReportFilter);
    }

    function renderReports(filterStatus) {
        const container = document.getElementById('admin-reports-list');
        if (!container) return;

        const reports = filterStatus === 'all'
            ? allReports
            : allReports.filter(r => r.status === filterStatus);

        // Update sidebar badge with pending count
        const pendingCount = allReports.filter(r => r.status === 'pending').length;
        const badge = document.getElementById('sidebar-reports-badge');
        if (badge) {
            badge.textContent = pendingCount;
            badge.classList.toggle('hidden', pendingCount === 0);
        }

        if (reports.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🛡️</div>
                    <h3 class="empty-state-title">No reports found</h3>
                    <p class="empty-state-desc">The community is in good standing!</p>
                </div>`;
            return;
        }

        const statusClass = { pending: 'pending', resolved: 'accepted', dismissed: 'cancelled' };

        container.innerHTML = reports.map(r => `
            <div class="ac-report-card">
                <div class="ac-report-header">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span class="status-badge ${statusClass[r.status] || 'pending'}">${escapeHtml(r.status)}</span>
                        <strong style="font-size:0.9rem;">${escapeHtml(r.report_type)}</strong>
                    </div>
                    <span style="font-size:0.78rem;color:var(--text-muted);">${escapeHtml(r.time_ago)}</span>
                </div>
                <div class="ac-report-meta">
                    Reporter: <strong>${escapeHtml(r.reporter_name)}</strong> (@${escapeHtml(r.reporter_username)})
                    &rarr; Reported: <strong style="color:var(--danger);">${escapeHtml(r.reported_name)}</strong>
                    (@${escapeHtml(r.reported_username)})
                </div>
                <div class="ac-report-reason">&ldquo;${escapeHtml(r.reason)}&rdquo;</div>
                ${r.status === 'pending' ? `
                    <div class="ac-report-actions">
                        <button class="btn btn-secondary btn-sm btn-report-action" data-id="${r.id}" data-status="dismissed">Dismiss</button>
                        <button class="btn btn-danger btn-sm btn-report-action" data-id="${r.id}" data-status="resolved">Mark Resolved</button>
                    </div>
                ` : ''}
            </div>
        `).join('');

        container.querySelectorAll('.btn-report-action').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id     = btn.getAttribute('data-id');
                const status = btn.getAttribute('data-status');
                const res = await window.apiFetch('api/admin/reports.php', {
                    method: 'POST',
                    body: { report_id: id, status: status }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminReports();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Failed to update report.', 'error');
                }
            });
        });
    }

    // Report filter buttons
    document.querySelectorAll('.report-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.report-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentReportFilter = btn.getAttribute('data-status');
            renderReports(currentReportFilter);
        });
    });


    // ----------------------------------------------------------------
    // 8. UTILITY: HTML Escape
    // ----------------------------------------------------------------
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    // ----------------------------------------------------------------
    // 9. INITIALISE ALL SECTIONS
    // ----------------------------------------------------------------
    loadAdminStats();
    loadRecentActivity();
    loadAdminUsers();
    loadAdminSkills();
    loadAdminSwaps();
    loadAdminReports();

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminConsole);
} else {
    initAdminConsole();
}
