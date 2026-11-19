/**
 * Administrator Panel Client Controller
 * Skill Swap Marketplace
 *
 * Modernized UI/UX controller for executive dashboard, real-time filters,
 * custom modal confirmations, proposal inspector, and moderation actions.
 */

function initAdminConsole() {
    'use strict';

    // ----------------------------------------------------------------
    // 1. TAB SWITCHING — sidebar nav + URL sync + header update
    // ----------------------------------------------------------------
    const navItems = document.querySelectorAll('.ac-nav-item');
    const tabPanes = document.querySelectorAll('.ac-pane');

    const bannerMeta = {
        dashboard: { icon: '📊', title: 'Dashboard Overview',   desc: 'Platform metrics, health telemetry, and recent events', breadcrumb: 'Console / Overview' },
        users:     { icon: '👥', title: 'User Management',      desc: 'Audit, moderate, and manage registered marketplace accounts', breadcrumb: 'Console / Users' },
        skills:    { icon: '🎯', title: 'Skills Taxonomy',      desc: 'Curate and organize the master skill exchange catalog', breadcrumb: 'Console / Skills' },
        swaps:     { icon: '🔄', title: 'Swap Requests Hub',    desc: 'Monitor reciprocal and one-way peer exchanges in real time', breadcrumb: 'Console / Swaps' },
        reports:   { icon: '🛡️', title: 'Moderation & Reports', desc: 'Review flagged members and enforce community safety guidelines', breadcrumb: 'Console / Moderation' },
    };

    function activateTab(section, pushState = false) {
        if (!bannerMeta[section]) section = 'dashboard';

        navItems.forEach(b => {
            const matches = b.getAttribute('data-tab') === section;
            b.classList.toggle('active', matches);
        });

        tabPanes.forEach(p => {
            const matches = p.id === `admin-tab-${section}`;
            p.classList.toggle('hidden', !matches);
        });

        // Update header banner titles and breadcrumb
        const meta = bannerMeta[section] || bannerMeta.dashboard;
        const bannerIcon       = document.getElementById('ac-banner-icon');
        const bannerTitle      = document.getElementById('ac-banner-title');
        const bannerDesc       = document.getElementById('ac-banner-desc');
        const bannerBreadcrumb = document.getElementById('ac-banner-breadcrumb');

        if (bannerIcon)       bannerIcon.textContent       = meta.icon;
        if (bannerTitle)      bannerTitle.textContent      = meta.title;
        if (bannerDesc)       bannerDesc.textContent       = meta.desc;
        if (bannerBreadcrumb) bannerBreadcrumb.textContent = meta.breadcrumb;

        if (pushState) {
            const url = new URL(window.location.href);
            url.searchParams.set('section', section);
            history.pushState({ section }, '', url);
        }
    }

    navItems.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const section = btn.getAttribute('data-tab');
            if (!section) return;
            activateTab(section, true);
        });
    });

    window.addEventListener('popstate', (e) => {
        const section = (e.state && e.state.section) ||
            new URLSearchParams(window.location.search).get('section') ||
            'dashboard';
        activateTab(section, false);
    });

    // Jump to tab from Stat Cards
    document.querySelectorAll('[data-jump-tab]').forEach(card => {
        card.addEventListener('click', () => {
            const tab = card.getAttribute('data-jump-tab');
            if (tab) activateTab(tab, true);
        });
    });

    // Quick add skill button in header
    const quickAddBtn = document.getElementById('btn-quick-add-skill-top');
    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', () => {
            openAddSkillModal();
        });
    }

    // Refresh Data button
    const refreshBtn = document.getElementById('btn-admin-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            refreshBtn.classList.add('is-spinning');
            await Promise.all([
                loadAdminStats(),
                loadRecentActivity(),
                loadAdminUsers(),
                loadAdminSkills(),
                loadAdminSwaps(),
                loadAdminReports()
            ]);
            setTimeout(() => {
                refreshBtn.classList.remove('is-spinning');
                window.showToast('Platform telemetry refreshed.', 'info');
            }, 500);
        });
    }


    // ----------------------------------------------------------------
    // 2. LOAD STATS & BADGES
    // ----------------------------------------------------------------
    async function loadAdminStats() {
        const res = await window.apiFetch('api/admin/stats.php');
        if (res.success && res.data) {
            const d = res.data;
            setStat('stat-total-users',     d.total_users);
            setStat('stat-active-users',    d.active_users);
            setStat('stat-total-skills',    d.total_skills);
            setStat('stat-total-swaps',     d.total_swaps);
            setStat('stat-accepted-swaps',  d.accepted_swaps);
            setStat('stat-pending-reports', d.pending_reports);

            // Update sidebar navigation badges
            setBadge('sidebar-users-badge', d.total_users);
            setBadge('sidebar-skills-badge', d.total_skills);
            setBadge('sidebar-swaps-badge', d.total_swaps);

            const reportsBadge = document.getElementById('sidebar-reports-badge');
            if (reportsBadge) {
                const count = d.pending_reports || 0;
                reportsBadge.textContent = count;
                reportsBadge.classList.toggle('hidden', count === 0);
            }
        }
    }

    function setStat(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = (val !== undefined && val !== null) ? Number(val).toLocaleString() : '0';
    }

    function setBadge(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = (val !== undefined && val !== null) ? Number(val).toLocaleString() : '—';
    }


    // ----------------------------------------------------------------
    // 3. RECENT ACTIVITY FEED (with live event filter)
    // ----------------------------------------------------------------
    let allActivities = [];
    let currentActivityFilter = 'all';

    async function loadRecentActivity() {
        const container = document.getElementById('admin-activity-list');
        if (!container) return;

        const res = await window.apiFetch('api/admin/activity.php');
        if (!res.success || !res.data || res.data.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3 class="empty-state-title">No recent activity</h3>
                    <p class="empty-state-desc">Platform events will stream here as community members join and swap skills.</p>
                </div>`;
            return;
        }

        allActivities = res.data;
        renderActivityFeed();
    }

    function renderActivityFeed() {
        const container = document.getElementById('admin-activity-list');
        if (!container) return;

        const filtered = currentActivityFilter === 'all'
            ? allActivities
            : allActivities.filter(a => a.type === currentActivityFilter);

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding:2.5rem 1rem;">
                    <div class="empty-state-icon">🔍</div>
                    <h4 style="margin:0.5rem 0 0.25rem;">No matching events</h4>
                    <p class="empty-state-desc">No activity recorded for this category yet.</p>
                </div>`;
            return;
        }

        const badgeMap = {
            user_registered: { label: 'Account Registration', class: 'ac-activity-badge--user' },
            swap_request:    { label: 'Skill Swap Exchange',  class: 'ac-activity-badge--swap' },
            skill_added:     { label: 'Taxonomy Catalog',     class: 'ac-activity-badge--skill' }
        };

        container.innerHTML = filtered.map(a => {
            const badge = badgeMap[a.type] || { label: a.label, class: '' };
            return `
                <div class="ac-activity-item">
                    <div class="ac-activity-icon-badge">${escapeHtml(a.icon)}</div>
                    <div class="ac-activity-body">
                        <div class="ac-activity-top-line">
                            <span class="ac-activity-badge ${badge.class}">${escapeHtml(badge.label)}</span>
                            <span class="ac-activity-time">${escapeHtml(a.time_ago)}</span>
                        </div>
                        <div class="ac-activity-detail">${escapeHtml(a.detail)}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Activity filter pills
    document.querySelectorAll('#activity-filter-group .ac-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#activity-filter-group .ac-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentActivityFilter = btn.getAttribute('data-filter') || 'all';
            renderActivityFeed();
        });
    });


    // ----------------------------------------------------------------
    // 4. USERS MANAGEMENT TABLE (Search, Filters, Role, Status)
    // ----------------------------------------------------------------
    let allUsers = [];
    let currentUserFilter = 'all';

    async function loadAdminUsers() {
        const tableBody = document.getElementById('admin-users-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/users.php');
        if (!res.success) {
            tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--danger);padding:2rem;">Failed to load users.</td></tr>`;
            return;
        }

        allUsers = res.data || [];
        updateUserFilterCounts();
        filterAndRenderUsers();
    }

    function updateUserFilterCounts() {
        const total = allUsers.length;
        const active = allUsers.filter(u => u.status === 'active').length;
        const suspended = allUsers.filter(u => u.status === 'suspended').length;
        const admin = allUsers.filter(u => u.role === 'admin').length;

        setText('count-users-all', total);
        setText('count-users-active', active);
        setText('count-users-suspended', suspended);
        setText('count-users-admin', admin);
    }

    function filterAndRenderUsers() {
        const searchInput = document.getElementById('user-search-input');
        const q = (searchInput ? searchInput.value : '').trim().toLowerCase();

        let list = allUsers;

        // Apply filter pill
        if (currentUserFilter === 'active') {
            list = list.filter(u => u.status === 'active');
        } else if (currentUserFilter === 'suspended') {
            list = list.filter(u => u.status === 'suspended');
        } else if (currentUserFilter === 'admin') {
            list = list.filter(u => u.role === 'admin');
        }

        // Apply search query
        if (q) {
            list = list.filter(u =>
                (u.name && u.name.toLowerCase().includes(q)) ||
                (u.username && u.username.toLowerCase().includes(q)) ||
                (u.email && u.email.toLowerCase().includes(q))
            );
        }

        renderUsersTable(list);

        // Update status bar
        const counterEl = document.getElementById('users-result-counter');
        if (counterEl) {
            counterEl.innerHTML = `Showing <strong>${list.length}</strong> of <strong>${allUsers.length}</strong> total registered accounts`;
        }
    }

    function renderUsersTable(users) {
        const tableBody = document.getElementById('admin-users-table-body');
        if (!tableBody) return;

        if (users.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align:center;padding:3rem 1.5rem;color:var(--text-muted);">
                        <div style="font-size:2rem;margin-bottom:0.5rem;">🔍</div>
                        <div style="font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">No matching accounts found</div>
                        <div style="font-size:0.85rem;">Try modifying your search or filter options.</div>
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = users.map(u => {
            const avatarUrl = u.avatar_url || 'assets/images/default-avatar.svg';
            const isAdmin = u.role === 'admin';
            const isActive = u.status === 'active';

            return `
            <tr data-user-id="${u.id}">
                <td><strong style="color:var(--text-muted);">#${u.id}</strong></td>
                <td>
                    <div class="ac-user-cell">
                        <img class="ac-user-avatar" src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(u.name)}" loading="lazy">
                        <div class="ac-user-names">
                            <span class="ac-user-display-name">${escapeHtml(u.name)}</span>
                            <a href="profile.php?id=${u.id}" target="_blank" class="ac-user-handle" title="View public profile">@${escapeHtml(u.username)} ↗</a>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-size:0.85rem;color:var(--text-secondary);">${escapeHtml(u.email)}</span>
                </td>
                <td>
                    <span class="ac-role-badge ${isAdmin ? 'ac-role-badge--admin' : 'ac-role-badge--user'}">
                        ${isAdmin ? '🛡️ Admin' : '👤 Member'}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${isActive ? 'accepted' : 'rejected'}">
                        ${isActive ? 'Active' : 'Suspended'}
                    </span>
                </td>
                <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap;">
                    ${escapeHtml(u.created_formatted || '—')}
                </td>
                <td>
                    <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                        <span class="skill-badge offer" style="padding:2px 8px;font-size:0.75rem;" title="Skills offered by user">
                            Offer: ${u.offered_count}
                        </span>
                        <span class="skill-badge want" style="padding:2px 8px;font-size:0.75rem;" title="Skills user wants to learn">
                            Want: ${u.wanted_count}
                        </span>
                    </div>
                </td>
                <td style="text-align:right;">
                    ${!isAdmin ? `
                        <div style="display:inline-flex;gap:0.4rem;justify-content:flex-end;">
                            <a href="profile.php?id=${u.id}" target="_blank" class="btn btn-ghost btn-sm" title="View profile in new tab">
                                Profile
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm btn-admin-toggle-status" data-id="${u.id}" data-status="${u.status}">
                                ${isActive ? 'Suspend' : 'Activate'}
                            </button>
                            <button type="button" class="btn btn-danger btn-sm btn-admin-del-user" data-id="${u.id}" data-name="${escapeHtml(u.name)}">
                                Delete
                            </button>
                        </div>
                    ` : '<span style="font-size:0.78rem;font-weight:700;color:var(--text-muted);">Protected Master</span>'}
                </td>
            </tr>
            `;
        }).join('');

        // Attach Suspend / Activate handlers
        tableBody.querySelectorAll('.btn-admin-toggle-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;"></span>';

                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'toggle_status', user_id: id }
                });

                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminUsers();
                    loadAdminStats();
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Retry';
                    window.showToast(res.message || 'Operation failed.', 'error');
                }
            });
        });

        // Attach Delete handlers with custom modal
        tableBody.querySelectorAll('.btn-admin-del-user').forEach(btn => {
            btn.addEventListener('click', () => {
                const id   = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                confirmDeleteUser(id, name);
            });
        });
    }

    function confirmDeleteUser(userId, userName) {
        const modalHtml = `
            <div class="ac-confirm-dialog-content">
                <div class="ac-confirm-icon ac-confirm-icon--danger">⚠️</div>
                <h3 class="ac-confirm-title">Delete User Account?</h3>
                <p class="ac-confirm-desc">
                    Are you sure you want to permanently delete <strong>${escapeHtml(userName)}</strong>?
                    All associated skill offerings, exchange requests, and conversations will be removed immediately.
                </p>
                <div style="display:flex;justify-content:center;gap:0.75rem;margin-top:1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-delete-user-submit">Yes, Delete Account</button>
                </div>
            </div>
        `;
        window.openModal('Confirm Account Deletion', modalHtml);

        const confirmBtn = document.getElementById('btn-confirm-delete-user-submit');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner"></span> Deleting…';

                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'delete', user_id: userId }
                });

                window.closeModal();

                if (res.success) {
                    window.showToast(res.message || 'User deleted successfully.', 'success');
                    await loadAdminUsers();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Failed to delete user.', 'error');
                }
            });
        }
    }

    // User filter pills click handlers
    document.querySelectorAll('#user-filter-group .ac-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#user-filter-group .ac-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentUserFilter = btn.getAttribute('data-filter') || 'all';
            filterAndRenderUsers();
        });
    });

    // User search input
    const userSearchInput = document.getElementById('user-search-input');
    const userSearchClear = document.getElementById('user-search-clear');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', () => {
            if (userSearchClear) {
                userSearchClear.classList.toggle('visible', userSearchInput.value.length > 0);
            }
            filterAndRenderUsers();
        });
    }
    if (userSearchClear) {
        userSearchClear.addEventListener('click', () => {
            userSearchInput.value = '';
            userSearchClear.classList.remove('visible');
            filterAndRenderUsers();
            userSearchInput.focus();
        });
    }


    // ----------------------------------------------------------------
    // 5. SKILLS TAXONOMY TABLE (Search, Category Filters, Add Skill)
    // ----------------------------------------------------------------
    let allSkills = [];
    let currentSkillCategory = 'all';

    async function loadAdminSkills() {
        const tableBody = document.getElementById('admin-skills-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/skills.php');
        if (!res.success) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--danger);padding:2rem;">Failed to load skills.</td></tr>`;
            return;
        }

        allSkills = res.data || [];
        filterAndRenderSkills();
    }

    function filterAndRenderSkills() {
        const searchInput = document.getElementById('skill-search-input');
        const q = (searchInput ? searchInput.value : '').trim().toLowerCase();

        let list = allSkills;

        // Apply category filter
        if (currentSkillCategory !== 'all') {
            list = list.filter(s => s.category && s.category.toLowerCase() === currentSkillCategory.toLowerCase());
        }

        // Apply search query
        if (q) {
            list = list.filter(s =>
                (s.name && s.name.toLowerCase().includes(q)) ||
                (s.category && s.category.toLowerCase().includes(q)) ||
                (s.description && s.description.toLowerCase().includes(q))
            );
        }

        renderSkillsTable(list);

        // Update status bar
        const counterEl = document.getElementById('skills-result-counter');
        if (counterEl) {
            counterEl.innerHTML = `Showing <strong>${list.length}</strong> of <strong>${allSkills.length}</strong> taxonomy skills`;
        }
    }

    function renderSkillsTable(skills) {
        const tableBody = document.getElementById('admin-skills-table-body');
        if (!tableBody) return;

        if (skills.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;padding:3rem 1.5rem;color:var(--text-muted);">
                        <div style="font-size:2rem;margin-bottom:0.5rem;">🎯</div>
                        <div style="font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">No skills found</div>
                        <div style="font-size:0.85rem;">Try another keyword or category filter.</div>
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = skills.map(s => `
            <tr>
                <td><strong style="color:var(--text-muted);">#${s.id}</strong></td>
                <td>
                    <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);">${escapeHtml(s.name)}</div>
                </td>
                <td>
                    <span class="skill-badge" style="font-size:0.75rem;">${escapeHtml(s.category)}</span>
                </td>
                <td style="font-size:0.82rem;color:var(--text-secondary);max-width:280px;line-height:1.45;">
                    ${escapeHtml(s.description || 'No description provided.')}
                </td>
                <td>
                    <div style="display:flex;gap:0.4rem;align-items:center;">
                        <span class="skill-badge offer" style="padding:2px 8px;font-size:0.75rem;" title="Users offering this skill">
                            Offered: ${s.offered_by}
                        </span>
                        <span class="skill-badge want" style="padding:2px 8px;font-size:0.75rem;" title="Users requesting this skill">
                            Wanted: ${s.wanted_by}
                        </span>
                    </div>
                </td>
                <td style="text-align:right;">
                    <button type="button" class="btn btn-danger btn-sm btn-admin-del-skill"
                            data-id="${s.id}" data-name="${escapeHtml(s.name)}">
                        Delete
                    </button>
                </td>
            </tr>
        `).join('');

        // Attach Delete Skill handlers
        tableBody.querySelectorAll('.btn-admin-del-skill').forEach(btn => {
            btn.addEventListener('click', () => {
                const id   = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                confirmDeleteSkill(id, name);
            });
        });
    }

    function confirmDeleteSkill(skillId, skillName) {
        const modalHtml = `
            <div class="ac-confirm-dialog-content">
                <div class="ac-confirm-icon ac-confirm-icon--danger">🎯</div>
                <h3 class="ac-confirm-title">Delete Skill Taxonomy?</h3>
                <p class="ac-confirm-desc">
                    Are you sure you want to remove <strong>${escapeHtml(skillName)}</strong> from the master catalog?
                    This will automatically detach it from all members offering or wanting it.
                </p>
                <div style="display:flex;justify-content:center;gap:0.75rem;margin-top:1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-delete-skill-submit">Yes, Delete Skill</button>
                </div>
            </div>
        `;
        window.openModal('Confirm Skill Deletion', modalHtml);

        const confirmBtn = document.getElementById('btn-confirm-delete-skill-submit');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner"></span> Deleting…';

                const res = await window.apiFetch('api/admin/skills.php', {
                    method: 'POST',
                    body: { action: 'delete', skill_id: skillId }
                });

                window.closeModal();

                if (res.success) {
                    window.showToast(res.message || 'Skill removed successfully.', 'success');
                    await loadAdminSkills();
                    loadAdminStats();
                } else {
                    window.showToast(res.message || 'Failed to delete skill.', 'error');
                }
            });
        }
    }

    // Add Skill Modal
    function openAddSkillModal() {
        const modalHtml = `
            <form id="admin-add-skill-form">
                <div class="form-group">
                    <label class="form-label">Skill Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. Flutter, Vue.js, Acoustic Bass, French Conversation" autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Taxonomy Category <span class="req">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="Programming">💻 Programming & Technology</option>
                        <option value="Design">🎨 Design & Creative Arts</option>
                        <option value="Creative">🎵 Music & Performance</option>
                        <option value="Education">📚 Languages & Education</option>
                        <option value="Business">💼 Business & Marketing</option>
                        <option value="Other">🌐 General / Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-textarea" rows="3" placeholder="Brief outline of what learners will study or practice…"></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-add-skill">Add to Catalog</button>
                </div>
            </form>
        `;
        window.openModal('Add Skill to Master Catalog', modalHtml);

        const form = document.getElementById('admin-add-skill-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-submit-add-skill');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Saving…';

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
                window.showToast(res.message || 'Skill added successfully.', 'success');
                await loadAdminSkills();
                loadAdminStats();
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add to Catalog';
                window.showToast(res.message || 'Failed to add skill.', 'error');
            }
        });
    }

    const addSkillBtn = document.getElementById('btn-admin-add-skill');
    if (addSkillBtn) {
        addSkillBtn.addEventListener('click', openAddSkillModal);
    }

    // Category filter pills click
    document.querySelectorAll('#skill-category-filter-group .ac-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#skill-category-filter-group .ac-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSkillCategory = btn.getAttribute('data-category') || 'all';
            filterAndRenderSkills();
        });
    });

    // Skill search input
    const skillSearchInput = document.getElementById('skill-search-input');
    const skillSearchClear = document.getElementById('skill-search-clear');
    if (skillSearchInput) {
        skillSearchInput.addEventListener('input', () => {
            if (skillSearchClear) {
                skillSearchClear.classList.toggle('visible', skillSearchInput.value.length > 0);
            }
            filterAndRenderSkills();
        });
    }
    if (skillSearchClear) {
        skillSearchClear.addEventListener('click', () => {
            skillSearchInput.value = '';
            skillSearchClear.classList.remove('visible');
            filterAndRenderSkills();
            skillSearchInput.focus();
        });
    }


    // ----------------------------------------------------------------
    // 6. SWAPS MONITORING TABLE (Search, Filters, View Proposal)
    // ----------------------------------------------------------------
    let allSwaps = [];
    let currentSwapFilter = 'all';

    async function loadAdminSwaps() {
        const tableBody = document.getElementById('admin-swaps-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/swaps.php');
        if (!res.success) {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--danger);padding:2rem;">Failed to load swaps.</td></tr>`;
            return;
        }

        allSwaps = res.data || [];
        updateSwapFilterCounts();
        filterAndRenderSwaps();
    }

    function updateSwapFilterCounts() {
        const total = allSwaps.length;
        const pending = allSwaps.filter(s => s.status === 'pending').length;
        const accepted = allSwaps.filter(s => s.status === 'accepted').length;
        const rejected = allSwaps.filter(s => s.status === 'rejected').length;
        const cancelled = allSwaps.filter(s => s.status === 'cancelled').length;

        setText('count-swaps-all', total);
        setText('count-swaps-pending', pending);
        setText('count-swaps-accepted', accepted);
        setText('count-swaps-rejected', rejected);
        setText('count-swaps-cancelled', cancelled);
    }

    function filterAndRenderSwaps() {
        const searchInput = document.getElementById('swap-search-input');
        const q = (searchInput ? searchInput.value : '').trim().toLowerCase();

        let list = allSwaps;

        // Apply status filter
        if (currentSwapFilter !== 'all') {
            list = list.filter(s => s.status === currentSwapFilter);
        }

        // Apply search query
        if (q) {
            list = list.filter(s =>
                (s.sender_name && s.sender_name.toLowerCase().includes(q)) ||
                (s.sender_username && s.sender_username.toLowerCase().includes(q)) ||
                (s.receiver_name && s.receiver_name.toLowerCase().includes(q)) ||
                (s.receiver_username && s.receiver_username.toLowerCase().includes(q)) ||
                (s.offered_skill && s.offered_skill.toLowerCase().includes(q)) ||
                (s.requested_skill && s.requested_skill.toLowerCase().includes(q))
            );
        }

        renderSwapsTable(list);

        // Update status bar
        const counterEl = document.getElementById('swaps-result-counter');
        if (counterEl) {
            counterEl.innerHTML = `Showing <strong>${list.length}</strong> of <strong>${allSwaps.length}</strong> exchange requests`;
        }
    }

    function renderSwapsTable(swaps) {
        const tableBody = document.getElementById('admin-swaps-table-body');
        const emptyBox  = document.getElementById('admin-swaps-empty');
        if (!tableBody) return;

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

        tableBody.innerHTML = swaps.map(s => {
            const senderAvatar = s.sender_avatar || 'assets/images/default-avatar.svg';
            const receiverAvatar = s.receiver_avatar || 'assets/images/default-avatar.svg';

            return `
            <tr>
                <td><strong style="color:var(--text-muted);">#${s.id}</strong></td>
                <td>
                    <div class="ac-user-cell">
                        <img class="ac-user-avatar" src="${escapeHtml(senderAvatar)}" alt="${escapeHtml(s.sender_name)}" loading="lazy">
                        <div class="ac-user-names">
                            <span class="ac-user-display-name">${escapeHtml(s.sender_name)}</span>
                            <a href="profile.php?id=${s.sender_id}" target="_blank" class="ac-user-handle">@${escapeHtml(s.sender_username)}</a>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="ac-user-cell">
                        <img class="ac-user-avatar" src="${escapeHtml(receiverAvatar)}" alt="${escapeHtml(s.receiver_name)}" loading="lazy">
                        <div class="ac-user-names">
                            <span class="ac-user-display-name">${escapeHtml(s.receiver_name)}</span>
                            <a href="profile.php?id=${s.receiver_id}" target="_blank" class="ac-user-handle">@${escapeHtml(s.receiver_username)}</a>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                        <span class="skill-badge offer" style="padding:2px 8px;font-size:0.75rem;" title="Skill offered by requester">
                            Teaches: ${escapeHtml(s.offered_skill)}
                        </span>
                        <span style="color:var(--text-muted);font-weight:700;">⇄</span>
                        <span class="skill-badge want" style="padding:2px 8px;font-size:0.75rem;" title="Skill requested from receiver">
                            Learns: ${escapeHtml(s.requested_skill)}
                        </span>
                    </div>
                </td>
                <td>
                    <span class="status-badge ${statusClass[s.status] || ''}">${escapeHtml(s.status)}</span>
                </td>
                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">
                    ${escapeHtml(s.time_ago)}
                </td>
                <td style="text-align:right;">
                    <button type="button" class="btn btn-secondary btn-sm btn-view-proposal"
                            data-swap-json='${JSON.stringify(s).replace(/'/g, "&apos;")}'>
                        View Details
                    </button>
                </td>
            </tr>
            `;
        }).join('');

        // Attach View Proposal message modal handlers
        tableBody.querySelectorAll('.btn-view-proposal').forEach(btn => {
            btn.addEventListener('click', () => {
                const swap = JSON.parse(btn.getAttribute('data-swap-json'));
                openProposalModal(swap);
            });
        });
    }

    function openProposalModal(swap) {
        const modalHtml = `
            <div style="padding:0.5rem 0;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border-color);">
                    <span class="status-badge ${swap.status}">${escapeHtml(swap.status)}</span>
                    <span style="font-size:0.82rem;color:var(--text-muted);">Proposed ${escapeHtml(swap.time_ago)}</span>
                </div>

                <div class="ac-report-parties" style="margin-bottom:1.25rem;">
                    <div class="ac-report-party">
                        <img class="ac-user-avatar" src="${escapeHtml(swap.sender_avatar || 'assets/images/default-avatar.svg')}" alt="${escapeHtml(swap.sender_name)}">
                        <div>
                            <div class="ac-report-party-label">Requester</div>
                            <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(swap.sender_name)}</div>
                            <a href="profile.php?id=${swap.sender_id}" target="_blank" style="font-size:0.78rem;color:var(--primary);">@${escapeHtml(swap.sender_username)}</a>
                        </div>
                    </div>
                    <div class="ac-report-arrow">⇄</div>
                    <div class="ac-report-party">
                        <img class="ac-user-avatar" src="${escapeHtml(swap.receiver_avatar || 'assets/images/default-avatar.svg')}" alt="${escapeHtml(swap.receiver_name)}">
                        <div>
                            <div class="ac-report-party-label">Receiver</div>
                            <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(swap.receiver_name)}</div>
                            <a href="profile.php?id=${swap.receiver_id}" target="_blank" style="font-size:0.78rem;color:var(--primary);">@${escapeHtml(swap.receiver_username)}</a>
                        </div>
                    </div>
                </div>

                <div style="background:var(--bg-surface-elevated);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:1rem;margin-bottom:1.25rem;">
                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">Exchange Agreement</div>
                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                        <span class="skill-badge offer">Offered: ${escapeHtml(swap.offered_skill)}</span>
                        <span style="font-weight:700;color:var(--text-muted);">for</span>
                        <span class="skill-badge want">Requested: ${escapeHtml(swap.requested_skill)}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Attached Proposal Message</label>
                    <div class="ac-report-reason-box" style="margin-bottom:0;">
                        ${swap.message ? `&ldquo;${escapeHtml(swap.message)}&rdquo;` : '<span style="color:var(--text-muted);font-style:normal;">No written message accompanied this proposal.</span>'}
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" onclick="window.closeModal()">Close Window</button>
                    <a href="messages.php?with=${swap.sender_id}" target="_blank" class="btn btn-primary">Message Requester</a>
                </div>
            </div>
        `;
        window.openModal(`Swap Proposal #${swap.id}`, modalHtml);
    }

    // Swap filter pills click
    document.querySelectorAll('#swap-filter-pills .ac-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#swap-filter-pills .ac-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSwapFilter = btn.getAttribute('data-status') || 'all';
            filterAndRenderSwaps();
        });
    });

    // Swap search input
    const swapSearchInput = document.getElementById('swap-search-input');
    const swapSearchClear = document.getElementById('swap-search-clear');
    if (swapSearchInput) {
        swapSearchInput.addEventListener('input', () => {
            if (swapSearchClear) {
                swapSearchClear.classList.toggle('visible', swapSearchInput.value.length > 0);
            }
            filterAndRenderSwaps();
        });
    }
    if (swapSearchClear) {
        swapSearchClear.addEventListener('click', () => {
            swapSearchInput.value = '';
            swapSearchClear.classList.remove('visible');
            filterAndRenderSwaps();
            swapSearchInput.focus();
        });
    }


    // ----------------------------------------------------------------
    // 7. COMMUNITY REPORTS & MODERATION (Filters, Actions, Suspend User)
    // ----------------------------------------------------------------
    let allReports = [];
    let currentReportFilter = 'all';

    async function loadAdminReports() {
        const container = document.getElementById('admin-reports-list');
        if (!container) return;

        const res = await window.apiFetch('api/admin/reports.php');
        if (!res.success) {
            container.innerHTML = `<p style="color:var(--danger);padding:1.5rem;">Failed to load reports queue.</p>`;
            return;
        }

        allReports = res.data || [];
        updateReportFilterCounts();
        filterAndRenderReports();
    }

    function updateReportFilterCounts() {
        const total = allReports.length;
        const pending = allReports.filter(r => r.status === 'pending').length;
        const resolved = allReports.filter(r => r.status === 'resolved').length;
        const dismissed = allReports.filter(r => r.status === 'dismissed').length;

        setText('count-reports-all', total);
        setText('count-reports-pending', pending);
        setText('count-reports-resolved', resolved);
        setText('count-reports-dismissed', dismissed);

        // Sidebar badge
        const badge = document.getElementById('sidebar-reports-badge');
        if (badge) {
            badge.textContent = pending;
            badge.classList.toggle('hidden', pending === 0);
        }
    }

    function filterAndRenderReports() {
        const container = document.getElementById('admin-reports-list');
        if (!container) return;

        const filtered = currentReportFilter === 'all'
            ? allReports
            : allReports.filter(r => r.status === currentReportFilter);

        // Update status bar
        const counterEl = document.getElementById('reports-result-counter');
        if (counterEl) {
            counterEl.innerHTML = `Showing <strong>${filtered.length}</strong> of <strong>${allReports.length}</strong> total moderation reports`;
        }

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding:3.5rem 1rem;">
                    <div class="empty-state-icon">🛡️</div>
                    <h3 class="empty-state-title">No reports in this view</h3>
                    <p class="empty-state-desc">The community moderation queue is clear and in good standing!</p>
                </div>`;
            return;
        }

        const statusClass = {
            pending:   'pending',
            resolved:  'accepted',
            dismissed: 'cancelled'
        };

        container.innerHTML = filtered.map(r => {
            const isPending = r.status === 'pending';
            const reporterAvatar = r.reporter_avatar || 'assets/images/default-avatar.svg';
            const reportedAvatar = r.reported_avatar || 'assets/images/default-avatar.svg';
            const isSuspended = r.reported_status === 'suspended';

            const hasReportedUser = !!r.reported_name;
            const titleHtml = r.title ? `<div style="font-weight:700;font-size:1.05rem;color:var(--text-primary);margin-bottom:0.75rem;">${escapeHtml(r.title)}</div>` : '';

            return `
                <div class="ac-report-card is-${r.status}">
                    <div class="ac-report-header">
                        <div class="ac-report-type-tag">
                            <span class="status-badge ${statusClass[r.status] || 'pending'}">${escapeHtml(r.status)}</span>
                            <span>Category: <strong>${escapeHtml(r.report_type)}</strong></span>
                        </div>
                        <span style="font-size:0.8rem;color:var(--text-muted);">${escapeHtml(r.time_ago)}</span>
                    </div>

                    ${titleHtml}

                    <div class="ac-report-parties">
                        <div class="ac-report-party">
                            <img class="ac-user-avatar" src="${escapeHtml(reporterAvatar)}" alt="${escapeHtml(r.reporter_name)}">
                            <div>
                                <div class="ac-report-party-label">Submitted By</div>
                                <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(r.reporter_name)}</div>
                                <span style="font-size:0.78rem;color:var(--text-muted);">@${escapeHtml(r.reporter_username)}</span>
                            </div>
                        </div>

                        <div class="ac-report-arrow">➔</div>

                        <div class="ac-report-party">
                            ${hasReportedUser ? `
                                <img class="ac-user-avatar" src="${escapeHtml(reportedAvatar)}" alt="${escapeHtml(r.reported_name)}">
                                <div>
                                    <div class="ac-report-party-label" style="color:var(--danger);">Reported Member</div>
                                    <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(r.reported_name)}</div>
                                    <div style="display:flex;gap:0.4rem;align-items:center;">
                                        <a href="profile.php?id=${r.reported_user_id}" target="_blank" style="font-size:0.78rem;color:var(--primary);">@${escapeHtml(r.reported_username)} ↗</a>
                                        ${isSuspended ? '<span style="font-size:0.7rem;background:var(--danger-bg);color:var(--danger);padding:1px 5px;border-radius:3px;">Suspended</span>' : ''}
                                    </div>
                                </div>
                            ` : `
                                <div class="ac-stat-icon-wrap" style="width:38px;height:38px;font-size:1.2rem;">🏢</div>
                                <div>
                                    <div class="ac-report-party-label" style="color:var(--primary);">Target</div>
                                    <div style="font-weight:700;color:var(--text-primary);">Platform / Administration</div>
                                    <span style="font-size:0.78rem;color:var(--text-muted);">Internal Review</span>
                                </div>
                            `}
                        </div>
                    </div>

                    <div class="ac-report-reason-box">
                        &ldquo;${escapeHtml(r.reason)}&rdquo;
                    </div>

                    <div class="ac-report-actions-row">
                        ${hasReportedUser ? `
                            <a href="profile.php?id=${r.reported_user_id}" target="_blank" class="btn btn-ghost btn-sm">
                                Inspect Member Profile ↗
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm btn-report-toggle-user"
                                    data-user-id="${r.reported_user_id}" data-current-status="${isSuspended ? 'suspended' : 'active'}">
                                ${isSuspended ? 'Activate User' : 'Suspend User'}
                            </button>
                        ` : ''}
                        ${isPending ? `
                            <button type="button" class="btn btn-secondary btn-sm btn-report-action" data-id="${r.id}" data-status="dismissed">
                                Dismiss Report
                            </button>
                            <button type="button" class="btn btn-success btn-sm btn-report-action" data-id="${r.id}" data-status="resolved">
                                Mark Resolved
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        // Action handlers: resolve / dismiss
        container.querySelectorAll('.btn-report-action').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id     = btn.getAttribute('data-id');
                const status = btn.getAttribute('data-status');
                btn.disabled = true;

                const res = await window.apiFetch('api/admin/reports.php', {
                    method: 'POST',
                    body: { report_id: id, status: status }
                });

                if (res.success) {
                    window.showToast(res.message, 'success');
                    await loadAdminReports();
                    loadAdminStats();
                } else {
                    btn.disabled = false;
                    window.showToast(res.message || 'Failed to update report status.', 'error');
                }
            });
        });

        // Direct moderation: Suspend / Activate user from report card
        container.querySelectorAll('.btn-report-toggle-user').forEach(btn => {
            btn.addEventListener('click', async () => {
                const userId = btn.getAttribute('data-user-id');
                btn.disabled = true;

                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'toggle_status', user_id: userId }
                });

                if (res.success) {
                    window.showToast(res.message, 'success');
                    await Promise.all([loadAdminReports(), loadAdminUsers(), loadAdminStats()]);
                } else {
                    btn.disabled = false;
                    window.showToast(res.message || 'Operation failed.', 'error');
                }
            });
        });
    }

    // Report filter pills click
    document.querySelectorAll('#report-filter-group .ac-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#report-filter-group .ac-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentReportFilter = btn.getAttribute('data-status') || 'all';
            filterAndRenderReports();
        });
    });


    // ----------------------------------------------------------------
    // 8. HELPERS & UTILITIES
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

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val !== undefined && val !== null ? val : '0';
    }


    // ----------------------------------------------------------------
    // 9. BOOTSTRAP INITIAL DATA
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
