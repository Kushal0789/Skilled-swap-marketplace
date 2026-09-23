/**
 * User Dashboard Client Controller
 * Dynamic metrics, quick two-way match preview, and pending swaps
 */

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardWidgets();

    async function loadDashboardWidgets() {
        const matchesWidget = document.getElementById('dash-top-matches');
        const requestsWidget = document.getElementById('dash-pending-requests');

        // Load matches preview
        if (matchesWidget) {
            const mRes = await window.apiFetch('api/matches/find.php');
            if (mRes.success && mRes.data) {
                const matches = mRes.data.two_way_matches || [];
                if (matches.length === 0) {
                    matchesWidget.innerHTML = `
                        <div class="empty-state" style="padding: 1.5rem;">
                            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 0.75rem;">No exact two-way matches found yet.</p>
                            <a href="matches.php" class="btn btn-secondary btn-sm">Check One-Way Recommendations</a>
                        </div>
                    `;
                } else {
                    matchesWidget.innerHTML = matches.slice(0, 3).map(m => `
                        <div class="match-card two-way-match" style="margin-bottom: 0.75rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="${escapeHtml(m.avatar_url)}" alt="${escapeHtml(m.name)}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.95rem;">${escapeHtml(m.name)}</div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">${escapeHtml(m.location || 'Remote')}</div>
                                    </div>
                                </div>
                                <span class="badge-pill" style="background: var(--gradient-brand); color: white; font-size: 0.72rem; padding: 2px 8px; font-weight: 700;">100% Match</span>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 0.6rem; color: var(--text-secondary);">
                                You teach <strong>${escapeHtml(m.primary_exchange.you_teach)}</strong> &bull; Learn <strong>${escapeHtml(m.primary_exchange.you_learn)}</strong>
                            </div>
                            <div style="margin-top: 0.75rem; text-align: right;">
                                <a href="matches.php" class="btn btn-primary btn-sm">View Exchange &rarr;</a>
                            </div>
                        </div>
                    `).join('');
                }
            }
        }

        // Load incoming requests preview
        if (requestsWidget) {
            const rRes = await window.apiFetch('api/requests/list.php');
            if (rRes.success && rRes.data) {
                const incoming = (rRes.data.incoming || []).filter(r => r.status === 'pending');
                if (incoming.length === 0) {
                    requestsWidget.innerHTML = `
                        <div class="empty-state" style="padding: 1.5rem;">
                            <p style="font-size: 0.88rem; color: var(--text-muted);">No pending incoming swap proposals.</p>
                        </div>
                    `;
                } else {
                    requestsWidget.innerHTML = incoming.slice(0, 3).map(r => `
                        <div style="background: var(--bg-surface-elevated); padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="font-size: 0.9rem;">${escapeHtml(r.sender_name)}</strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    Offers: <span style="color: var(--success); font-weight: 600;">${escapeHtml(r.offered_skill_name)}</span> &bull; Wants: <span>${escapeHtml(r.requested_skill_name)}</span>
                                </div>
                            </div>
                            <a href="requests.php" class="btn btn-sm btn-outline">Review</a>
                        </div>
                    `).join('');
                }
            }
        }
    }
});
