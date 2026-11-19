<?php
/**
 * Messages Page - SkillSwap Marketplace
 * Instagram DM-inspired messaging interface
 */

define('APP_INIT', true);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Must be logged in
require_auth();

$currentUser   = current_user();
$currentUserId = current_user_id();

// Support deep-linking: ?with=USER_ID opens a conversation immediately
$openWithUserId = isset($_GET['with']) ? (int)$_GET['with'] : 0;

$pageTitle  = 'Messages — SkillSwap';
$pageDesc   = 'Chat with your skill-swap partners.';
$pageScript = 'messages.js';

require_once __DIR__ . '/includes/header.php';
?>
<div class="messages-page-wrapper">

    <!-- ===== CONVERSATION LIST PANEL (left) ===== -->
    <aside class="conv-sidebar" id="conv-sidebar">
        <div class="conv-sidebar-header">
            <h2 class="conv-sidebar-title">Messages</h2>
        </div>

        <!-- Conversation list -->
        <div class="conv-list" id="conv-list">
            <!-- Loading state -->
            <div class="conv-list-loading" id="conv-list-loading">
                <span class="spinner spinner-primary"></span>
                <span>Loading conversations…</span>
            </div>

            <!-- Empty state (hidden until loaded) -->
            <div class="conv-empty-state hidden" id="conv-empty-state">
                <div class="conv-empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <p class="conv-empty-title">No conversations yet</p>
                <p class="conv-empty-hint">Go to <a href="matches.php">Matches</a> and message a partner!</p>
            </div>

            <!-- Conversation items rendered by JS -->
            <div id="conv-items-container"></div>
        </div>
    </aside>

    <!-- ===== CHAT PANEL (right) ===== -->
    <section class="chat-panel" id="chat-panel">

        <!-- Welcome screen (no conversation open) -->
        <div class="chat-welcome" id="chat-welcome">
            <div class="chat-welcome-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <h3>Your Messages</h3>
            <p>Select a conversation from the left to start chatting with your skill-swap partners.</p>
        </div>

        <!-- Active conversation view (hidden until a conversation is opened) -->
        <div class="chat-view hidden" id="chat-view">

            <!-- Chat header -->
            <div class="chat-header" id="chat-header">
                <!-- Mobile back button -->
                <button class="chat-back-btn" id="chat-back-btn" aria-label="Back to conversations">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <img class="chat-header-avatar" id="chat-header-avatar" src="assets/images/default-avatar.svg" alt="Partner avatar">
                <div class="chat-header-info">
                    <span class="chat-header-name" id="chat-header-name">Partner Name</span>
                    <span class="chat-header-username" id="chat-header-username">@username</span>
                </div>
            </div>

            <!-- Messages area -->
            <div class="chat-messages-area" id="chat-messages-area">
                <!-- Loading state -->
                <div class="chat-loading hidden" id="chat-loading">
                    <span class="spinner spinner-primary"></span>
                    <span>Loading messages…</span>
                </div>

                <!-- Empty messages state -->
                <div class="chat-no-messages hidden" id="chat-no-messages">
                    <div class="chat-no-msg-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <p>No messages yet. Say hello! 👋</p>
                </div>

                <!-- Messages rendered by JS -->
                <div id="messages-container"></div>
            </div>

            <!-- Message composer -->
            <div class="chat-composer" id="chat-composer">
                <div class="composer-inner">
                    <textarea
                        class="composer-textarea"
                        id="composer-textarea"
                        placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
                        rows="1"
                        maxlength="5000"
                        aria-label="Message input"
                    ></textarea>
                    <button class="composer-send-btn" id="composer-send-btn" aria-label="Send message" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <div class="composer-error hidden" id="composer-error">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span id="composer-error-text">Could not send message.</span>
                </div>
            </div>

        </div><!-- /chat-view -->
    </section>
</div>

<!-- Hidden data for JS bootstrapping -->
<script>
    window.MESSAGES_CONFIG = {
        currentUserId:    <?= (int)$currentUserId ?>,
        currentUserName:  <?= json_encode($currentUser['name']) ?>,
        currentUserAvatar:<?= json_encode(get_avatar_url($currentUser['profile_image'])) ?>,
        openWithUserId:   <?= (int)$openWithUserId ?>,
        csrfToken:        <?= json_encode(generate_csrf_token()) ?>
    };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
