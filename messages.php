<?php
/**
 * Private Chat & Messaging System (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$pageTitle = 'Messages & Chat - Skill Swap Marketplace';
$pageScript = 'messages.js';
include __DIR__ . '/includes/header.php';
?>

<div class="chat-page-wrapper">
    <div class="chat-layout">
        <!-- Sidebar: Conversations List -->
        <aside class="chat-conv-sidebar">
            <div class="chat-sidebar-header">
                <h3>Direct Messages</h3>
            </div>
            <div class="chat-conversations-list" id="chat-conv-list">
                <div style="text-align: center; padding: 2rem;">
                    <span class="spinner spinner-primary"></span>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">Loading chats...</p>
                </div>
            </div>
        </aside>

        <!-- Main: Active Conversation Area -->
        <section class="chat-main-area">
            <!-- Empty state when no conversation is selected -->
            <div id="chat-empty-view" class="empty-state" style="margin: auto;">
                <div class="empty-state-icon">💬</div>
                <h3 class="empty-state-title">Select a Conversation</h3>
                <p class="empty-state-desc">Choose a partner from the sidebar to view your messages and plan your swap sessions.</p>
            </div>

            <!-- Active View -->
            <div id="chat-active-view" class="chat-active-container hidden">
                <div class="chat-thread-header">
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <img src="assets/images/default-avatar.svg" alt="User Avatar" id="chat-partner-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <h4 id="chat-partner-name" style="font-size: 1rem; margin-bottom: 2px;">Partner Name</h4>
                            <span id="chat-partner-location" style="font-size: 0.78rem; color: var(--text-muted);">Location</span>
                        </div>
                    </div>

                    <div>
                        <span class="badge-pill bg-success" style="font-size: 0.72rem; padding: 2px 8px;">Active Connection</span>
                    </div>
                </div>

                <div class="chat-messages-container" id="chat-messages-container">
                    <!-- Messages will be injected here -->
                </div>

                <form id="chat-send-form" class="chat-input-bar">
                    <input type="text" id="chat-message-input" class="form-input" placeholder="Type your message and press Enter..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary" id="btn-send-msg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        <span>Send</span>
                    </button>
                </form>
            </div>
        </section>
    </div>
</div>

<style>
.chat-page-wrapper {
    max-width: 1280px;
    margin: 1.5rem auto;
    padding: 0 1.5rem;
}

.chat-active-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
