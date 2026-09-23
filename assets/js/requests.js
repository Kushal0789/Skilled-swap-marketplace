/**
 * Swap Requests Client Controller
 * Tabbed Management for Incoming & Outgoing Requests
 */

document.addEventListener('DOMContentLoaded', () => {
    const incomingList = document.getElementById('incoming-requests-list');
    const outgoingList = document.getElementById('outgoing-requests-list');
    const tabBtns = document.querySelectorAll('.requests-tab-btn');
    const tabPanes = document.querySelectorAll('.requests-tab-pane');

    loadRequests();

    // Tab switching
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.add('hidden'));

            btn.classList.add('active');
            const activePane = document.getElementById(`tab-${targetTab}`);
            if (activePane) activePane.classList.remove('hidden');
        });
    });

    async function loadRequests() {
        const res = await window.apiFetch('api/requests/list.php');

        if (!res.success) {
            window.showToast('Unable to load swap requests.', 'error');
            return;
        }

        const { incoming, outgoing, pending_in_count } = res.data;

        // Update badge
        const badge = document.getElementById('incoming-badge-count');
        if (badge) {
            badge.textContent = pending_in_count || 0;
            if (pending_in_count > 0) badge.classList.remove('hidden');
            else badge.classList.add('hidden');
        }

        // Render Incoming
        if (incomingList) {
            if (incoming.length === 0) {
                incomingList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📥</div>
                        <h3 class="empty-state-title">No incoming swap requests</h3>
                        <p class="empty-state-desc">When other members find your skills and want to exchange knowledge, their proposals will appear here.</p>
                    </div>
                `;
            } else {
                incomingList.innerHTML = incoming.map(r => renderIncomingCard(r)).join('');
            }
        }

        // Render Outgoing
        if (outgoingList) {
            if (outgoing.length === 0) {
                outgoingList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📤</div>
                        <h3 class="empty-state-title">No outgoing requests</h3>
                        <p class="empty-state-desc">You haven't proposed any skill swaps yet. Discover talented peers and send your first request!</p>
                        <a href="discover.php" class="btn btn-primary" style="margin-top: 1rem;">Discover Skills</a>
                    </div>
                `;
            } else {
                outgoingList.innerHTML = outgoing.map(r => renderOutgoingCard(r)).join('');
            }
        }

        attachRequestActions();
    }

    function renderIncomingCard(req) {
        let actionControls = '';

        if (req.status === 'pending') {
            actionControls = `
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-danger btn-sm btn-reject-request" data-id="${req.id}">Decline</button>
                    <button class="btn btn-success btn-sm btn-accept-request" data-id="${req.id}">Accept Swap</button>
                </div>
            `;
        } else if (req.status === 'accepted') {
            actionControls = `
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-outline btn-sm btn-leave-review" data-user-id="${req.sender_id}" data-user-name="${escapeHtml(req.sender_name)}">Leave Review</button>
                    <a href="messages.php" class="btn btn-primary btn-sm">Chat Now</a>
                </div>
            `;
        }

        return `
            <div class="card card-hover" style="margin-bottom: 1rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <img src="${escapeHtml(req.sender_avatar_url)}" alt="${escapeHtml(req.sender_name)}" class="user-card-avatar" style="width: 48px; height: 48px;">
                        <div>
                            <h4 style="font-size: 1.05rem;">${escapeHtml(req.sender_name)}</h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(req.time_ago)} &bull; ${escapeHtml(req.sender_location || 'Remote')}</p>
                        </div>
                    </div>
                    <span class="status-badge ${req.status}">${escapeHtml(req.status)}</span>
                </div>

                <div class="match-exchange-visual" style="margin: 1rem 0; padding: 0.75rem 1rem;">
                    <div class="exchange-node">
                        <div class="exchange-node-title">They Teach You</div>
                        <div class="exchange-node-skill" style="color: var(--success);">${escapeHtml(req.offered_skill_name)}</div>
                    </div>
                    <div class="exchange-arrows">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 10 12 15 17 10"></polyline><polyline points="17 14 12 9 7 14"></polyline></svg>
                    </div>
                    <div class="exchange-node">
                        <div class="exchange-node-title">You Teach Them</div>
                        <div class="exchange-node-skill" style="color: var(--primary);">${escapeHtml(req.requested_skill_name)}</div>
                    </div>
                </div>

                ${req.message ? `<p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem; background: var(--bg-surface-elevated); padding: 0.75rem; border-radius: var(--radius-md); font-style: italic;">"${escapeHtml(req.message)}"</p>` : ''}

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <a href="profile.php?id=${req.sender_id}" class="btn btn-ghost btn-sm">View Profile</a>
                    ${actionControls}
                </div>
            </div>
        `;
    }

    function renderOutgoingCard(req) {
        let actionControls = '';

        if (req.status === 'pending') {
            actionControls = `
                <button class="btn btn-ghost btn-sm text-danger btn-cancel-request" data-id="${req.id}">Cancel Proposal</button>
            `;
        } else if (req.status === 'accepted') {
            actionControls = `
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-outline btn-sm btn-leave-review" data-user-id="${req.receiver_id}" data-user-name="${escapeHtml(req.receiver_name)}">Leave Review</button>
                    <a href="messages.php" class="btn btn-primary btn-sm">Chat</a>
                </div>
            `;
        }

        return `
            <div class="card card-hover" style="margin-bottom: 1rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <img src="${escapeHtml(req.receiver_avatar_url)}" alt="${escapeHtml(req.receiver_name)}" class="user-card-avatar" style="width: 48px; height: 48px;">
                        <div>
                            <h4 style="font-size: 1.05rem;">Proposal to ${escapeHtml(req.receiver_name)}</h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(req.time_ago)}</p>
                        </div>
                    </div>
                    <span class="status-badge ${req.status}">${escapeHtml(req.status)}</span>
                </div>

                <div class="match-exchange-visual" style="margin: 1rem 0; padding: 0.75rem 1rem;">
                    <div class="exchange-node">
                        <div class="exchange-node-title">You Teach</div>
                        <div class="exchange-node-skill" style="color: var(--primary);">${escapeHtml(req.offered_skill_name)}</div>
                    </div>
                    <div class="exchange-arrows">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 10 12 15 17 10"></polyline><polyline points="17 14 12 9 7 14"></polyline></svg>
                    </div>
                    <div class="exchange-node">
                        <div class="exchange-node-title">You Learn</div>
                        <div class="exchange-node-skill" style="color: var(--success);">${escapeHtml(req.requested_skill_name)}</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    ${actionControls}
                </div>
            </div>
        `;
    }

    function attachRequestActions() {
        // Accept
        document.querySelectorAll('.btn-accept-request').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                btn.disabled = true;
                const res = await window.apiFetch('api/requests/accept.php', {
                    method: 'POST',
                    body: { request_id: id }
                });
                if (res.success) {
                    window.showToast('Swap request accepted! A new chat has been created.', 'success', 'Accepted');
                    loadRequests();
                } else {
                    btn.disabled = false;
                    window.showToast(res.message, 'error');
                }
            });
        });

        // Reject
        document.querySelectorAll('.btn-reject-request').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to decline this request?')) return;
                const id = btn.getAttribute('data-id');
                const res = await window.apiFetch('api/requests/reject.php', {
                    method: 'POST',
                    body: { request_id: id }
                });
                if (res.success) {
                    window.showToast('Request declined.', 'info');
                    loadRequests();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });

        // Cancel
        document.querySelectorAll('.btn-cancel-request').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const res = await window.apiFetch('api/requests/cancel.php', {
                    method: 'POST',
                    body: { request_id: id }
                });
                if (res.success) {
                    window.showToast('Request cancelled.', 'info');
                    loadRequests();
                } else {
                    window.showToast(res.message, 'error');
                }
            });
        });

        // Leave Review Modal
        document.querySelectorAll('.btn-leave-review').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetUserId = btn.getAttribute('data-user-id');
                const targetUserName = btn.getAttribute('data-user-name');
                openReviewModal(targetUserId, targetUserName);
            });
        });
    }

    function openReviewModal(targetUserId, targetUserName) {
        const modalHtml = `
            <form id="review-submission-form">
                <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    Rate your learning & teaching experience with <strong>${escapeHtml(targetUserName)}</strong>.
                </p>

                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select" required>
                        <option value="5">★★★★★ - Excellent (5 Stars)</option>
                        <option value="4">★★★★☆ - Great (4 Stars)</option>
                        <option value="3">★★★☆☆ - Good (3 Stars)</option>
                        <option value="2">★★☆☆☆ - Fair (2 Stars)</option>
                        <option value="1">★☆☆☆☆ - Poor (1 Star)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Review Comment</label>
                    <textarea name="comment" class="form-textarea" placeholder="Describe their teaching style, punctuality, and what you learned..." required></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        `;

        window.openModal(`Review ${targetUserName}`, modalHtml);

        const form = document.getElementById('review-submission-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const res = await window.apiFetch('api/reviews/add.php', {
                method: 'POST',
                body: {
                    reviewed_user_id: targetUserId,
                    rating: form.querySelector('[name="rating"]').value,
                    comment: form.querySelector('[name="comment"]').value.trim()
                }
            });

            if (res.success) {
                window.closeModal();
                window.showToast(res.message, 'success', 'Review Posted');
            } else {
                submitBtn.disabled = false;
                window.showToast(res.message, 'error');
            }
        });
    }
});
