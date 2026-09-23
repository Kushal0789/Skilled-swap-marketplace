/**
 * Messaging Client Controller
 * Conversations List, Active Chat Thread & Incremental Polling
 */

document.addEventListener('DOMContentLoaded', () => {
    const convListEl = document.getElementById('chat-conv-list');
    const msgContainer = document.getElementById('chat-messages-container');
    const sendForm = document.getElementById('chat-send-form');
    const msgInput = document.getElementById('chat-message-input');
    const partnerNameEl = document.getElementById('chat-partner-name');
    const partnerLocationEl = document.getElementById('chat-partner-location');
    const partnerAvatarEl = document.getElementById('chat-partner-avatar');
    const chatEmptyView = document.getElementById('chat-empty-view');
    const chatActiveView = document.getElementById('chat-active-view');
    const chatLayout = document.querySelector('.chat-layout');

    let activeConversationId = null;
    let lastMessageId = 0;
    let pollInterval = null;

    init();

    async function init() {
        await loadConversations();

        // Check if URL specifies conversation_id or partner
        const params = new URLSearchParams(window.location.search);
        const urlConvId = params.get('conversation_id');
        const urlUserId = params.get('user_id');

        if (urlConvId) {
            selectConversation(parseInt(urlConvId));
        } else if (urlUserId) {
            // Start or open conversation with this user
            const res = await window.apiFetch(`api/messages/conversations.php?recipient_id=${urlUserId}`);
            if (res.success) {
                await loadConversations();
                const matched = res.data.conversations.find(c => c.partner.id == urlUserId);
                if (matched) selectConversation(matched.id);
            }
        }
    }

    async function loadConversations() {
        if (!convListEl) return;
        const res = await window.apiFetch('api/messages/conversations.php');

        if (!res.success) {
            convListEl.innerHTML = '<div class="notif-empty-state">Unable to load conversations.</div>';
            return;
        }

        const convs = res.data.conversations || [];

        if (convs.length === 0) {
            convListEl.innerHTML = `
                <div style="padding: 2rem 1.5rem; text-align: center; color: var(--text-muted);">
                    <p style="font-size: 0.9rem;">No active conversations.</p>
                    <p style="font-size: 0.8rem; margin-top: 0.5rem;">Accepted swap requests automatically create a private chat.</p>
                </div>
            `;
            return;
        }

        convListEl.innerHTML = convs.map(c => `
            <div class="conv-item ${c.id === activeConversationId ? 'active' : ''}" data-id="${c.id}" data-partner='${JSON.stringify(c.partner)}'>
                <img src="${escapeHtml(c.partner.avatar_url)}" alt="${escapeHtml(c.partner.name)}" class="conv-avatar">
                <div class="conv-info">
                    <div class="conv-name-row">
                        <span class="conv-name">${escapeHtml(c.partner.name)}</span>
                        <span class="conv-time">${escapeHtml(c.last_time)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="conv-last-msg">${escapeHtml(c.last_message)}</span>
                        ${c.unread_count > 0 ? `<span class="badge-pill bg-primary" style="font-size: 0.68rem; padding: 1px 6px;">${c.unread_count}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');

        // Attach clicks
        convListEl.querySelectorAll('.conv-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = parseInt(item.getAttribute('data-id'));
                selectConversation(id);
            });
        });
    }

    async function selectConversation(id) {
        activeConversationId = id;
        lastMessageId = 0;

        // Highlight selected in sidebar
        document.querySelectorAll('.conv-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.getAttribute('data-id')) === id);
        });

        if (chatLayout) chatLayout.classList.add('chat-open');
        if (chatEmptyView) chatEmptyView.classList.add('hidden');
        if (chatActiveView) chatActiveView.classList.remove('hidden');

        // Stop prior poll
        if (pollInterval) clearInterval(pollInterval);

        // Load messages for thread
        if (msgContainer) {
            msgContainer.innerHTML = '<div style="text-align: center; padding: 2rem;"><span class="spinner spinner-primary"></span></div>';
        }

        await fetchMessages(false);

        // Start incremental poller every 3.5 seconds
        pollInterval = setInterval(() => {
            if (activeConversationId) {
                fetchMessages(true);
            }
        }, 3500);

        if (msgInput) msgInput.focus();
    }

    async function fetchMessages(isPolling = false) {
        if (!activeConversationId) return;

        const url = isPolling
            ? `api/messages/list.php?conversation_id=${activeConversationId}&since_id=${lastMessageId}`
            : `api/messages/list.php?conversation_id=${activeConversationId}`;

        const res = await window.apiFetch(url);

        if (!res.success) return;

        // Set partner header
        if (!isPolling && res.data.partner) {
            if (partnerNameEl) partnerNameEl.textContent = res.data.partner.name;
            if (partnerLocationEl) partnerLocationEl.textContent = res.data.partner.location || 'Remote / Online';
            if (partnerAvatarEl) partnerAvatarEl.src = res.data.partner.avatar_url;
        }

        const newMessages = res.data.messages || [];

        if (!isPolling) {
            // Full refresh
            if (newMessages.length === 0) {
                msgContainer.innerHTML = `
                    <div style="text-align: center; color: var(--text-muted); margin: auto;">
                        <p style="font-size: 0.95rem;">No messages exchanged yet.</p>
                        <p style="font-size: 0.82rem; margin-top: 0.25rem;">Say hello to kick off your skill swap!</p>
                    </div>
                `;
            } else {
                msgContainer.innerHTML = newMessages.map(m => renderBubble(m)).join('');
                lastMessageId = newMessages[newMessages.length - 1].id;
                scrollToBottom();
            }
        } else {
            // Polling additions
            if (newMessages.length > 0) {
                newMessages.forEach(m => {
                    const bubble = document.createElement('div');
                    bubble.className = `chat-bubble ${m.is_mine ? 'mine' : 'theirs'}`;
                    bubble.innerHTML = `
                        <div class="bubble-text">${escapeHtml(m.message)}</div>
                        <div class="bubble-meta">${escapeHtml(m.time)}</div>
                    `;
                    msgContainer.appendChild(bubble);
                    lastMessageId = m.id;
                });
                scrollToBottom();
            }
        }
    }

    function renderBubble(m) {
        return `
            <div class="chat-bubble ${m.is_mine ? 'mine' : 'theirs'}">
                <div class="bubble-text">${escapeHtml(m.message)}</div>
                <div class="bubble-meta">${escapeHtml(m.time)}</div>
            </div>
        `;
    }

    function scrollToBottom() {
        if (msgContainer) {
            msgContainer.scrollTop = msgContainer.scrollHeight;
        }
    }

    // Message Send Form
    if (sendForm) {
        sendForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeConversationId || !msgInput) return;

            const text = msgInput.value.trim();
            if (!text) return;

            msgInput.value = '';

            const res = await window.apiFetch('api/messages/send.php', {
                method: 'POST',
                body: {
                    conversation_id: activeConversationId,
                    message: text
                }
            });

            if (res.success) {
                // Immediately append sent message to UI
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble mine';
                bubble.innerHTML = `
                    <div class="bubble-text">${escapeHtml(text)}</div>
                    <div class="bubble-meta">${escapeHtml(res.data.time || 'Just now')}</div>
                `;
                msgContainer.appendChild(bubble);
                lastMessageId = Math.max(lastMessageId, res.data.id);
                scrollToBottom();

                // Refresh conversation list to update last message
                loadConversations();
            } else {
                window.showToast(res.message || 'Failed to send message.', 'error');
            }
        });
    }
});
