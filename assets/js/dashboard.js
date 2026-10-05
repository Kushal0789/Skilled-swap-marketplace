/**
 * User Dashboard Client Controller
 * Dynamic metrics, quick two-way match preview, and pending swaps
 */

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardWidgets();

    async function loadDashboardWidgets() {
        const matchesWidget = document.getElementById('dash-top-matches');
        const requestsWidget = document.getElementById('dash-pending-requests');

        // Show skeleton loading states
        if (matchesWidget) {
            matchesWidget.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                    <div class="skeleton" style="height: 90px; border-radius: var(--radius-md);"></div>
                    <div class="skeleton" style="height: 90px; border-radius: var(--radius-md);"></div>
                </div>
            `;
        }

        if (requestsWidget) {
            requestsWidget.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div class="skeleton" style="height: 60px; border-radius: var(--radius-md);"></div>
                    <div class="skeleton" style="height: 60px; border-radius: var(--radius-md);"></div>
                </div>
            `;
        }

        // Load matches preview
        if (matchesWidget) {
            try {
                const mRes = await window.apiFetch('api/matches/find.php');
                if (mRes.success && mRes.data) {
                    const matches = mRes.data.two_way_matches || [];
                    if (matches.length === 0) {
                        matchesWidget.innerHTML = `
                            <div class="empty-state" style="padding: 2rem 1rem;">
                                <div class="empty-state-icon" style="width: 52px; height: 52px; font-size: 1.5rem; margin-bottom: 0.75rem;">🤝</div>
                                <h4 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.25rem;">No exact 2-way matches yet</h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; max-width: 320px;">
                                    Add more skills to your wishlist or check out one-way mentoring matches.
                                </p>
                                <a href="matches.php" class="btn btn-secondary btn-sm">Explore Matching Engine &rarr;</a>
                            </div>
                        `;
                    } else {
                        matchesWidget.innerHTML = matches.slice(0, 3).map(m => `
                            <div class="match-card two-way-match" style="margin-bottom: 0.85rem; padding: 1.15rem; border-radius: var(--radius-lg);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <img src="${escapeHtml(m.avatar_url)}" alt="${escapeHtml(m.name)}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                                        <div>
                                            <div style="font-weight: 700; font-size: 0.98rem; color: var(--text-primary);">${escapeHtml(m.name)}</div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.25rem;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                ${escapeHtml(m.location || 'Online')}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge accepted" style="font-size: 0.72rem; padding: 2px 8px;">100% Match</span>
                                </div>
                                <div style="font-size: 0.86rem; margin: 0.85rem 0 0.6rem; color: var(--text-secondary); background: var(--bg-surface-elevated); padding: 8px 12px; border-radius: var(--radius-md);">
                                    You teach <strong style="color: var(--primary);">${escapeHtml(m.primary_exchange.you_teach)}</strong> &bull; Learn <strong style="color: var(--success);">${escapeHtml(m.primary_exchange.you_learn)}</strong>
                                </div>
                                <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end;">
                                    <a href="matches.php" class="btn btn-primary btn-sm">View Match Details &rarr;</a>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } catch (err) {
                matchesWidget.innerHTML = '<div style="color: var(--danger); font-size: 0.85rem; padding: 1rem;">Failed to load matches.</div>';
            }
        }

        // Load incoming requests preview
        if (requestsWidget) {
            try {
                const rRes = await window.apiFetch('api/requests/list.php');
                if (rRes.success && rRes.data) {
                    const incoming = (rRes.data.incoming || []).filter(r => r.status === 'pending');
                    if (incoming.length === 0) {
                        requestsWidget.innerHTML = `
                            <div class="empty-state" style="padding: 2rem 1rem;">
                                <div class="empty-state-icon" style="width: 52px; height: 52px; font-size: 1.5rem; margin-bottom: 0.75rem;">📥</div>
                                <h4 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.25rem;">No pending proposals</h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); max-width: 300px;">When members want to swap with you, their requests will appear here.</p>
                            </div>
                        `;
                    } else {
                        requestsWidget.innerHTML = incoming.slice(0, 3).map(r => `
                            <div style="background: var(--bg-surface-elevated); padding: 0.9rem 1.15rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                                <div>
                                    <strong style="font-size: 0.95rem; color: var(--text-primary);">${escapeHtml(r.sender_name)}</strong>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">
                                        Offers: <span style="color: var(--success); font-weight: 700;">${escapeHtml(r.offered_skill_name)}</span> &bull; Wants: <span>${escapeHtml(r.requested_skill_name)}</span>
                                    </div>
                                </div>
                                <a href="requests.php" class="btn btn-sm btn-primary">Review &rarr;</a>
                            </div>
                        `).join('');
                    }
                }
            } catch (err) {
                requestsWidget.innerHTML = '<div style="color: var(--danger); font-size: 0.85rem; padding: 1rem;">Failed to load requests.</div>';
            }
        }
    }
});
