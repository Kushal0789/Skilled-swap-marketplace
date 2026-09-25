/**
 * User Profile & Skill Management Controller
 * Dynamic Social Links Management, Live Previews, Avatar Upload & Dynamic Skill CRUD
 */

document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profile-info-form');
    const avatarInput = document.getElementById('avatar-file-input');
    const avatarPreviewImg = document.getElementById('profile-avatar-preview');
    const heroSocialLinksBar = document.getElementById('profile-social-links-bar');
    const addOfferedSkillBtn = document.getElementById('btn-add-offered-skill');
    const addWantedSkillBtn = document.getElementById('btn-add-wanted-skill');
    const toggleEditModeBtn = document.getElementById('btn-toggle-edit-mode');
    const addSocialShortcutBtn = document.getElementById('btn-add-social-shortcut');
    const quickAddSkillForm = document.getElementById('quick-add-skill-form');
    const dynamicSkillsSummary = document.getElementById('dynamic-skills-summary');

    // Social Platforms Metadata
    const platformsMeta = {
        whatsapp: {
            name: 'WhatsApp',
            color: '#25D366',
            badge: 'Direct Chat',
            isDirectChat: true,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.764.819 2.791.819h.002c3.18 0 5.767-2.586 5.768-5.766 0-3.18-2.587-5.765-5.77-5.765zm3.376 8.204c-.149.418-.753.79-1.045.84-.282.049-.646.079-2.072-.511-1.824-.755-3.003-2.607-3.094-2.729-.091-.122-.74-1.002-.74-1.928s.475-1.378.653-1.564c.178-.186.388-.232.518-.232.13 0 .259.002.373.008.119.006.279-.045.437.334.162.388.552 1.345.6 1.442.049.097.081.21.016.339-.065.129-.098.21-.194.323-.098.113-.205.253-.293.34-.097.097-.199.202-.086.396.113.194.502.828 1.077 1.341.741.66 1.365.865 1.559.962.194.097.307.081.421-.049.113-.129.486-.566.615-.76.13-.194.259-.162.437-.097.178.065 1.134.535 1.328.632.194.097.324.146.372.227.049.081.049.469-.1 1.015zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.662 1.435 5.177L2 22l4.974-1.399C8.423 21.498 10.154 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                const clean = v.replace(/[^0-9]/g, '');
                return clean ? `https://wa.me/${clean}` : '';
            }
        },
        discord: {
            name: 'Discord',
            color: '#5865F2',
            badge: 'Community / Chat',
            isDirectChat: true,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
            isCopy: true,
            formatUrl: (v) => v.startsWith('http') ? v : `#discord:${v.trim()}`
        },
        linkedin: {
            name: 'LinkedIn',
            color: '#0A66C2',
            badge: 'Network',
            isDirectChat: false,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                if (v.includes('linkedin.com')) return `https://${v.replace(/^\/+/, '')}`;
                return `https://linkedin.com/in/${v.replace(/^@/, '').trim()}`;
            }
        },
        github: {
            name: 'GitHub',
            color: '#f0f6fc',
            badge: 'Code / Repos',
            isDirectChat: false,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                if (v.includes('github.com')) return `https://${v.replace(/^\/+/, '')}`;
                return `https://github.com/${v.replace(/^@/, '').trim()}`;
            }
        },
        twitter: {
            name: 'X (Twitter)',
            color: '#e2e8f0',
            badge: 'Social',
            isDirectChat: false,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                if (v.includes('x.com') || v.includes('twitter.com')) return `https://${v.replace(/^\/+/, '')}`;
                return `https://x.com/${v.replace(/^@/, '').trim()}`;
            }
        },
        instagram: {
            name: 'Instagram',
            color: '#E4405F',
            badge: 'Photos / DMs',
            isDirectChat: false,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                if (v.includes('instagram.com')) return `https://${v.replace(/^\/+/, '')}`;
                return `https://instagram.com/${v.replace(/^@/, '').trim()}`;
            }
        },
        facebook: {
            name: 'Facebook',
            color: '#1877F2',
            badge: 'Profile',
            isDirectChat: true,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            formatUrl: (v) => {
                if (v.startsWith('http')) return v;
                if (v.includes('facebook.com')) return `https://${v.replace(/^\/+/, '')}`;
                return `https://facebook.com/${v.replace(/^@/, '').trim()}`;
            }
        },
        website: {
            name: 'Portfolio / Website',
            color: 'var(--primary)',
            badge: 'Portfolio',
            isDirectChat: false,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
            formatUrl: (v) => v.startsWith('http') ? v : `https://${v.replace(/^\/+/, '')}`
        },
        contact_email: {
            name: 'Email',
            color: '#EA4335',
            badge: 'Direct Mail',
            isDirectChat: true,
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
            formatUrl: (v) => `mailto:${v.trim()}`
        }
    };

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Helper: Copy Discord tag to clipboard with visual badge state
    function bindDiscordCopyButtons() {
        document.querySelectorAll('.social-link-copy').forEach(btn => {
            btn.onclick = async () => {
                const tag = btn.getAttribute('data-copy');
                if (!tag) return;
                try {
                    await navigator.clipboard.writeText(tag);
                    window.showToast(`Discord handle "${tag}" copied to clipboard!`, 'success', 'Copied');
                } catch (err) {
                    const temp = document.createElement('textarea');
                    temp.value = tag;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    window.showToast(`Discord handle "${tag}" copied!`, 'success', 'Copied');
                }
                const badge = btn.querySelector('.social-copy-badge');
                if (badge) {
                    const originalText = badge.textContent;
                    badge.textContent = '✓ Copied!';
                    badge.classList.add('badge-copied-active');
                    setTimeout(() => {
                        badge.textContent = originalText;
                        badge.classList.remove('badge-copied-active');
                    }, 2000);
                }
            };
        });
    }
    bindDiscordCopyButtons();

    // Helper: Copy username chip
    const copyUsernameBtn = document.getElementById('btn-copy-username');
    if (copyUsernameBtn) {
        copyUsernameBtn.onclick = async () => {
            const handle = copyUsernameBtn.getAttribute('data-username');
            if (!handle) return;
            const textToCopy = '@' + handle;
            try {
                await navigator.clipboard.writeText(textToCopy);
                window.showToast(`Username "${textToCopy}" copied!`, 'success', 'Copied');
            } catch (e) {
                const temp = document.createElement('textarea');
                temp.value = textToCopy;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                window.showToast(`Username "${textToCopy}" copied!`, 'success', 'Copied');
            }
        };
    }

    // 1. Render Hero Social Media Links Bar
    function renderHeroSocialLinks(user) {
        if (!heroSocialLinksBar) return;
        const isOwn = !!document.getElementById('profile-manager-section');
        let html = '';
        let activeCount = 0;

        for (const [key, meta] of Object.entries(platformsMeta)) {
            const val = (user[key] || '').trim();
            if (!val) continue; // HIDE EMPTY LINKS!

            activeCount++;
            const formatted = meta.formatUrl(val);

            if (meta.isCopy && formatted.startsWith('#discord:')) {
                const copyText = formatted.substring(9);
                html += `
                    <button type="button" class="social-link-btn social-link-copy" data-copy="${escapeHtml(copyText)}" title="Discord handle: ${escapeHtml(copyText)} (Click to copy)">
                        <span class="social-icon" style="color: ${meta.color};">${meta.icon}</span>
                        <span class="social-name">${escapeHtml(meta.name)}</span>
                        <span class="social-val-preview">${escapeHtml(copyText)}</span>
                        <span class="social-copy-badge">Copy</span>
                    </button>
                `;
            } else {
                html += `
                    <a href="${escapeHtml(formatted)}" target="_blank" rel="noopener noreferrer" class="social-link-btn ${meta.isDirectChat ? 'social-link-chat' : ''}" title="${escapeHtml(meta.name)}: ${escapeHtml(val)}">
                        <span class="social-icon" style="color: ${meta.color};">${meta.icon}</span>
                        <span class="social-name">${escapeHtml(meta.name)}</span>
                        ${meta.isDirectChat ? `
                            <span class="social-chat-badge" title="Direct Online Chat">
                                <span class="pulse-dot"></span> Chat
                            </span>
                        ` : ''}
                    </a>
                `;
            }
        }

        if (activeCount > 0) {
            heroSocialLinksBar.style.display = 'flex';
            heroSocialLinksBar.innerHTML = html;
            bindDiscordCopyButtons();
        } else {
            if (isOwn) {
                heroSocialLinksBar.style.display = 'flex';
                heroSocialLinksBar.innerHTML = `
                    <div class="empty-social-callout" id="empty-social-callout">
                        <span class="empty-social-text">💬 Connect your social accounts & direct chat channels so peers can message you.</span>
                        <button type="button" class="btn btn-sm btn-outline" id="btn-add-social-shortcut">+ Add Links</button>
                    </div>
                `;
                const newShortcut = document.getElementById('btn-add-social-shortcut');
                if (newShortcut) {
                    newShortcut.addEventListener('click', () => {
                        const mgr = document.getElementById('profile-manager-section');
                        if (mgr) {
                            mgr.scrollIntoView({ behavior: 'smooth' });
                            switchManagerTab('tab-social');
                        }
                    });
                }
            } else {
                // Other users: completely hide if no active links
                heroSocialLinksBar.style.display = 'none';
                heroSocialLinksBar.innerHTML = '';
            }
        }
    }

    // 2. Live Preview in Management Panel
    function updateLiveSocialPreview() {
        const previewBar = document.getElementById('live-social-preview-bar');
        if (!previewBar) return;

        let previewHtml = '';
        let hasAny = false;

        for (const [key, meta] of Object.entries(platformsMeta)) {
            const input = document.getElementById(`social-input-${key}`);
            const val = input ? input.value.trim() : '';
            const row = document.getElementById(`channel-row-${key}`);
            const removeBtn = row ? row.querySelector('.btn-remove-social') : null;
            const chip = document.querySelector(`.social-chip-jump[data-channel="${key}"]`);

            if (val) {
                hasAny = true;
                if (removeBtn) removeBtn.style.display = 'inline-flex';
                if (chip) {
                    chip.classList.add('is-active');
                    const dot = chip.querySelector('.chip-dot');
                    if (dot) dot.classList.add('dot-active');
                }

                const formatted = meta.formatUrl(val);
                if (meta.isCopy && formatted.startsWith('#discord:')) {
                    const copyText = formatted.substring(9);
                    previewHtml += `
                        <span class="social-link-btn" style="cursor: default;">
                            <span class="social-icon" style="color: ${meta.color};">${meta.icon}</span>
                            <span class="social-name">${escapeHtml(meta.name)}</span>
                            <span class="social-val-preview">${escapeHtml(copyText)}</span>
                        </span>
                    `;
                } else {
                    previewHtml += `
                        <span class="social-link-btn ${meta.isDirectChat ? 'social-link-chat' : ''}">
                            <span class="social-icon" style="color: ${meta.color};">${meta.icon}</span>
                            <span class="social-name">${escapeHtml(meta.name)}</span>
                            ${meta.isDirectChat ? `
                                <span class="social-chat-badge">
                                    <span class="pulse-dot"></span> Chat
                                </span>
                            ` : ''}
                        </span>
                    `;
                }
            } else {
                if (removeBtn) removeBtn.style.display = 'none';
                if (chip) {
                    chip.classList.remove('is-active');
                    const dot = chip.querySelector('.chip-dot');
                    if (dot) dot.classList.remove('dot-active');
                }
            }
        }

        previewBar.innerHTML = hasAny ? previewHtml : '<span style="font-size: 0.84rem; color: var(--text-muted); font-style: italic;">No social channels connected yet. Enter handles above to preview.</span>';
    }

    // Attach listeners on all social inputs for real-time live preview
    document.querySelectorAll('.social-channel-input').forEach(input => {
        input.addEventListener('input', updateLiveSocialPreview);
    });
    updateLiveSocialPreview();

    // 3. Remove Social Link Handler
    document.querySelectorAll('.btn-remove-social').forEach(btn => {
        btn.addEventListener('click', () => {
            const channel = btn.getAttribute('data-channel');
            const input = document.getElementById(`social-input-${channel}`);
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('input'));
                input.focus();
                const name = platformsMeta[channel] ? platformsMeta[channel].name : channel;
                window.showToast(`Removed ${name} channel. Click 'Save Social Links' to apply changes.`, 'info');
            }
        });
    });

    // 4. Quick Jump Chips
    document.querySelectorAll('.social-chip-jump').forEach(chip => {
        chip.addEventListener('click', () => {
            const channel = chip.getAttribute('data-channel');
            const row = document.getElementById(`channel-row-${channel}`);
            const input = document.getElementById(`social-input-${channel}`);
            if (row && input) {
                switchManagerTab('tab-social');
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                row.style.borderColor = 'var(--primary)';
                setTimeout(() => {
                    input.focus();
                    row.style.borderColor = '';
                }, 300);
            }
        });
    });

    // 5. Manager Tab Switching
    function switchManagerTab(tabId) {
        document.querySelectorAll('.manager-tab-btn').forEach(b => {
            b.classList.toggle('active', b.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.manager-tab-pane').forEach(p => {
            p.style.display = (p.id === tabId) ? 'block' : 'none';
        });
    }

    document.querySelectorAll('.manager-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            switchManagerTab(targetTab);
        });
    });

    // Toggle Edit Mode & Shortcut Buttons
    if (toggleEditModeBtn) {
        toggleEditModeBtn.addEventListener('click', () => {
            const mgr = document.getElementById('profile-manager-section');
            if (mgr) {
                mgr.scrollIntoView({ behavior: 'smooth' });
                switchManagerTab('tab-social');
            }
        });
    }

    if (addSocialShortcutBtn) {
        addSocialShortcutBtn.addEventListener('click', () => {
            const mgr = document.getElementById('profile-manager-section');
            if (mgr) {
                mgr.scrollIntoView({ behavior: 'smooth' });
                switchManagerTab('tab-social');
            }
        });
    }

    // 6. Save Profile & Social Media Form Submit (AJAX)
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtns = profileForm.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(b => {
                b.disabled = true;
                b.innerHTML = '<span class="spinner"></span> Saving...';
            });

            const formData = new FormData(profileForm);

            // Ensure 'name' is always present — required by update_profile.php
            if (!formData.get('name') || !formData.get('name').trim()) {
                const nameFromPage = document.querySelector('.profile-name')?.textContent.trim();
                if (nameFromPage) formData.set('name', nameFromPage);
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch('api/users/update_profile.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });
                const res = await response.json();

                submitBtns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = b.classList.contains('btn-save-social') ? '💾 Save Social Links' : 'Save Changes';
                });

                if (res.success) {
                    window.showToast(res.message || 'Profile updated successfully!', 'success');

                    // Update Hero card elements in real-time
                    if (res.data && res.data.user) {
                        const u = res.data.user;
                        renderHeroSocialLinks(u);

                        const heroName = document.querySelector('.profile-name');
                        if (heroName) heroName.textContent = u.name;

                        const heroBio = document.querySelector('.profile-bio-text');
                        if (heroBio) heroBio.innerHTML = u.bio ? escapeHtml(u.bio).replace(/\n/g, '<br>') : '<span style="color: var(--text-muted); font-style: italic;">No bio written yet.</span>';
                    }

                    if (res.data.avatar_url && avatarPreviewImg) {
                        avatarPreviewImg.src = res.data.avatar_url;
                        const navAvatar = document.querySelector('.nav-avatar-img');
                        if (navAvatar) navAvatar.src = res.data.avatar_url;
                    }
                } else {
                    window.showToast(res.message || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                submitBtns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = 'Save Changes';
                });
                window.showToast('Network error while saving profile.', 'error');
            }
        });
    }

    // Avatar preview when selected
    if (avatarInput && avatarPreviewImg) {
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    window.showToast('Image file size must be less than 2MB.', 'error');
                    avatarInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 7. Dynamic Skills Operations (Insert, Delete, Edit without full page reload)

    // Helper: Dynamic Insertion of a Skill badge into the DOM
    function insertSkillIntoDOM(skill) {
        const type = skill.skill_type || 'OFFER';
        const listContainer = (type === 'OFFER') 
            ? document.getElementById('offered-skills-list') 
            : document.getElementById('wanted-skills-list');

        if (!listContainer) return;

        // Remove empty state if present
        const emptyState = listContainer.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const item = document.createElement('div');
        item.className = 'skill-badge-item';
        item.id = `user-skill-${skill.id}`;
        item.style.animation = 'fadeInUp 0.35s ease';

        const targetLabel = (type === 'WANT') ? 'Target: ' : '';
        const nameColor = (type === 'WANT') ? 'color: var(--primary);' : '';
        const descText = skill.description ? ` &bull; ${escapeHtml(skill.description)}` : '';

        item.innerHTML = `
            <div class="skill-info-left">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <strong style="font-size: 1rem; ${nameColor}">${escapeHtml(skill.skill_name)}</strong>
                    <span class="proficiency-pill proficiency-${escapeHtml(skill.proficiency)}">${targetLabel}${escapeHtml(skill.proficiency)}</span>
                </div>
                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 2px;">
                    Category: ${escapeHtml(skill.category || 'General')}${descText}
                </span>
            </div>
            <div class="skill-actions-right">
                <button type="button" class="btn-icon btn-edit-skill" data-id="${skill.id}" data-name="${escapeHtml(skill.skill_name)}" data-proficiency="${escapeHtml(skill.proficiency)}" data-description="${escapeHtml(skill.description || '')}" title="Edit details">
                    ✎
                </button>
                <button type="button" class="btn-icon btn-delete-skill text-danger" data-id="${skill.id}" data-name="${escapeHtml(skill.skill_name)}" title="Remove skill">
                    &times;
                </button>
            </div>
        `;

        listContainer.prepend(item);

        // Bind event listeners for the new item
        bindSkillActions(item);

        // Refresh overview
        refreshDynamicSkillsSummary();
    }

    // Helper: Dynamic Deletion of a Skill from the DOM
    async function handleDeleteSkill(skillId, skillName, itemElement) {
        if (!confirm(`Are you sure you want to remove "${skillName}" from your profile?`)) return;

        const res = await window.apiFetch('api/skills/delete.php', {
            method: 'POST',
            body: { user_skill_id: skillId }
        });

        if (res.success) {
            window.showToast(res.message, 'success');

            const parentList = itemElement.closest('.skills-card-list');

            // Smooth exit animation
            itemElement.style.opacity = '0';
            itemElement.style.transform = 'scale(0.95)';
            itemElement.style.maxHeight = '0';
            itemElement.style.padding = '0';
            itemElement.style.margin = '0';
            itemElement.style.overflow = 'hidden';

            setTimeout(() => {
                itemElement.remove();

                // Check if list is now empty and restore empty state if needed
                if (parentList && !parentList.querySelector('.skill-badge-item')) {
                    const isOffered = (parentList.id === 'offered-skills-list');
                    const emptyText = isOffered ? 'No skills offered yet.' : 'No learning wishlist items yet.';
                    const btnId = isOffered ? 'btn-add-offered-skill' : 'btn-add-wanted-skill';
                    const btnLabel = isOffered ? '+ Add First Skill' : '+ Add Desired Skill';

                    parentList.innerHTML = `
                        <div class="empty-state" style="padding: 2rem 1rem;">
                            <p style="font-size: 0.9rem; color: var(--text-muted);">${emptyText}</p>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;" onclick="document.getElementById('${btnId}').click()">${btnLabel}</button>
                        </div>
                    `;
                }

                refreshDynamicSkillsSummary();
            }, 250);
        } else {
            window.showToast(res.message || 'Failed to remove skill.', 'error');
        }
    }

    // Helper: Dynamic Editing of a Skill
    function openEditSkillModal(id, name, proficiency, description) {
        const modalHtml = `
            <form id="edit-skill-form">
                <input type="hidden" name="user_skill_id" value="${id}">
                <p style="font-weight: 600; margin-bottom: 1.25rem;">Skill: ${escapeHtml(name)}</p>

                <div class="form-group">
                    <label class="form-label">Proficiency Level</label>
                    <select name="proficiency" class="form-select" required>
                        <option value="Beginner" ${proficiency === 'Beginner' ? 'selected' : ''}>Beginner</option>
                        <option value="Intermediate" ${proficiency === 'Intermediate' ? 'selected' : ''}>Intermediate</option>
                        <option value="Advanced" ${proficiency === 'Advanced' ? 'selected' : ''}>Advanced</option>
                        <option value="Expert" ${proficiency === 'Expert' ? 'selected' : ''}>Expert</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea name="description" class="form-textarea">${escapeHtml(description)}</textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Skill</button>
                </div>
            </form>
        `;

        window.openModal(`Edit ${name}`, modalHtml);

        const form = document.getElementById('edit-skill-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const newProf = form.querySelector('[name="proficiency"]').value;
            const newDesc = form.querySelector('[name="description"]').value.trim();

            const res = await window.apiFetch('api/skills/update.php', {
                method: 'POST',
                body: {
                    user_skill_id: id,
                    proficiency: newProf,
                    description: newDesc
                }
            });

            if (res.success) {
                window.closeModal();
                window.showToast(res.message, 'success');

                // Dynamically update skill in DOM without page reload!
                const item = document.getElementById(`user-skill-${id}`);
                if (item) {
                    const pill = item.querySelector('.proficiency-pill');
                    if (pill) {
                        pill.className = `proficiency-pill proficiency-${escapeHtml(newProf)}`;
                        pill.textContent = (item.closest('#wanted-skills-list') ? 'Target: ' : '') + newProf;
                    }
                    const editBtn = item.querySelector('.btn-edit-skill');
                    if (editBtn) {
                        editBtn.setAttribute('data-proficiency', newProf);
                        editBtn.setAttribute('data-description', newDesc);
                    }
                }
                refreshDynamicSkillsSummary();
            } else {
                submitBtn.disabled = false;
                window.showToast(res.message || 'Failed to update skill.', 'error');
            }
        });
    }

    // Bind edit and delete handlers to any skill item
    function bindSkillActions(container = document) {
        container.querySelectorAll('.btn-delete-skill').forEach(btn => {
            btn.onclick = () => {
                const skillId = btn.getAttribute('data-id');
                const skillName = btn.getAttribute('data-name');
                const item = btn.closest('.skill-badge-item');
                handleDeleteSkill(skillId, skillName, item);
            };
        });

        container.querySelectorAll('.btn-edit-skill').forEach(btn => {
            btn.onclick = () => {
                const skillId = btn.getAttribute('data-id');
                const skillName = btn.getAttribute('data-name');
                const proficiency = btn.getAttribute('data-proficiency');
                const description = btn.getAttribute('data-description') || '';
                openEditSkillModal(skillId, skillName, proficiency, description);
            };
        });
    }
    bindSkillActions();

    // 8. Refresh Dynamic Skills Summary in Management Panel
    function refreshDynamicSkillsSummary() {
        if (!dynamicSkillsSummary) return;

        const offeredItems = document.querySelectorAll('#offered-skills-list .skill-badge-item');
        const wantedItems = document.querySelectorAll('#wanted-skills-list .skill-badge-item');

        if (offeredItems.length === 0 && wantedItems.length === 0) {
            dynamicSkillsSummary.innerHTML = '<span style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">No skills added yet. Use the adder above to add your first skill dynamically.</span>';
            return;
        }

        let html = '';
        offeredItems.forEach(item => {
            const nameEl = item.querySelector('strong');
            const editBtn = item.querySelector('.btn-edit-skill');
            const skillId = editBtn ? editBtn.getAttribute('data-id') : null;
            const skillName = nameEl ? nameEl.textContent : 'Skill';

            html += `
                <span class="dynamic-summary-pill" id="summary-pill-${skillId}">
                    <span style="color: var(--primary);">🎁 ${escapeHtml(skillName)}</span>
                    <button type="button" class="btn-delete-summary" data-id="${skillId}" data-name="${escapeHtml(skillName)}" title="Remove skill dynamically">&times;</button>
                </span>
            `;
        });

        wantedItems.forEach(item => {
            const nameEl = item.querySelector('strong');
            const editBtn = item.querySelector('.btn-edit-skill');
            const skillId = editBtn ? editBtn.getAttribute('data-id') : null;
            const skillName = nameEl ? nameEl.textContent : 'Skill';

            html += `
                <span class="dynamic-summary-pill" id="summary-pill-${skillId}">
                    <span style="color: #f59e0b;">🎯 ${escapeHtml(skillName)}</span>
                    <button type="button" class="btn-delete-summary" data-id="${skillId}" data-name="${escapeHtml(skillName)}" title="Remove skill dynamically">&times;</button>
                </span>
            `;
        });

        dynamicSkillsSummary.innerHTML = html;

        dynamicSkillsSummary.querySelectorAll('.btn-delete-summary').forEach(btn => {
            btn.onclick = () => {
                const skillId = btn.getAttribute('data-id');
                const skillName = btn.getAttribute('data-name');
                const item = document.getElementById(`user-skill-${skillId}`);
                if (item) {
                    handleDeleteSkill(skillId, skillName, item);
                }
            };
        });
    }
    refreshDynamicSkillsSummary();

    // 9. Quick Add Skill Form (Inside Management Panel)
    if (quickAddSkillForm) {
        quickAddSkillForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-submit-quick-skill');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Adding...';

            const typeRadio = quickAddSkillForm.querySelector('input[name="quick_skill_type"]:checked');
            const skillType = typeRadio ? typeRadio.value : 'OFFER';
            const catalogSelect = document.getElementById('quick-skill-select');
            const customInput = document.getElementById('quick-new-skill-input');
            const categorySelect = document.getElementById('quick-category-select');
            const profSelect = quickAddSkillForm.querySelector('[name="quick_proficiency"]');
            const descInput = quickAddSkillForm.querySelector('[name="quick_description"]');

            const payload = {
                skill_id: catalogSelect ? catalogSelect.value : '',
                new_skill_name: customInput ? customInput.value.trim() : '',
                category: categorySelect ? categorySelect.value : 'General',
                skill_type: skillType,
                proficiency: profSelect ? profSelect.value : 'Intermediate',
                description: descInput ? descInput.value.trim() : ''
            };

            const addRes = await window.apiFetch('api/skills/add.php', {
                method: 'POST',
                body: payload
            });

            submitBtn.disabled = false;
            submitBtn.innerHTML = '+ Add Skill Dynamically';

            if (addRes.success) {
                // Dynamically insert into DOM without page reload!
                insertSkillIntoDOM(addRes.data);
                window.showToast(addRes.message, 'success');

                // Reset form fields
                if (catalogSelect) catalogSelect.value = '';
                if (customInput) customInput.value = '';
                if (descInput) descInput.value = '';
            } else {
                window.showToast(addRes.message || 'Failed to add skill.', 'error');
            }
        });
    }

    // 10. Modal Add Skill Buttons
    if (addOfferedSkillBtn) {
        addOfferedSkillBtn.addEventListener('click', () => openAddSkillModal('OFFER'));
    }

    if (addWantedSkillBtn) {
        addWantedSkillBtn.addEventListener('click', () => openAddSkillModal('WANT'));
    }

    async function openAddSkillModal(type = 'OFFER') {
        const res = await window.apiFetch('api/skills/list.php');
        const skills = (res.success && res.data.skills) ? res.data.skills : [];
        const categories = (res.success && res.data.categories) ? res.data.categories : ['General'];

        const skillsOptions = skills.map(s => `
            <option value="${s.id}">[${escapeHtml(s.category)}] ${escapeHtml(s.name)}</option>
        `).join('');

        const categoryOptions = categories.map(c => `
            <option value="${escapeHtml(c)}">${escapeHtml(c)}</option>
        `).join('');

        const typeLabel = type === 'OFFER' ? 'Skill You Can Offer & Teach' : 'Skill You Want to Learn';

        const modalHtml = `
            <form id="add-skill-form">
                <input type="hidden" name="skill_type" value="${type}">

                <div class="form-group">
                    <label class="form-label">Select Skill from Catalog</label>
                    <select name="skill_id" id="skill-catalog-select" class="form-select">
                        <option value="">-- Choose from existing skills --</option>
                        ${skillsOptions}
                    </select>
                </div>

                <div style="text-align: center; margin: 0.5rem 0; color: var(--text-muted); font-size: 0.85rem;">
                    &mdash; OR CREATE A NEW SKILL &mdash;
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Skill Name</label>
                    <input type="text" name="new_skill_name" id="new-skill-input" class="form-input" placeholder="e.g. Kotlin, Origami, Docker">
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        ${categoryOptions}
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Proficiency Level</label>
                    <select name="proficiency" class="form-select" required>
                        <option value="Beginner">Beginner - Basic understanding</option>
                        <option value="Intermediate" selected>Intermediate - Practical experience</option>
                        <option value="Advanced">Advanced - In-depth expertise</option>
                        <option value="Expert">Expert - Professional / Master</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Topics covered (Optional)</label>
                    <textarea name="description" class="form-textarea" placeholder="Describe what you can share or what specifically you want to learn..."></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Skill to Profile</button>
                </div>
            </form>
        `;

        window.openModal(`Add ${typeLabel}`, modalHtml);

        const form = document.getElementById('add-skill-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const payload = {
                skill_id: form.querySelector('#skill-catalog-select').value,
                new_skill_name: form.querySelector('#new-skill-input').value.trim(),
                category: form.querySelector('[name="category"]').value,
                skill_type: type,
                proficiency: form.querySelector('[name="proficiency"]').value,
                description: form.querySelector('[name="description"]').value.trim()
            };

            const addRes = await window.apiFetch('api/skills/add.php', {
                method: 'POST',
                body: payload
            });

            if (addRes.success) {
                window.closeModal();
                window.showToast(addRes.message, 'success');
                // Dynamically insert into DOM without page reload!
                insertSkillIntoDOM(addRes.data);
            } else {
                submitBtn.disabled = false;
                window.showToast(addRes.message || 'Failed to add skill.', 'error');
            }
        });
    }

    // 11. Populate Quick Skill Adder Catalog on page load
    async function loadCatalogForQuickAdder() {
        const quickSkillSelect = document.getElementById('quick-skill-select');
        const quickCatSelect = document.getElementById('quick-category-select');
        if (!quickSkillSelect) return;

        try {
            const res = await window.apiFetch('api/skills/list.php');
            if (res.success && res.data) {
                if (res.data.skills && res.data.skills.length > 0) {
                    const opts = res.data.skills.map(s => `
                        <option value="${s.id}">[${escapeHtml(s.category)}] ${escapeHtml(s.name)}</option>
                    `).join('');
                    quickSkillSelect.innerHTML = '<option value="">-- Choose from Catalog --</option>' + opts;
                }
                if (res.data.categories && res.data.categories.length > 0 && quickCatSelect) {
                    const catOpts = res.data.categories.map(c => `
                        <option value="${escapeHtml(c)}">${escapeHtml(c)}</option>
                    `).join('');
                    quickCatSelect.innerHTML = catOpts;
                }
            }
        } catch (err) {
            console.error('[Catalog Preload Error]', err);
        }
    }
    loadCatalogForQuickAdder();

    // ─────────────────────────────────────────────────────────────
    // 12. Avatar: Dropdown Toggle, Upload & Remove Photo
    // ─────────────────────────────────────────────────────────────

    const avatarMenuBtn     = document.getElementById('avatarMenuBtn');
    const avatarDropdown    = document.getElementById('avatarDropdown');
    const uploadTriggerBtn  = document.getElementById('uploadTriggerBtn');
    const removeAvatarBtn   = document.getElementById('removeAvatarBtn');
    const avatarUploadInput = document.getElementById('avatarUploadInput');
    // avatarPreviewImg is already declared at the top of this DOMContentLoaded block

    // ── Dropdown toggle ──────────────────────────────────────────
    if (avatarMenuBtn && avatarDropdown) {
        avatarDropdown.style.display = 'none';

        avatarMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            avatarDropdown.style.display =
                (avatarDropdown.style.display === 'none') ? 'block' : 'none';
        });

        document.addEventListener('click', (e) => {
            if (avatarDropdown && !avatarDropdown.contains(e.target) && e.target !== avatarMenuBtn) {
                avatarDropdown.style.display = 'none';
            }
        });
    }

    // ── "Upload / Update Photo" → trigger file picker ────────────
    if (uploadTriggerBtn && avatarUploadInput) {
        uploadTriggerBtn.addEventListener('click', () => {
            if (avatarDropdown) avatarDropdown.style.display = 'none';
            avatarUploadInput.click();
        });
    }

    // ── File selected → instant preview then AJAX upload ─────────
    if (avatarUploadInput && avatarPreviewImg) {
        avatarUploadInput.addEventListener('change', () => {
            const file = avatarUploadInput.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                window.showToast('Image must be under 2 MB.', 'error');
                avatarUploadInput.value = '';
                return;
            }

            // Immediately show a local preview
            const reader = new FileReader();
            reader.onload = (ev) => { avatarPreviewImg.src = ev.target.result; };
            reader.readAsDataURL(file);

            // Upload to dedicated endpoint
            uploadAvatarFile(file);
        });
    }

    async function uploadAvatarFile(file) {
        const formData = new FormData();
        formData.append('avatar', file);   // endpoint expects field name "avatar"

        try {
            // Use raw fetch with CSRF header (apiFetch can't handle FormData bodies)
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response  = await fetch('api/users/upload_avatar.php', {
                method : 'POST',
                headers: { 'X-CSRF-Token': csrfToken },
                body   : formData
            });

            let res;
            try { res = await response.json(); }
            catch (_) { res = { success: false, message: 'Server returned an invalid response.' }; }

            if (res.success) {
                window.showToast('Profile photo updated!', 'success');
                const newUrl = res.data?.avatar_url;
                if (newUrl && avatarPreviewImg) {
                    avatarPreviewImg.src = newUrl;
                    const navAvatar = document.querySelector('.nav-avatar-img');
                    if (navAvatar) navAvatar.src = newUrl;
                }
                // Reveal "Remove" button since user now has a custom photo
                if (removeAvatarBtn) removeAvatarBtn.style.removeProperty('display');
            } else {
                window.showToast(res.message || 'Upload failed.', 'error');
                // Revert preview on error
                avatarPreviewImg.src = avatarPreviewImg.dataset.original || 'assets/images/default-avatar.svg';
            }
        } catch (err) {
            console.error('[Avatar Upload Error]', err);
            window.showToast('Network error during upload.', 'error');
        } finally {
            avatarUploadInput.value = '';  // reset so same file can be re-picked
        }
    }

    // Store original src so we can revert on error
    if (avatarPreviewImg) {
        avatarPreviewImg.dataset.original = avatarPreviewImg.src;
    }

    // ── "Remove Photo" → AJAX remove → switch to default avatar ──
    if (removeAvatarBtn) {
        removeAvatarBtn.addEventListener('click', async () => {
            if (avatarDropdown) avatarDropdown.style.display = 'none';

            if (!confirm('Remove your profile photo and revert to the default avatar?')) return;

            removeAvatarBtn.disabled = true;
            removeAvatarBtn.textContent = 'Removing…';

            // Use window.apiFetch — it handles CSRF & JSON automatically
            const res = await window.apiFetch('api/users/remove_avatar.php', { method: 'POST' });

            if (res.success) {
                window.showToast('Profile photo removed.', 'success');

                const defaultSrc = res.data?.avatar_url || 'assets/images/default-avatar.svg';

                if (avatarPreviewImg) {
                    avatarPreviewImg.src = defaultSrc;
                    avatarPreviewImg.dataset.original = defaultSrc;
                }
                const navAvatar = document.querySelector('.nav-avatar-img');
                if (navAvatar) navAvatar.src = defaultSrc;

                // Hide "Remove" since there's no custom photo now
                removeAvatarBtn.style.display = 'none';
            } else {
                window.showToast(res.message || 'Failed to remove photo.', 'error');
            }

            removeAvatarBtn.disabled = false;
            removeAvatarBtn.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Remove Photo
            `;
        });
    }
});
