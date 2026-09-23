/**
 * Skill Matchmaking Client Controller
 * Displays Reciprocal Two-Way Matches and One-Way Matches
 */

document.addEventListener('DOMContentLoaded', () => {
    const twoWayGrid = document.getElementById('two-way-matches-grid');
    const oneWayGrid = document.getElementById('one-way-matches-grid');
    const twoWayCountEl = document.getElementById('two-way-count');
    const oneWayCountEl = document.getElementById('one-way-count');

    /**
     * Helper function to escape HTML special characters
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    loadMatches();

    async function loadMatches() {
        try {
            const res = await window.apiFetch('api/matches/find.php');

            if (!res || !res.success) {
                window.showToast((res && res.message) || 'Failed to calculate skill matches.', 'error');
                return;
            }

            const data = res.data;

            if (!data.has_skills) {
                if (twoWayGrid) {
                    twoWayGrid.innerHTML = `
                        <div class="empty-state col-span-full">
                            <div class="empty-state-icon">🎯</div>
                            <h3 class="empty-state-title">No skills in your profile yet</h3>
                            <p class="empty-state-desc">Add at least one skill you can offer and one skill you want to learn so the matching engine can find your ideal partners!</p>
                            <a href="profile.php" class="btn btn-primary" style="margin-top: 1rem;">Setup My Skills</a>
                        </div>
                    `;
                }
                if (oneWayGrid) oneWayGrid.innerHTML = '';
                return;
            }

            if (twoWayCountEl) twoWayCountEl.textContent = data.two_way_count || 0;
            if (oneWayCountEl) oneWayCountEl.textContent = data.one_way_count || 0;

            // Render Two-Way Matches
            if (twoWayGrid) {
                if (!data.two_way_matches || data.two_way_matches.length === 0) {
                    twoWayGrid.innerHTML = `
                        <div class="empty-state col-span-full">
                            <div class="empty-state-icon">🤝</div>
                            <h3 class="empty-state-title">No two-way matches yet</h3>
                            <p class="empty-state-desc">We couldn't find a member with an exact mutual skill swap yet. Check out the one-way recommendations below or browse the Discover page!</p>
                            <a href="discover.php" class="btn btn-secondary" style="margin-top: 1rem;">Explore Discover Catalog</a>
                        </div>
                    `;
                } else {
                    twoWayGrid.innerHTML = data.two_way_matches.map(m => renderTwoWayCard(m)).join('');
                }
            }

            // Render One-Way Matches
            if (oneWayGrid) {
                if (!data.one_way_matches || data.one_way_matches.length === 0) {
                    oneWayGrid.innerHTML = `
                        <div class="empty-state col-span-full">
                            <div class="empty-state-icon">💡</div>
                            <h3 class="empty-state-title">No one-way recommendations</h3>
                            <p class="empty-state-desc">Try adding more skills to your wishlist to discover more mentors.</p>
                        </div>
                    `;
                } else {
                    oneWayGrid.innerHTML = data.one_way_matches.map(m => renderOneWayCard(m)).join('');
                }
            }

            attachMatchActionHandlers();

        } catch (err) {
            console.error('Match error:', err);
            window.showToast('Failed to calculate skill matches.', 'error');
        }
    }

    function renderTwoWayCard(match) {
        const statusBadge = match.swap_status
            ? `<span class="status-badge ${escapeHtml(match.swap_status.status)}">${escapeHtml(match.swap_status.status)}</span>`
            : '';

        const actionBtn = match.swap_status
            ? (match.swap_status.status === 'accepted'
                ? `<a href="messages.php" class="btn btn-primary btn-sm">Open Chat</a>`
                : `<button class="btn btn-secondary btn-sm" disabled>Request Sent</button>`)
            : `<button class="btn btn-primary btn-sm btn-quick-swap" data-match='${JSON.stringify(match).replace(/'/g, "&apos;")}'>Send Swap Proposal</button>`;

        return `
            <div class="match-card two-way-match">
                <span class="match-ribbon">100% Reciprocal</span>

                <div class="user-card-top">
                    <img src="${escapeHtml(match.avatar_url)}" alt="${escapeHtml(match.name)}" class="user-card-avatar">
                    <div class="user-card-meta">
                        <h4>${escapeHtml(match.name)}</h4>
                        <div class="user-card-location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            ${escapeHtml(match.location || 'Online / Remote')}
                        </div>
                    </div>
                </div>

                <div class="match-exchange-visual">
                    <div class="exchange-node">
                        <div class="exchange-node-title">You Teach</div>
                        <div class="exchange-node-skill" style="color: var(--primary);">${escapeHtml(match.primary_exchange.you_teach)}</div>
                    </div>
                    <div class="exchange-arrows">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 10 12 15 17 10"></polyline><polyline points="17 14 12 9 7 14"></polyline></svg>
                    </div>
                    <div class="exchange-node">
                        <div class="exchange-node-title">You Learn</div>
                        <div class="exchange-node-skill" style="color: var(--success);">${escapeHtml(match.primary_exchange.you_learn)}</div>
                    </div>
                </div>

                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    ${escapeHtml(match.explanation)}
                </p>

                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <div>${statusBadge}</div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="profile.php?id=${match.id}" class="btn btn-ghost btn-sm">Profile</a>
                        ${actionBtn}
                    </div>
                </div>
            </div>
        `;
    }

    function renderOneWayCard(match) {
        const offeredSkills = match.offered_to_you.map(s => `
            <span class="skill-badge offer">${escapeHtml(s.name || s.skill_name)}</span>
        `).join('');

        return `
            <div class="card card-hover">
                <div class="user-card-top">
                    <img src="${escapeHtml(match.avatar_url)}" alt="${escapeHtml(match.name)}" class="user-card-avatar">
                    <div class="user-card-meta">
                        <h4>${escapeHtml(match.name)}</h4>
                        <div class="user-card-location">${escapeHtml(match.location || 'Online')}</div>
                    </div>
                </div>

                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                    Offers to teach:
                </p>
                <div class="skill-badge-group" style="margin-bottom: 1rem;">
                    ${offeredSkills}
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <a href="profile.php?id=${match.id}" class="btn btn-ghost btn-sm">View Profile</a>
                    <button class="btn btn-secondary btn-sm btn-quick-swap" data-match='${JSON.stringify(match).replace(/'/g, "&apos;")}'>Propose Swap</button>
                </div>
            </div>
        `;
    }

    function attachMatchActionHandlers() {
        document.querySelectorAll('.btn-quick-swap').forEach(btn => {
            btn.addEventListener('click', () => {
                const match = JSON.parse(btn.getAttribute('data-match'));
                openMatchProposalModal(match);
            });
        });
    }

    async function openMatchProposalModal(match) {
        const myProfileRes = await window.apiFetch('api/users/profile.php');
        if (!myProfileRes || !myProfileRes.success) {
            window.showToast('Could not load user profile details.', 'error');
            return;
        }

        // Safely map offered skills across varying API response schemas
        const rawSkills = myProfileRes.data.skills || myProfileRes.data.skills_offered || [];
        const myOffered = Array.isArray(rawSkills)
            ? rawSkills.filter(s => !s.skill_type || s.skill_type === 'OFFER')
            : [];

        const theyOffer = match.offered_to_you || [];

        const myOptions = myOffered.map(s => {
            const skillId = s.skill_id || s.id;
            const skillName = s.skill_name || s.name;
            const isMatch = match.wanted_from_you && match.wanted_from_you.some(w => (w.skill_id || w.id) == skillId);
            return `<option value="${skillId}" ${isMatch ? 'selected' : ''}>${escapeHtml(skillName)} ${isMatch ? '(Matching Demand ★)' : ''}</option>`;
        }).join('');

        const theirOptions = theyOffer.map(s => {
            const skillId = s.skill_id || s.id;
            const skillName = s.skill_name || s.name;
            return `<option value="${skillId}" selected>${escapeHtml(skillName)}</option>`;
        }).join('');

        const modalHtml = `
            <form id="match-proposal-form">
                <p style="margin-bottom: 1.25rem; font-size: 0.92rem; color: var(--text-secondary);">
                    Send a direct swap proposal to <strong>${escapeHtml(match.name)}</strong>.
                </p>

                <div class="form-group">
                    <label class="form-label">You will teach</label>
                    <select name="offered_skill_id" class="form-select" required>
                        ${myOptions}
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">${escapeHtml(match.name)} will teach you</label>
                    <select name="requested_skill_id" class="form-select" required>
                        ${theirOptions}
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Message (Optional)</label>
                    <textarea name="message" class="form-textarea" placeholder="Hi ${escapeHtml(match.name.split(' ')[0])}! I saw our skills match. Would love to set up a swap session!"></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Swap Proposal</button>
                </div>
            </form>
        `;

        window.openModal(`Send Skill Swap Proposal`, modalHtml);

        const form = document.getElementById('match-proposal-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sending...';

            const payload = {
                receiver_id: parseInt(match.id, 10),
                offered_skill_id: parseInt(form.querySelector('[name="offered_skill_id"]').value, 10),
                requested_skill_id: parseInt(form.querySelector('[name="requested_skill_id"]').value, 10),
                message: form.querySelector('[name="message"]').value.trim()
            };

            const res = await window.apiFetch('api/requests/send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res && res.success) {
                window.closeModal();
                window.showToast(res.message, 'success', 'Proposal Sent!');
                loadMatches();
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Swap Proposal';
                window.showToast((res && res.message) || 'Error sending proposal.', 'error');
            }
        });
    }
});