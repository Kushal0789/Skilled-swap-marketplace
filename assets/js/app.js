/**
 * Skill Swap Marketplace - Core Application Client Library
 * Theme Management, Toast Notifications, Dynamic Modal, and Secure API Fetching
 */

(function () {
    'use strict';

    // 1. THEME MANAGEMENT (Dark / Light)
    const THEME_KEY = 'skillswap_theme';
    const htmlElement = document.documentElement;
    const themeBtn = document.getElementById('theme-toggle-btn');

    function applyTheme(theme) {
        htmlElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
    }

    // Initialize theme from storage or system preference
    const savedTheme = localStorage.getItem(THEME_KEY) || 
        (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    applyTheme(savedTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme') || 'dark';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
        });
    }

    // 2. CSRF TOKEN & API FETCH WRAPPER
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    window.apiFetch = async function (url, options = {}) {
        options.headers = options.headers || {};
        options.headers['X-CSRF-Token'] = getCsrfToken();

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, options);
            const data = await response.json();

            if (response.status === 401) {
                window.showToast('Please log in to continue.', 'error', 'Session Expired');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            }

            return data;
        } catch (error) {
            console.error('[API Fetch Error]', error);
            return {
                success: false,
                message: 'Network communication error. Please try again.',
                errors: [error.message]
            };
        }
    };

    // 3. TOAST NOTIFICATIONS
    const toastContainer = document.getElementById('toast-container');

    window.showToast = function (message, type = 'info', title = null, duration = 4000) {
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const titles = {
            success: 'Success',
            error: 'Error',
            info: 'Notice',
            warning: 'Warning'
        };

        const displayTitle = title || titles[type] || 'Notice';

        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-title">${escapeHtml(displayTitle)}</div>
                <div class="toast-msg">${escapeHtml(message)}</div>
            </div>
            <button type="button" class="toast-close" aria-label="Close">&times;</button>
        `;

        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });

        toastContainer.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(20px)';
                    setTimeout(() => toast.remove(), 250);
                }
            }, duration);
        }
    };

    // 4. DYNAMIC MODAL DIALOG
    const modalBackdrop = document.getElementById('app-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    window.openModal = function (title, contentHtml) {
        if (!modalBackdrop) return;
        modalTitle.textContent = title;
        modalBody.innerHTML = contentHtml;
        modalBackdrop.classList.remove('hidden');
    };

    window.closeModal = function () {
        if (!modalBackdrop) return;
        modalBackdrop.classList.add('hidden');
        modalBody.innerHTML = '';
    };

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', window.closeModal);
    }

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) {
                window.closeModal();
            }
        });
    }

    // 5. NAVBAR DROPDOWNS & MOBILE DRAWER
    const userMenuBtn = document.getElementById('user-avatar-menu-btn');
    const userDropdownPanel = document.getElementById('user-dropdown-panel');
    const notifBellBtn = document.getElementById('notif-bell-btn');
    const notifPanel = document.getElementById('notif-panel');
    const notifListContainer = document.getElementById('notif-list-container');
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    const globalNotifCount = document.getElementById('global-notif-count');
    const mobileToggleBtn = document.getElementById('mobile-menu-toggle-btn');
    const mobileDrawer = document.getElementById('mobile-drawer');

    // Toggle User Menu
    if (userMenuBtn && userDropdownPanel) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdownPanel.classList.toggle('hidden');
            if (notifPanel) notifPanel.classList.add('hidden');
        });
    }

    // Toggle Notifications Panel
    if (notifBellBtn && notifPanel) {
        notifBellBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            notifPanel.classList.toggle('hidden');
            if (userDropdownPanel) userDropdownPanel.classList.add('hidden');

            if (!notifPanel.classList.contains('hidden')) {
                await loadNotifications();
            }
        });
    }

    async function loadNotifications() {
        if (!notifListContainer) return;
        notifListContainer.innerHTML = '<div class="notif-empty-state"><span class="spinner spinner-primary"></span> Loading...</div>';

        const res = await window.apiFetch('api/notifications/list.php');
        if (res.success && res.data && res.data.length > 0) {
            notifListContainer.innerHTML = res.data.map(n => `
                <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}">
                    <div class="notif-title">${escapeHtml(n.title)}</div>
                    <div class="notif-msg">${escapeHtml(n.message)}</div>
                    <div class="notif-time">${escapeHtml(n.time_ago)}</div>
                </div>
            `).join('');
        } else {
            notifListContainer.innerHTML = '<div class="notif-empty-state" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No new notifications</div>';
        }
    }

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async () => {
            const res = await window.apiFetch('api/notifications/read.php', {
                method: 'POST',
                body: { all: true }
            });
            if (res.success) {
                if (globalNotifCount) {
                    globalNotifCount.classList.add('hidden');
                    globalNotifCount.textContent = '0';
                }
                document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                window.showToast('All notifications marked as read', 'success');
            }
        });
    }

    // Mobile Hamburger
    if (mobileToggleBtn && mobileDrawer) {
        mobileToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            mobileDrawer.classList.toggle('hidden');
        });
    }

    // Close open popups when clicking outside
    document.addEventListener('click', () => {
        if (userDropdownPanel) userDropdownPanel.classList.add('hidden');
        if (notifPanel) notifPanel.classList.add('hidden');
    });

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    window.escapeHtml = escapeHtml;

})();
