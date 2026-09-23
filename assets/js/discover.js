/**
 * Discovery & Search Client Controller
 * Live Filter, Category Chips, and Swap Request Modal
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('discover-search-input');
    const categoryFilter = document.getElementById('category-filter');
    const proficiencyFilter = document.getElementById('proficiency-filter');
    const resultsContainer = document.getElementById('discover-results-grid');
    const resultsCountEl = document.getElementById('results-count-text');

    let debounceTimer = null;

    // Load initial users
    fetchUsers();

    // Search input listener with 300ms debounce
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchUsers();
            }, 300);
        });
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', fetchUsers);
    }

    if (proficiencyFilter) {
        proficiencyFilter.addEventListener('change', fetchUsers);
    }

    async function fetchUsers() {
        if (!resultsContainer) return;

        resultsContainer.innerHTML = `
            <div class="col-span-full" style="text-align: center; padding: 3rem;">
                <span class="spinner spinner-primary" style="width: 32px; height: 32px;"></span>
                <p style="margin-top: 1rem; color: var(--text-muted);">Finding talented community members...</p>
            </div>
        `;

        const query = searchInput ? searchInput.value.trim() : '';
        const category = categoryFilter ? categoryFilter.value : '';
        const proficiency = proficiencyFilter ? proficiencyFilter.value : '';

        const params = new URLSearchParams();
        if (query) params.append('q', query);
        if (category) params.append('category', category);
        if (proficiency) params.append('proficiency', proficiency);

        const res = await window.apiFetch(`api/users/search.php?${params.toString()}`);

        if (res.success && res.data && res.data.users) {
            renderUsers(res.data.users);
            if (resultsCountEl) {
                resultsCountEl.textContent = `Showing ${res.data.users.length} members available for swap`;
            }
        } else {
            resultsContainer.innerHTML = `
                <div class="empty-state col-span-full">
                    <div class="empty-state-icon">🔍</div>
                    <h3 class="empty-state-title">No members match your criteria</h3>
                    <p class="empty-state-desc">Try clearing your filters or search for another skill like Python, Photoshop, or Guitar.</p>
                </div>
            `;
            if (resultsCountEl) resultsCountEl.textContent = '0 members found';
        }
    }

    function renderUsers(users) {
        if (!resultsContainer) return;

        if (users.length === 0) {
            resultsContainer.innerHTML = `
                <div class="empty-state col-span-full">
                    <div class="empty-state-icon">🔍</div>
                    <h3 class="empty-state-title">No skill matches found</h3>
                    <p class="empty-state-desc">Be the first to introduce this skill or broaden your keywords.</p>
                </div>
            `;
            return;
        }

        resultsContainer.innerHTML = users.map(user => {
            const offeredBadges = user.skills_offered && user.skills_offered.length > 0
                ? user.skills_offered.map(s => `
                    <span class="skill-badge offer">
                        ${escapeHtml(s.name)}
                        <span class="proficiency-pill proficiency-${escapeHtml(s.proficiency)}">${escapeHtml(s.proficiency)}</span>
                    </span>
                `).join('')
                : '<span style="font-size: 0.8rem; color: var(--text-muted);">No skills offered yet</span>';

            const wantedBadges = user.skills_wanted && user.skills_wanted.length > 0
                ? user.skills_wanted.map(s => `
                    <span class="skill-badge want">
                        ${escapeHtml(s.name)}
                    </span>
                `).join('')
                : '<span style="font-size: 0.8rem; color: var(--text-muted);">No skills wanted yet</span>';

            const ratingDisplay = user.total_reviews > 0
                ? `<span style="font-weight: 700; color: #f59e0b;">★ ${user.avg_rating}</span> <span style="font-size: 0.78rem; color: var(--text-muted);">(${user.total_reviews} reviews)</span>`
                : `<span style="font-size: 0.78rem; color: var(--text-muted);">New member</span>`;

            return `
                <div class="user-card">
                    <div>
                        <div class="user-card-top">
                            <img src="${escapeHtml(user.avatar_url)}" alt="${escapeHtml(user.name)}" class="user-card-avatar">
                            <div class="user-card-meta">
                                <h4>${escapeHtml(user.name)}</h4>
                                <div class="user-card-location">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    ${escapeHtml(user.location || 'Remote / Online')} &bull; ${ratingDisplay}
                                </div>
                            </div>
                        </div>

                        <p class="user-card-bio">${escapeHtml(user.bio || 'Skill enthusiast eager to collaborate and exchange knowledge.')}</p>

                        <div class="user-card-skills-section">
                            <div class="skills-sec-label">Offers to Teach:</div>
                            <div class="skill-badge-group">${offeredBadges}</div>
                        </div>

                        <div class="user-card-skills-section">
                            <div class="skills-sec-label">Wants to Learn:</div>
                            <div class="skill-badge-group">${wantedBadges}</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <a href="profile.php?id=${user.id}" class="btn btn-secondary btn-sm" style="flex: 1;">View Profile</a>
                        <button type="button" class="btn btn-primary btn-sm btn-initiate-swap" data-user='${JSON.stringify({
                            id: user.id,
                            name: user.name,
                            skills_offered: user.skills_offered || []
                        })}' style="flex: 1.2;">
                            Propose Swap
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        // Attach Propose Swap Modal Handlers
        document.querySelectorAll('.btn-initiate-swap').forEach(btn => {
            btn.addEventListener('click', () => {
                const userData = JSON.parse(btn.getAttribute('data-user'));
                openSwapModal(userData);
            });
        });
    }

    async function openSwapModal(targetUser) {
        // Fetch current user's profile to know what they can offer
        const myProfileRes = await window.apiFetch('api/users/profile.php');
        if (!myProfileRes.success) {
            window.showToast('Please log in to propose a skill swap.', 'info');
            return;
        }

        const myOffered = myProfileRes.data.skills_offered || [];
        const theirOffered = targetUser.skills_offered || [];

        if (myOffered.length === 0) {
            window.openModal('Add a Skill First', `
                <div class="empty-state">
                    <p class="empty-state-desc">You need to have at least one skill listed under <strong>"Skills I Can Offer"</strong> in your profile before proposing an exchange.</p>
                    <a href="profile.php" class="btn btn-primary" style="margin-top: 1rem;">Go to My Profile</a>
                </div>
            `);
            return;
        }

        const myOptions = myOffered.map(s => `<option value="${s.skill_id}">${escapeHtml(s.skill_name)} (${escapeHtml(s.proficiency)})</option>`).join('');
        const theirOptions = theirOffered.length > 0
            ? theirOffered.map(s => `<option value="${s.id || s.skill_id}">${escapeHtml(s.name || s.skill_name)}</option>`).join('')
            : '<option value="">Any skill they offer</option>';

        const modalHtml = `
            <form id="swap-proposal-form">
                <p style="margin-bottom: 1.25rem; font-size: 0.92rem; color: var(--text-secondary);">
                    Propose a reciprocal skill exchange with <strong>${escapeHtml(targetUser.name)}</strong>.
                </p>

                <div class="form-group">
                    <label class="form-label">You will teach <span class="req">*</span></label>
                    <select name="offered_skill_id" class="form-select" required>
                        ${myOptions}
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">${escapeHtml(targetUser.name)} will teach you <span class="req">*</span></label>
                    <select name="requested_skill_id" class="form-select" required>
                        ${theirOptions}
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Message / Proposal Details</label>
                    <textarea name="message" class="form-textarea" placeholder="Hi! I'd love to exchange lessons. How about 1 hour a week?"></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Swap Request</button>
                </div>
            </form>
        `;

        window.openModal(`Skill Swap Proposal`, modalHtml);

        const form = document.getElementById('swap-proposal-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sending...';

            const payload = {
                receiver_id: targetUser.id,
                offered_skill_id: form.querySelector('[name="offered_skill_id"]').value,
                requested_skill_id: form.querySelector('[name="requested_skill_id"]').value,
                message: form.querySelector('[name="message"]').value.trim()
            };

            const res = await window.apiFetch('api/requests/send.php', {
                method: 'POST',
                body: payload
            });

            if (res.success) {
                window.closeModal();
                window.showToast(res.message, 'success', 'Request Sent!');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Swap Request';
                window.showToast(res.message || 'Failed to send request.', 'error');
            }
        });
    }
});
