/**
 * Skill Swap Marketplace - Report & Feedback Client Controller
 * Category dynamics, live character counts, user preselection, and submission history.
 */

(function () {
    'use strict';

    const categoryIcons = {
        'Bug Report':       '🐞',
        'User Report':      '👤',
        'Technical Issue':  '⚙️',
        'Suggestion':       '💡',
        'General Feedback': '💬',
        'Other':            '🌐'
    };

    function initReportPage() {
        const form                = document.getElementById('report-feedback-form');
        const categorySelect      = document.getElementById('report-category-select');
        const categoryIconPreview = document.getElementById('category-icon-preview');
        const reportedUserGroup   = document.getElementById('reported-user-group');
        const reportedUserSelect  = document.getElementById('reported-user-select');
        const reportedUserBadge   = document.getElementById('reported-user-badge');
        const subjectInput        = document.getElementById('report-subject-input');
        const subjectCountEl      = document.getElementById('subject-char-count');
        const descTextarea        = document.getElementById('report-desc-textarea');
        const descCountEl         = document.getElementById('desc-char-count');
        const submitBtn           = document.getElementById('btn-submit-report');
        const submissionsListEl   = document.getElementById('my-submissions-list');

        if (!form) return;

        // 1. URL Query Parameter Pre-population (?type=... & ?user_id=...)
        const urlParams = new URLSearchParams(window.location.search);
        const paramUserId = urlParams.get('user_id');
        const paramType = urlParams.get('type');

        if (paramType) {
            const matchOption = Array.from(categorySelect.options).find(opt =>
                opt.value.toLowerCase() === paramType.toLowerCase() ||
                opt.value.toLowerCase().replace(/\s+/g, '_') === paramType.toLowerCase()
            );
            if (matchOption) {
                categorySelect.value = matchOption.value;
            }
        }

        if (paramUserId) {
            categorySelect.value = 'User Report';
            if (reportedUserSelect) {
                reportedUserSelect.value = paramUserId;
            }
        }

        // 2. Category Change Handler & Dynamics
        function handleCategoryChange() {
            const selectedVal = categorySelect.value;

            // Update category icon
            if (categoryIconPreview) {
                categoryIconPreview.textContent = categoryIcons[selectedVal] || '📋';
            }

            // User Report specific state
            const isUserReport = selectedVal === 'User Report';
            if (reportedUserGroup) {
                reportedUserGroup.classList.toggle('highlighted', isUserReport);
            }
            if (reportedUserBadge) {
                reportedUserBadge.textContent = isUserReport ? 'Required for User Report' : 'Optional';
            }
            if (reportedUserSelect) {
                reportedUserSelect.required = isUserReport;
            }
        }

        categorySelect.addEventListener('change', handleCategoryChange);
        handleCategoryChange(); // initial run

        // 3. Live Character Counters
        if (subjectInput && subjectCountEl) {
            subjectInput.addEventListener('input', () => {
                subjectCountEl.textContent = `${subjectInput.value.length} / 150`;
            });
        }

        if (descTextarea && descCountEl) {
            descTextarea.addEventListener('input', () => {
                const len = descTextarea.value.length;
                descCountEl.textContent = `${len} characters`;
                if (len < 10) {
                    descCountEl.style.color = 'var(--danger)';
                } else {
                    descCountEl.style.color = 'var(--text-muted)';
                }
            });
        }

        // 4. Form Submission Handler
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const category = categorySelect.value;
            const subject = subjectInput.value.trim();
            const desc = descTextarea.value.trim();
            const targetUser = reportedUserSelect ? parseInt(reportedUserSelect.value, 10) : 0;

            if (!subject) {
                window.showToast('Please enter a subject or title.', 'error');
                subjectInput.focus();
                return;
            }

            if (desc.length < 10) {
                window.showToast('Please provide at least 10 characters in your detailed description.', 'error');
                descTextarea.focus();
                return;
            }

            if (category === 'User Report' && (!targetUser || targetUser <= 0)) {
                window.showToast('Please select the member you want to report.', 'error');
                if (reportedUserSelect) reportedUserSelect.focus();
                return;
            }

            // Disable button and show spinner
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner spinner-primary"></span> Submitting…';

            const payload = {
                category: category,
                title: subject,
                description: desc,
                reported_user_id: targetUser > 0 ? targetUser : null
            };

            const res = await window.apiFetch('api/reports/create.php', {
                method: 'POST',
                body: payload
            });

            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <span>Submit Report &amp; Feedback</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            `;

            if (res.success) {
                window.showToast(res.message || 'Report submitted successfully!', 'success', 'Thank You!');
                form.reset();
                categorySelect.value = 'Bug Report';
                handleCategoryChange();
                if (subjectCountEl) subjectCountEl.textContent = '0 / 150';
                if (descCountEl) descCountEl.textContent = '0 characters';
                loadMySubmissions();
            } else {
                const errMsg = res.errors
                    ? Object.values(res.errors).join(' ')
                    : (res.message || 'Unable to submit report.');
                window.showToast(errMsg, 'error');
            }
        });

        // 5. Load User's Past Submissions
        async function loadMySubmissions() {
            if (!submissionsListEl) return;

            const res = await window.apiFetch('api/reports/list.php');
            if (!res.success || !res.data || res.data.length === 0) {
                submissionsListEl.innerHTML = `
                    <div style="text-align:center;padding:1.5rem 0.5rem;color:var(--text-muted);font-size:0.85rem;">
                        No past submissions yet. Your feedback and reports will be listed here.
                    </div>`;
                return;
            }

            const statusClassMap = {
                pending:   'pending',
                resolved:  'accepted',
                dismissed: 'cancelled'
            };

            submissionsListEl.innerHTML = res.data.map(item => `
                <div class="my-report-item">
                    <div class="my-report-top">
                        <span class="my-report-type">${escapeHtml(item.report_type)}</span>
                        <span class="status-badge ${statusClassMap[item.status] || 'pending'}" style="font-size:0.7rem;padding:1px 7px;">
                            ${escapeHtml(item.status)}
                        </span>
                    </div>
                    <div class="my-report-subject">${escapeHtml(item.title || item.reason)}</div>
                    <div class="my-report-meta">
                        <span>Submitted ${escapeHtml(item.time_ago)}</span>
                        ${item.reported_name ? ` &bull; <span>Regarding @${escapeHtml(item.reported_username)}</span>` : ''}
                    </div>
                </div>
            `).join('');
        }

        loadMySubmissions();

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportPage);
    } else {
        initReportPage();
    }
})();
