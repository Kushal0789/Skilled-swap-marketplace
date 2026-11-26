/**
 * Discovery & Search Client Controller
 * Live Filter, Category Chips, Intent Toggle, Sort, Grid/List View & Swap Proposal Modal
 */

document.addEventListener('DOMContentLoaded', () => {
    const containerEl = document.querySelector('.discover-page-container');
    const isLoggedIn = containerEl ? containerEl.dataset.loggedIn === '1' : false;

    const searchInput = document.getElementById('discover-search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const searchKbdHint = document.getElementById('search-kbd-hint');
    const categoryFilter = document.getElementById('category-filter');
    const proficiencyFilter = document.getElementById('proficiency-filter');
    const locationFilter = document.getElementById('location-filter');
    const sortSelect = document.getElementById('discover-sort');
    const catChips = document.querySelectorAll('.cat-chip-btn');
    const intentBtns = document.querySelectorAll('.intent-btn');
    const viewGridBtn = document.getElementById('view-grid-btn');
    const viewListBtn = document.getElementById('view-list-btn');
    const activeFiltersBar = document.getElementById('active-filters-bar');
    const resultsContainer = document.getElementById('discover-results-grid');
    const resultsCountEl = document.getElementById('results-count-text');

    const VIEW_STORAGE_KEY = 'skillswap_discover_view';
    let debounceTimer = null;
    let selectedSkillType = '';
    let currentUsers = [];
    let usersById = new Map();

    // Restore persisted layout view (grid or list)
    const savedView = localStorage.getItem(VIEW_STORAGE_KEY) || 'grid';
    applyViewMode(savedView);

    // Sync search clear button & shortcut hint
    function syncSearchControls() {
        if (!searchInput) return;
        const hasVal = searchInput.value.trim().length > 0;
        if (clearSearchBtn) clearSearchBtn.classList.toggle('hidden', !hasVal);
        if (searchKbdHint) searchKbdHint.classList.toggle('hidden', hasVal);
    }

    // Sync category chips with select value
    function syncCategoryChips(categoryVal) {
        catChips.forEach(chip => {
            chip.classList.toggle('active', chip.dataset.cat === categoryVal);
        });
    }

    // Apply Grid vs List layout
    function applyViewMode(mode) {
        if (!resultsContainer) return;
        const isList = mode === 'list';
        resultsContainer.classList.toggle('list-view', isList);
        if (viewGridBtn) viewGridBtn.classList.toggle('active', !isList);
        if (viewListBtn) viewListBtn.classList.toggle('active', isList);
        localStorage.setItem(VIEW_STORAGE_KEY, isList ? 'list' : 'grid');
    }

    if (viewGridBtn) viewGridBtn.addEventListener('click', () => applyViewMode('grid'));
    if (viewListBtn) viewListBtn.addEventListener('click', () => applyViewMode('list'));

    // Keyboard shortcut '/' to focus search, Escape to clear
    document.addEventListener('keydown', (e) => {
        const tag = document.activeElement ? document.activeElement.tagName : '';
        const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag);
        if (e.key === '/' && !isTyping && searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
    });

    if (searchInput) {
        syncSearchControls();
        searchInput.addEventListener('input', () => {
            syncSearchControls();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchUsers, 250);
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchInput.value) {
                searchInput.value = '';
                syncSearchControls();
                fetchUsers();
            }
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            syncSearchControls();
            searchInput.focus();
            fetchUsers();
        });
    }

    // Intent segmented toggle (All / Teaching / Learning)
    intentBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            selectedSkillType = btn.dataset.type || '';
            intentBtns.forEach(b => b.classList.toggle('active', b === btn));
            fetchUsers();
        });
    });

    // Category dropdown + chips sync
    if (categoryFilter) {
        categoryFilter.addEventListener('change', () => {
            syncCategoryChips(categoryFilter.value);
            fetchUsers();
        });
    }

    catChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const cat = chip.dataset.cat || '';
            if (categoryFilter) categoryFilter.value = cat;
            syncCategoryChips(cat);
            fetchUsers();
        });
    });

    if (proficiencyFilter) {
        proficiencyFilter.addEventListener('change', fetchUsers);
    }

    if (locationFilter) {
        locationFilter.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchUsers, 280);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            renderSortedUsers();
        });
    }

    function renderSkeletons(count = 6) {
        if (!resultsContainer) return;
        resultsContainer.innerHTML = Array.from({ length: count }).map(() => `
            <div class="skeleton-card" aria-hidden="true">
                <div style="display: flex; align-items: center; gap: 0.9rem;">
                    <div class="skeleton skeleton-circle" style="width: 58px; height: 58px; flex-shrink: 0;"></div>
                    <div style="flex: 1;">
                        <div class="skeleton skeleton-text" style="width: 55%; height: 16px;"></div>
                        <div class="skeleton skeleton-text" style="width: 38%; height: 12px;"></div>
                    </div>
                </div>
                <div class="skeleton skeleton-text" style="width: 92%;"></div>
                <div class="skeleton skeleton-text" style="width: 75%;"></div>
                <div class="skeleton" style="height: 58px; border-radius: var(--radius-md);"></div>
                <div class="skeleton" style="height: 52px; border-radius: var(--radius-md);"></div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem;">
                    <div class="skeleton" style="height: 34px; flex: 1;"></div>
                    <div class="skeleton" style="height: 34px; flex: 1.2;"></div>
                </div>
            </div>
        `).join('');
    }

    function renderActiveFilters() {
        if (!activeFiltersBar) return;
        const tags = [];

        const q = searchInput ? searchInput.value.trim() : '';
        const cat = categoryFilter ? categoryFilter.value : '';
        const prof = proficiencyFilter ? proficiencyFilter.value : '';
        const loc = locationFilter ? locationFilter.value.trim() : '';

        if (q) tags.push({ key: 'q', label: `Search: "${q}"` });
        if (selectedSkillType) {
            tags.push({
                key: 'type',
                label: selectedSkillType === 'OFFER' ? 'Intent: Teaching' : 'Intent: Learning'
            });
        }
        if (cat) tags.push({ key: 'cat', label: `Category: ${cat}` });
        if (prof) tags.push({ key: 'prof', label: `Level: ${prof}` });
        if (loc) tags.push({ key: 'loc', label: `Location: ${loc}` });

        if (tags.length === 0) {
            activeFiltersBar.classList.add('hidden');
            activeFiltersBar.innerHTML = '';
            return;
        }

        activeFiltersBar.classList.remove('hidden');
        activeFiltersBar.innerHTML = tags.map(t => `
            <span class="active-filter-tag">
                ${escapeHtml(t.label)}
                <button type="button" class="active-filter-remove" data-filter-key="${t.key}" aria-label="Remove ${escapeHtml(t.label)}">&times;</button>
            </span>
        `).join('') + `<button type="button" class="clear-all-filters-btn" id="reset-all-filters-btn">Reset all</button>`;

        activeFiltersBar.querySelectorAll('.active-filter-remove').forEach(btn => {
            btn.addEventListener('click', () => clearFilterKey(btn.dataset.filterKey));
        });

        const resetBtn = document.getElementById('reset-all-filters-btn');
        if (resetBtn) resetBtn.addEventListener('click', resetAllFilters);
    }

    function clearFilterKey(key) {
        if (key === 'q' && searchInput) {
            searchInput.value = '';
            syncSearchControls();
        } else if (key === 'type') {
            selectedSkillType = '';
            intentBtns.forEach(b => b.classList.toggle('active', (b.dataset.type || '') === ''));
        } else if (key === 'cat' && categoryFilter) {
            categoryFilter.value = '';
            syncCategoryChips('');
        } else if (key === 'prof' && proficiencyFilter) {
            proficiencyFilter.value = '';
        } else if (key === 'loc' && locationFilter) {
            locationFilter.value = '';
        }
        fetchUsers();
    }

    function resetAllFilters() {
        if (searchInput) {
            searchInput.value = '';
            syncSearchControls();
        }
        selectedSkillType = '';
        intentBtns.forEach(b => b.classList.toggle('active', (b.dataset.type || '') === ''));
        if (categoryFilter) categoryFilter.value = '';
        syncCategoryChips('');
        if (proficiencyFilter) proficiencyFilter.value = '';
        if (locationFilter) locationFilter.value = '';
        if (sortSelect) sortSelect.value = 'newest';
        fetchUsers();
    }

    async function fetchUsers() {
        if (!resultsContainer) return;

        renderActiveFilters();
        renderSkeletons(6);

        const query = searchInput ? searchInput.value.trim() : '';
        const category = categoryFilter ? categoryFilter.value : '';
        const proficiency = proficiencyFilter ? proficiencyFilter.value : '';
        const location = locationFilter ? locationFilter.value.trim() : '';

        const params = new URLSearchParams();
        if (query) params.append('q', query);
        if (category) params.append('category', category);
        if (selectedSkillType) params.append('skill_type', selectedSkillType);
        if (proficiency) params.append('proficiency', proficiency);
        if (location) params.append('location', location);

        const res = await window.apiFetch(`api/users/search.php?${params.toString()}`);

        if (res.success && res.data && Array.isArray(res.data.users)) {
            currentUsers = res.data.users;
            usersById = new Map(currentUsers.map(u => [Number(u.id), u]));
            renderSortedUsers();
        } else {
            currentUsers = [];
            usersById.clear();
            renderEmptyState('Unable to load community members right now.', 'Please check your connection or try refreshing.');
            if (resultsCountEl) resultsCountEl.textContent = '0 members found';
        }
    }

    function renderSortedUsers() {
        const sortBy = sortSelect ? sortSelect.value : 'newest';
        const sorted = [...currentUsers];

        if (sortBy === 'rating') {
            sorted.sort((a, b) => (b.avg_rating - a.avg_rating) || (b.total_reviews - a.total_reviews));
        } else if (sortBy === 'reviews') {
            sorted.sort((a, b) => (b.total_reviews - a.total_reviews) || (b.avg_rating - a.avg_rating));
        } else if (sortBy === 'skills') {
            sorted.sort((a, b) => {
                const countA = (a.skills_offered?.length || 0) + (a.skills_wanted?.length || 0);
                const countB = (b.skills_offered?.length || 0) + (b.skills_wanted?.length || 0);
                return countB - countA;
            });
        }

        if (resultsCountEl) {
            const label = sorted.length === 1 ? 'member' : 'members';
            resultsCountEl.textContent = `Showing ${sorted.length} ${label} available for swap`;
        }

        renderUsers(sorted);
    }

    function renderEmptyState(title, desc) {
        if (!resultsContainer) return;
        resultsContainer.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">🔍</div>
                <h3 class="empty-state-title">${escapeHtml(title)}</h3>
                <p class="empty-state-desc">${escapeHtml(desc)}</p>
                <button type="button" class="btn btn-secondary" id="empty-reset-btn">Clear Filters</button>
            </div>
        `;
        const emptyResetBtn = document.getElementById('empty-reset-btn');
        if (emptyResetBtn) emptyResetBtn.addEventListener('click', resetAllFilters);
    }

    function renderSkillPills(skills, type) {
        if (!skills || skills.length === 0) {
            return `<span class="empty-skills-inline">${type === 'offer' ? 'No skills offered yet' : 'No learning wishlist yet'}</span>`;
        }

        const maxVisible = 4;
        const visible = skills.slice(0, maxVisible);
        const remaining = skills.length - maxVisible;

        const pillsHtml = visible.map(s => {
            if (type === 'offer' && s.proficiency) {
                return `
                    <button type="button"
                            class="skill-badge offer discover-skill-pill"
                            data-skill-name="${escapeHtml(s.name)}"
                            title="Filter by ${escapeHtml(s.name)} (${escapeHtml(s.proficiency)})">
                        <span class="skill-pill-name">${escapeHtml(s.name)}</span>
                        <span class="proficiency-pill proficiency-${escapeHtml(s.proficiency)}"><span class="prof-dot" aria-hidden="true"></span>${escapeHtml(s.proficiency)}</span>
                    </button>
                `;
            }

            return `
                <button type="button"
                        class="skill-badge ${type} discover-skill-pill"
                        data-skill-name="${escapeHtml(s.name)}"
                        title="Filter by ${escapeHtml(s.name)}">
                    ${escapeHtml(s.name)}
                </button>
            `;
        }).join('');

        const moreHtml = remaining > 0
            ? `<span class="skill-more-pill" title="${remaining} more skill(s)">+${remaining} more</span>`
            : '';

        return pillsHtml + moreHtml;
    }

    function renderUsers(users) {
        if (!resultsContainer) return;

        if (users.length === 0) {
            renderEmptyState('No members match your filters', 'Try clearing some filters or searching for another skill like Python, Design, or Guitar.');
            return;
        }

        resultsContainer.innerHTML = users.map((user, idx) => {
            const offeredCount = user.skills_offered ? user.skills_offered.length : 0;
            const wantedCount = user.skills_wanted ? user.skills_wanted.length : 0;
            const ratingHtml = user.total_reviews > 0
                ? `<span class="discover-rating-pill" title="${user.total_reviews} review(s)">★ ${user.avg_rating} (${user.total_reviews})</span>`
                : `<span class="discover-rating-pill is-new">New</span>`;

            const messageHref = isLoggedIn ? `messages.php?with=${user.id}` : 'login.php';

            return `
                <article class="discover-card" style="animation-delay: ${Math.min(idx * 0.035, 0.35)}s;">
                    <div class="discover-card-main">
                        <div>
                            <div class="discover-card-header">
                                <div class="discover-card-identity">
                                    <a href="profile.php?id=${user.id}">
                                        <img src="${escapeHtml(user.avatar_url)}" alt="${escapeHtml(user.name)}" class="discover-avatar" loading="lazy">
                                    </a>
                                    <div class="discover-user-meta">
                                        <a href="profile.php?id=${user.id}" class="discover-user-name">${escapeHtml(user.name)}</a>
                                        ${user.username ? `<span class="discover-user-handle">@${escapeHtml(user.username)}</span>` : ''}
                                        <div class="discover-user-submeta">
                                            <span class="discover-loc-item">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                ${escapeHtml(user.location || 'Remote')}
                                            </span>
                                            ${user.member_since ? `<span>&bull; Since ${escapeHtml(user.member_since)}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                                ${ratingHtml}
                            </div>

                            <p class="discover-card-bio">${escapeHtml(user.bio || 'Skill enthusiast eager to collaborate and exchange practical knowledge.')}</p>
                        </div>

                        <div class="discover-skills-stack">
                            <div class="discover-skill-box">
                                <div class="discover-skill-box-label">
                                    <span><span class="label-dot offer-dot"></span>Offers to Teach</span>
                                    <span>${offeredCount}</span>
                                </div>
                                <div class="skill-badge-group">${renderSkillPills(user.skills_offered, 'offer')}</div>
                            </div>

                            <div class="discover-skill-box">
                                <div class="discover-skill-box-label">
                                    <span><span class="label-dot want-dot"></span>Wants to Learn</span>
                                    <span>${wantedCount}</span>
                                </div>
                                <div class="skill-badge-group">${renderSkillPills(user.skills_wanted, 'want')}</div>
                            </div>
                        </div>
                    </div>

                    <div class="discover-card-footer">
                        <a href="profile.php?id=${user.id}" class="btn btn-secondary btn-sm btn-view-profile">View Profile</a>
                        <a href="${messageHref}" class="btn btn-ghost btn-sm btn-msg-user" title="Message ${escapeHtml(user.name)}" aria-label="Message ${escapeHtml(user.name)}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </a>
                        <button type="button" class="btn btn-primary btn-sm btn-initiate-swap" data-user-id="${user.id}">
                            Propose Swap
                        </button>
                    </div>
                </article>
            `;
        }).join('');

        // Clicking a skill pill filters directly by that skill
        resultsContainer.querySelectorAll('.discover-skill-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const skillName = pill.dataset.skillName || '';
                if (searchInput && skillName) {
                    searchInput.value = skillName;
                    syncSearchControls();
                    fetchUsers();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });

        // Attach Propose Swap Modal Handlers
        resultsContainer.querySelectorAll('.btn-initiate-swap').forEach(btn => {
            btn.addEventListener('click', () => {
                const uid = Number(btn.dataset.userId);
                const userData = usersById.get(uid);
                if (userData) openSwapModal(userData);
            });
        });
    }

    async function openSwapModal(targetUser) {
        if (!isLoggedIn) {
            window.showToast('Please sign in to propose a skill swap.', 'info');
            setTimeout(() => { window.location.href = 'login.php'; }, 900);
            return;
        }

        const myProfileRes = await window.apiFetch('api/users/profile.php');
        if (!myProfileRes.success) {
            window.showToast('Please log in to propose a skill swap.', 'info');
            return;
        }

        const myOffered = myProfileRes.data.skills_offered || [];
        const theirOffered = targetUser.skills_offered || [];

        if (myOffered.length === 0) {
            window.openModal('Add a Skill First', `
                <div class="empty-state" style="padding: 1.5rem 1rem;">
                    <div class="empty-state-icon">🎯</div>
                    <h4 class="empty-state-title">Add an Offered Skill</h4>
                    <p class="empty-state-desc">You need at least one skill listed under <strong>"Skills I Can Offer"</strong> before proposing a swap.</p>
                    <a href="profile-edit.php?tab=skills" class="btn btn-primary">Add Skills Now</a>
                </div>
            `);
            return;
        }

        if (theirOffered.length === 0) {
            window.openModal('No Offered Skills Yet', `
                <div class="empty-state" style="padding: 1.5rem 1rem;">
                    <div class="empty-state-icon">💬</div>
                    <h4 class="empty-state-title">${escapeHtml(targetUser.name)} hasn't listed offered skills yet</h4>
                    <p class="empty-state-desc">You can send them a direct message to ask what skills they'd like to teach.</p>
                    <a href="messages.php?with=${targetUser.id}" class="btn btn-primary">Send Direct Message</a>
                </div>
            `);
            return;
        }

        const myOptions = myOffered.map(s => `<option value="${s.skill_id}">${escapeHtml(s.skill_name)} (${escapeHtml(s.proficiency)})</option>`).join('');
        const theirOptions = theirOffered.length > 0
            ? theirOffered.map(s => `<option value="${s.id || s.skill_id}">${escapeHtml(s.name || s.skill_name)} (${escapeHtml(s.proficiency || 'Member')})</option>`).join('')
            : '<option value="">Any skill they offer</option>';

        const modalHtml = `
            <div class="modal-swap-partner">
                <img src="${escapeHtml(targetUser.avatar_url)}" alt="${escapeHtml(targetUser.name)}">
                <div>
                    <div class="modal-swap-partner-name">${escapeHtml(targetUser.name)}</div>
                    <div class="modal-swap-partner-sub">${escapeHtml(targetUser.location || 'Remote')} &bull; ${theirOffered.length} offered skill(s)</div>
                </div>
            </div>

            <form id="swap-proposal-form">
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
                    <textarea name="message" class="form-textarea" placeholder="Hi ${escapeHtml(targetUser.name.split(' ')[0])}! I'd love to exchange lessons. How about 1 hour a week?"></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.75rem -1.75rem; padding: 1rem 1.75rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Swap Request</button>
                </div>
            </form>
        `;

        window.openModal('Propose Skill Swap', modalHtml);

        const form = document.getElementById('swap-proposal-form');
        if (!form) return;

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

    // Load initial results
    fetchUsers();
});
