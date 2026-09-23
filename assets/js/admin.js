/**
 * Administrator Panel Client Controller
 * Users moderation, master skills catalog, and user report handling
 */

document.addEventListener('DOMContentLoaded', () => {
    const tabBtns = document.querySelectorAll('.admin-tab-btn');
    const tabPanes = document.querySelectorAll('.admin-tab-pane');

    // Tab switcher
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.add('hidden'));

            btn.classList.add('active');
            const pane = document.getElementById(`admin-tab-${target}`);
            if (pane) pane.classList.remove('hidden');
        });
    });

    loadAdminStats();
    loadAdminUsers();
    loadAdminSkills();
    loadAdminReports();

    // 1. Stats
    async function loadAdminStats() {
        const res = await window.apiFetch('api/admin/stats.php');
        if (res.success && res.data) {
            const d = res.data;
            setStat('stat-total-users', d.total_users);
            setStat('stat-active-users', d.active_users);
            setStat('stat-total-skills', d.total_skills);
            setStat('stat-total-swaps', d.total_swaps);
            setStat('stat-accepted-swaps', d.accepted_swaps);
            setStat('stat-pending-reports', d.pending_reports);
        }
    }

    function setStat(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val !== undefined ? val : '0';
    }

    // 2. Users Table
    async function loadAdminUsers() {
        const tableBody = document.getElementById('admin-users-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/users.php');
        if (!res.success) {
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--danger);">Failed to load users.</td></tr>';
            return;
        }

        const users = res.data || [];
        tableBody.innerHTML = users.map(u => `
            <tr>
                <td><strong>#${u.id}</strong></td>
                <td>
                    <div style="font-weight: 600;">${escapeHtml(u.name)}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">@${escapeHtml(u.username)}</div>
                </td>
                <td>${escapeHtml(u.email)}</td>
                <td>
                    <span class="status-badge ${u.role === 'admin' ? 'accepted' : 'cancelled'}">${escapeHtml(u.role)}</span>
                </td>
                <td>
                    <span class="status-badge ${u.status === 'active' ? 'accepted' : 'rejected'}">${escapeHtml(u.status)}</span>
                </td>
                <td>
                    <span style="font-size: 0.8rem;">Offers: <strong>${u.offered_count}</strong> | Wants: <strong>${u.wanted_count}</strong></span>
                </td>
                <td>
                    ${u.role !== 'admin' ? `
                        <div style="display: flex; gap: 0.4rem;">
                            <button class="btn btn-secondary btn-sm btn-admin-toggle-status" data-id="${u.id}">
                                ${u.status === 'active' ? 'Suspend' : 'Activate'}
                            </button>
                            <button class="btn btn-danger btn-sm btn-admin-del-user" data-id="${u.id}" data-name="${escapeHtml(u.name)}">
                                Delete
                            </button>
                        </div>
                    ` : '<span style="font-size: 0.75rem; color: var(--text-muted);">Protected</span>'}
                </td>
            </tr>
        `).join('');

        // Attach action listeners
        tableBody.querySelectorAll('.btn-admin-toggle-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'toggle_status', user_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadAdminUsers();
                    loadAdminStats();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });

        tableBody.querySelectorAll('.btn-admin-del-user').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                if (!confirm(`Are you sure you want to permanently delete user "${name}"?`)) return;

                const res = await window.apiFetch('api/admin/users.php', {
                    method: 'POST',
                    body: { action: 'delete', user_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadAdminUsers();
                    loadAdminStats();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });
    }

    // 3. Skills Catalog
    async function loadAdminSkills() {
        const tableBody = document.getElementById('admin-skills-table-body');
        if (!tableBody) return;

        const res = await window.apiFetch('api/admin/skills.php');
        if (!res.success) return;

        const skills = res.data || [];
        tableBody.innerHTML = skills.map(s => `
            <tr>
                <td><strong>#${s.id}</strong></td>
                <td><strong>${escapeHtml(s.name)}</strong></td>
                <td><span class="skill-badge">${escapeHtml(s.category)}</span></td>
                <td style="font-size: 0.82rem; color: var(--text-secondary); max-width: 250px;">${escapeHtml(s.description || '-')}</td>
                <td>
                    <span style="font-size: 0.8rem;">Offered: <strong>${s.offered_by}</strong> | Wanted: <strong>${s.wanted_by}</strong></span>
                </td>
                <td>
                    <button class="btn btn-danger btn-sm btn-admin-del-skill" data-id="${s.id}" data-name="${escapeHtml(s.name)}">
                        Delete
                    </button>
                </td>
            </tr>
        `).join('');

        tableBody.querySelectorAll('.btn-admin-del-skill').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                if (!confirm(`Delete skill "${name}" from master taxonomy?`)) return;

                const res = await window.apiFetch('api/admin/skills.php', {
                    method: 'POST',
                    body: { action: 'delete', skill_id: id }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadAdminSkills();
                    loadAdminStats();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });
    }

    // Add Skill Modal
    const addSkillBtn = document.getElementById('btn-admin-add-skill');
    if (addSkillBtn) {
        addSkillBtn.addEventListener('click', () => {
            const modalHtml = `
                <form id="admin-add-skill-form">
                    <div class="form-group">
                        <label class="form-label">Skill Name</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. Flutter">
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
                        <textarea name="description" class="form-textarea" placeholder="Brief explanation of skill scope..."></textarea>
                    </div>
                    <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                        <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Skill</button>
                    </div>
                </form>
            `;

            window.openModal('Add Master Skill', modalHtml);

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
                    loadAdminSkills();
                    loadAdminStats();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });
    }

    // 4. Reports
    async function loadAdminReports() {
        const container = document.getElementById('admin-reports-list');
        if (!container) return;

        const res = await window.apiFetch('api/admin/reports.php');
        if (!res.success) return;

        const reports = res.data || [];

        if (reports.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🛡️</div>
                    <h3 class="empty-state-title">No pending reports</h3>
                    <p class="empty-state-desc">The community is happy and well-moderated!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = reports.map(r => `
            <div class="card" style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <div>
                        <span class="status-badge ${r.status === 'pending' ? 'pending' : (r.status === 'resolved' ? 'accepted' : 'cancelled')}">
                            ${escapeHtml(r.status)}
                        </span>
                        <strong style="margin-left: 0.5rem;">Type: ${escapeHtml(r.report_type)}</strong>
                    </div>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">${escapeHtml(r.time_ago)}</span>
                </div>
                <p style="font-size: 0.88rem; margin: 0.5rem 0;">
                    Reporter: <strong>${escapeHtml(r.reporter_name)}</strong> (@${escapeHtml(r.reporter_username)}) &rarr;
                    Reported User: <strong style="color: var(--danger);">${escapeHtml(r.reported_name)}</strong> (@${escapeHtml(r.reported_username)})
                </p>
                <p style="background: var(--bg-surface-elevated); padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; color: var(--text-secondary);">
                    "${escapeHtml(r.reason)}"
                </p>
                ${r.status === 'pending' ? `
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <button class="btn btn-secondary btn-sm btn-report-action" data-id="${r.id}" data-status="dismissed">Dismiss</button>
                        <button class="btn btn-danger btn-sm btn-report-action" data-id="${r.id}" data-status="resolved">Resolve / Take Action</button>
                    </div>
                ` : ''}
            </div>
        `).join('');

        container.querySelectorAll('.btn-report-action').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const status = btn.getAttribute('data-status');
                const res = await window.apiFetch('api/admin/reports.php', {
                    method: 'POST',
                    body: { report_id: id, status: status }
                });
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadAdminReports();
                    loadAdminStats();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });
    }
});
