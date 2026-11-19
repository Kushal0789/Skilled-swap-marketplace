/**
 * Skill Swap Marketplace - Messaging System JavaScript
 * Full client-side logic: conversation list, chat view, polling, send/receive
 */

(function () {
    'use strict';

    // ─── CONFIG & STATE ────────────────────────────────────────────────────────
    const CFG = window.MESSAGES_CONFIG || {};
    const ME  = CFG.currentUserId;

    let activeConversationId   = null;
    let activePartnerId        = null;
    let activePartnerName      = null;
    let activePartnerUsername  = null;
    let activePartnerAvatar    = null;
    let lastMessageId          = 0;       // for incremental polling
    let pollTimer              = null;
    let isSending              = false;
    let conversationsCache     = [];      // in-memory list

    // ─── DOM ELEMENTS ──────────────────────────────────────────────────────────
    const convSidebar        = document.getElementById('conv-sidebar');
    const convList           = document.getElementById('conv-list');
    const convListLoading    = document.getElementById('conv-list-loading');
    const convEmptyState     = document.getElementById('conv-empty-state');
    const convItemsContainer = document.getElementById('conv-items-container');

    const chatPanel          = document.getElementById('chat-panel');
    const chatWelcome        = document.getElementById('chat-welcome');
    const chatView           = document.getElementById('chat-view');
    const chatBackBtn        = document.getElementById('chat-back-btn');

    const chatHeaderAvatar   = document.getElementById('chat-header-avatar');
    const chatHeaderName     = document.getElementById('chat-header-name');
    const chatHeaderUsername = document.getElementById('chat-header-username');

    const chatMessagesArea   = document.getElementById('chat-messages-area');
    const chatLoading        = document.getElementById('chat-loading');
    const chatNoMessages     = document.getElementById('chat-no-messages');
    const messagesContainer  = document.getElementById('messages-container');

    const composerTextarea   = document.getElementById('composer-textarea');
    const composerSendBtn    = document.getElementById('composer-send-btn');
    const composerError      = document.getElementById('composer-error');
    const composerErrorText  = document.getElementById('composer-error-text');

    // ─── INIT ──────────────────────────────────────────────────────────────────
    async function init() {
        await loadConversations();

        // Deep-link: ?with=USER_ID
        if (CFG.openWithUserId && CFG.openWithUserId !== ME) {
            await openConversationWithUser(CFG.openWithUserId);
        }
    }

    // ─── CONVERSATIONS LIST ────────────────────────────────────────────────────
    async function loadConversations() {
        convListLoading.classList.remove('hidden');
        convEmptyState.classList.add('hidden');
        convItemsContainer.innerHTML = '';

        const res = await window.apiFetch('api/messages/conversations.php');

        convListLoading.classList.add('hidden');

        if (!res.success) {
            convItemsContainer.innerHTML = `
                <div class="conv-error-state">
                    <p>Could not load conversations.</p>
                </div>`;
            return;
        }

        conversationsCache = res.data || [];

        if (conversationsCache.length === 0) {
            convEmptyState.classList.remove('hidden');
            return;
        }

        renderConversationList(conversationsCache);
    }

    function renderConversationList(conversations) {
        convItemsContainer.innerHTML = conversations.map(conv => buildConvItem(conv)).join('');

        // Attach click handlers
        convItemsContainer.querySelectorAll('.conv-item').forEach(item => {
            item.addEventListener('click', () => {
                const convId        = parseInt(item.dataset.convId, 10);
                const partnerId     = parseInt(item.dataset.partnerId, 10);
                const partnerName   = item.dataset.partnerName;
                const partnerUser   = item.dataset.partnerUsername;
                const partnerAvatar = item.dataset.partnerAvatar;
                openChat(convId, partnerId, partnerName, partnerUser, partnerAvatar);
            });
        });
    }

    function buildConvItem(conv) {
        const isActive  = conv.conversation_id === activeConversationId;
        const hasUnread = conv.unread_count > 0;
        const preview   = window.escapeHtml(conv.last_message || 'No messages yet');

        return `
        <div class="conv-item ${isActive ? 'active' : ''} ${hasUnread ? 'has-unread' : ''}"
             data-conv-id="${conv.conversation_id}"
             data-partner-id="${conv.partner_id}"
             data-partner-name="${window.escapeHtml(conv.partner_name)}"
             data-partner-username="${window.escapeHtml(conv.partner_username)}"
             data-partner-avatar="${window.escapeHtml(conv.partner_avatar)}"
             role="button"
             tabindex="0"
             aria-label="Conversation with ${window.escapeHtml(conv.partner_name)}">
            <div class="conv-item-avatar-wrap">
                <img class="conv-item-avatar" src="${window.escapeHtml(conv.partner_avatar)}" alt="${window.escapeHtml(conv.partner_name)}" loading="lazy">
                ${hasUnread ? `<span class="conv-unread-dot" aria-label="${conv.unread_count} unread"></span>` : ''}
            </div>
            <div class="conv-item-body">
                <div class="conv-item-row">
                    <span class="conv-item-name">${window.escapeHtml(conv.partner_name)}</span>
                    <span class="conv-item-time">${window.escapeHtml(conv.time_ago)}</span>
                </div>
                <div class="conv-item-preview ${hasUnread ? 'conv-item-preview--unread' : ''}">${preview}</div>
            </div>
        </div>`;
    }

    // Update just the active-state and preview of a single conv item without re-rendering all
    function updateConvItemPreview(conversationId, previewText, timeAgo, unreadCount) {
        const item = convItemsContainer.querySelector(`[data-conv-id="${conversationId}"]`);
        if (!item) return;

        const previewEl = item.querySelector('.conv-item-preview');
        const timeEl    = item.querySelector('.conv-item-time');
        const dotEl     = item.querySelector('.conv-unread-dot');
        const avatarWrap = item.querySelector('.conv-item-avatar-wrap');

        if (previewEl) previewEl.textContent = previewText;
        if (timeEl)    timeEl.textContent    = timeAgo;

        if (unreadCount > 0) {
            item.classList.add('has-unread');
            previewEl && previewEl.classList.add('conv-item-preview--unread');
            if (!dotEl && avatarWrap) {
                const dot = document.createElement('span');
                dot.className = 'conv-unread-dot';
                avatarWrap.appendChild(dot);
            }
        } else {
            item.classList.remove('has-unread');
            previewEl && previewEl.classList.remove('conv-item-preview--unread');
            if (dotEl) dotEl.remove();
        }

        // Move this conv to the top of the list
        const container = item.parentElement;
        if (container && container.firstChild !== item) {
            container.prepend(item);
        }
    }

    // ─── OPEN CONVERSATION ─────────────────────────────────────────────────────
    function openChat(convId, partnerId, partnerName, partnerUsername, partnerAvatar) {
        // Clear polling for previous conversation
        stopPolling();

        activeConversationId  = convId;
        activePartnerId       = partnerId;
        activePartnerName     = partnerName;
        activePartnerUsername = partnerUsername;
        activePartnerAvatar   = partnerAvatar;
        lastMessageId         = 0;

        // Update active state in sidebar
        convItemsContainer.querySelectorAll('.conv-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.convId, 10) === convId);
        });

        // Update chat header
        chatHeaderAvatar.src = partnerAvatar;
        chatHeaderAvatar.alt = partnerName;
        chatHeaderName.textContent     = partnerName;
        chatHeaderUsername.textContent = '@' + partnerUsername;

        // Show chat view
        chatWelcome.classList.add('hidden');
        chatView.classList.remove('hidden');

        // On mobile: hide sidebar, show chat panel
        convSidebar.classList.add('mobile-hidden');
        chatPanel.classList.add('mobile-chat-visible');

        // Reset composer
        composerTextarea.value = '';
        composerSendBtn.disabled = true;
        hideComposerError();
        autoResizeTextarea();

        // Load messages
        loadMessages(convId, true);
    }

    // Open by partner user ID (creates conversation if needed)
    async function openConversationWithUser(userId) {
        const res = await window.apiFetch('api/messages/start.php', {
            method: 'POST',
            body: { partner_id: userId }
        });

        if (res.success && res.data) {
            const d = res.data;

            // Check if this conv is already in the list; if not, add a placeholder
            const existing = conversationsCache.find(c => c.conversation_id === d.conversation_id);
            if (!existing) {
                // Reload conversation list to include the new one
                await loadConversations();
            }

            openChat(d.conversation_id, d.partner_id, d.partner_name, d.partner_username, d.partner_avatar);
        } else {
            window.showToast(res.message || 'Could not open conversation.', 'error');
        }
    }

    // ─── LOAD MESSAGES ─────────────────────────────────────────────────────────
    async function loadMessages(convId, isInitial = false) {
        if (isInitial) {
            chatLoading.classList.remove('hidden');
            chatNoMessages.classList.add('hidden');
            messagesContainer.innerHTML = '';
        }

        const url = isInitial
            ? `api/messages/messages.php?conversation_id=${convId}`
            : `api/messages/messages.php?conversation_id=${convId}&after_id=${lastMessageId}`;

        const res = await window.apiFetch(url);

        if (isInitial) chatLoading.classList.add('hidden');

        if (!res.success) return;

        const messages = res.data || [];

        if (isInitial && messages.length === 0) {
            chatNoMessages.classList.remove('hidden');
        }

        if (messages.length > 0) {
            chatNoMessages.classList.add('hidden');
            appendMessages(messages, isInitial);
            lastMessageId = messages[messages.length - 1].id;

            // Update sidebar preview
            const lastMsg = messages[messages.length - 1];
            const previewText = (lastMsg.is_mine ? 'You: ' : '') + truncate(lastMsg.message, 60);
            updateConvItemPreview(convId, previewText, lastMsg.time_ago, 0);
        }

        // Start polling after initial load
        if (isInitial) {
            startPolling(convId);
        }
    }

    function appendMessages(messages, scrollToBottom = false) {
        const fragment = document.createDocumentFragment();

        messages.forEach(msg => {
            const el = buildMessageBubble(msg);
            fragment.appendChild(el);
        });

        messagesContainer.appendChild(fragment);

        if (scrollToBottom) {
            scrollToLatest();
        }
    }

    function buildMessageBubble(msg) {
        const wrap = document.createElement('div');
        wrap.className = `msg-bubble-wrap ${msg.is_mine ? 'msg-mine' : 'msg-theirs'}`;
        wrap.dataset.msgId = msg.id;

        if (!msg.is_mine) {
            // Show avatar for received messages
            const avatarImg = document.createElement('img');
            avatarImg.className = 'msg-avatar';
            avatarImg.src = msg.sender_avatar;
            avatarImg.alt = msg.sender_name;
            avatarImg.loading = 'lazy';
            wrap.appendChild(avatarImg);
        }

        const bubbleGroup = document.createElement('div');
        bubbleGroup.className = 'msg-bubble-group';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        // Safe text content (no XSS) - preserve line breaks
        bubble.innerHTML = nl2br(window.escapeHtml(msg.message));

        const meta = document.createElement('div');
        meta.className = 'msg-meta';
        meta.textContent = msg.time_ago;
        meta.title = msg.created_at;

        bubbleGroup.appendChild(bubble);
        bubbleGroup.appendChild(meta);
        wrap.appendChild(bubbleGroup);

        return wrap;
    }

    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }

    function scrollToLatest(smooth = false) {
        chatMessagesArea.scrollTo({
            top: chatMessagesArea.scrollHeight,
            behavior: smooth ? 'smooth' : 'instant'
        });
    }

    // ─── POLLING ───────────────────────────────────────────────────────────────
    function startPolling(convId) {
        stopPolling();
        pollTimer = setInterval(() => {
            if (activeConversationId === convId) {
                pollNewMessages(convId);
            }
        }, 3000); // poll every 3 seconds
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    async function pollNewMessages(convId) {
        const res = await window.apiFetch(
            `api/messages/messages.php?conversation_id=${convId}&after_id=${lastMessageId}`
        );
        if (!res.success || !res.data || res.data.length === 0) return;

        const newMessages = res.data;
        chatNoMessages.classList.add('hidden');
        appendMessages(newMessages, true);
        lastMessageId = newMessages[newMessages.length - 1].id;

        // Update sidebar preview for the last message
        const last = newMessages[newMessages.length - 1];
        const previewText = (last.is_mine ? 'You: ' : '') + truncate(last.message, 60);
        updateConvItemPreview(convId, previewText, last.time_ago, 0);
    }

    // ─── SEND MESSAGE ──────────────────────────────────────────────────────────
    async function sendMessage() {
        if (isSending || !activeConversationId) return;

        const text = composerTextarea.value.trim();
        if (!text) return;

        isSending = true;
        composerSendBtn.disabled = true;
        hideComposerError();

        // Optimistic UI: add the message immediately
        const tempId = 'temp-' + Date.now();
        const optimisticMsg = {
            id:              tempId,
            conversation_id: activeConversationId,
            sender_id:       ME,
            is_mine:         true,
            message:         text,
            is_read:         false,
            created_at:      new Date().toISOString(),
            time_ago:        'Just now',
            sender_name:     CFG.currentUserName,
            sender_username: '',
            sender_avatar:   CFG.currentUserAvatar,
        };

        chatNoMessages.classList.add('hidden');
        appendMessages([optimisticMsg], true);
        composerTextarea.value = '';
        autoResizeTextarea();

        const res = await window.apiFetch('api/messages/send.php', {
            method: 'POST',
            body: {
                conversation_id: activeConversationId,
                message: text
            }
        });

        isSending = false;

        if (res.success && res.data) {
            // Replace optimistic bubble with real one (update id)
            const tempEl = messagesContainer.querySelector(`[data-msg-id="${tempId}"]`);
            if (tempEl) {
                tempEl.dataset.msgId = res.data.id;
                const metaEl = tempEl.querySelector('.msg-meta');
                if (metaEl) metaEl.textContent = res.data.time_ago;
            }
            lastMessageId = Math.max(lastMessageId, res.data.id);

            // Update sidebar preview
            updateConvItemPreview(
                activeConversationId,
                'You: ' + truncate(text, 60),
                res.data.time_ago,
                0
            );
        } else {
            // Remove the optimistic bubble
            const tempEl = messagesContainer.querySelector(`[data-msg-id="${tempId}"]`);
            if (tempEl) tempEl.remove();
            showComposerError(res.message || 'Could not send message. Please try again.');
            composerTextarea.value = text; // restore text
            autoResizeTextarea();
        }

        // Re-enable button based on current content
        composerSendBtn.disabled = composerTextarea.value.trim() === '';
    }

    // ─── COMPOSER ──────────────────────────────────────────────────────────────
    if (composerTextarea) {
        composerTextarea.addEventListener('input', () => {
            composerSendBtn.disabled = composerTextarea.value.trim() === '';
            autoResizeTextarea();
            hideComposerError();
        });

        composerTextarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!composerSendBtn.disabled) sendMessage();
            }
        });
    }

    if (composerSendBtn) {
        composerSendBtn.addEventListener('click', () => sendMessage());
    }

    function autoResizeTextarea() {
        if (!composerTextarea) return;
        composerTextarea.style.height = 'auto';
        const maxH = 140;
        composerTextarea.style.height = Math.min(composerTextarea.scrollHeight, maxH) + 'px';
    }

    function showComposerError(msg) {
        composerErrorText.textContent = msg;
        composerError.classList.remove('hidden');
    }

    function hideComposerError() {
        composerError.classList.add('hidden');
    }

    // ─── MOBILE BACK BUTTON ────────────────────────────────────────────────────
    if (chatBackBtn) {
        chatBackBtn.addEventListener('click', () => {
            convSidebar.classList.remove('mobile-hidden');
            chatPanel.classList.remove('mobile-chat-visible');
            // Keep activeConversation open but just hide the panel on mobile
        });
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────────
    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    // ─── BOOT ──────────────────────────────────────────────────────────────────
    init();

})();
