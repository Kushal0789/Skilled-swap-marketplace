<?php
/**
 * API: Get messages for a specific conversation
 * GET /api/messages/messages.php?conversation_id=X[&after_id=Y]
 * Skill Swap Marketplace - Messaging System
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

$currentUserId = current_user_id();
$pdo = get_db();

$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$afterId        = isset($_GET['after_id'])        ? (int)$_GET['after_id']        : 0;

if ($conversationId <= 0) {
    json_response(false, 'Invalid conversation ID.', null, 400);
}

try {
    // Authorize: verify this user is a participant in the conversation
    $authStmt = $pdo->prepare("
        SELECT id FROM conversations
        WHERE id = :conv_id
          AND (user_one = :uid1 OR user_two = :uid2)
        LIMIT 1
    ");
    $authStmt->execute([
        ':conv_id' => $conversationId,
        ':uid1'    => $currentUserId,
        ':uid2'    => $currentUserId,
    ]);

    if (!$authStmt->fetch()) {
        json_response(false, 'Access denied.', null, 403);
    }

    // Mark all unread messages from the partner as read
    $markReadStmt = $pdo->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE conversation_id = :conv_id
          AND sender_id != :uid
          AND is_read = 0
    ");
    $markReadStmt->execute([
        ':conv_id' => $conversationId,
        ':uid'     => $currentUserId,
    ]);

    // Fetch messages (optionally only newer than after_id for polling)
    if ($afterId > 0) {
        $msgStmt = $pdo->prepare("
            SELECT
                m.id,
                m.conversation_id,
                m.sender_id,
                m.message,
                m.is_read,
                m.created_at,
                u.name    AS sender_name,
                u.username AS sender_username,
                u.profile_image AS sender_avatar
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = :conv_id
              AND m.id > :after_id
            ORDER BY m.created_at ASC
        ");
        $msgStmt->execute([
            ':conv_id'  => $conversationId,
            ':after_id' => $afterId,
        ]);
    } else {
        // Load last 100 messages on initial load
        $msgStmt = $pdo->prepare("
            SELECT
                m.id,
                m.conversation_id,
                m.sender_id,
                m.message,
                m.is_read,
                m.created_at,
                u.name    AS sender_name,
                u.username AS sender_username,
                u.profile_image AS sender_avatar
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = :conv_id
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $msgStmt->execute([':conv_id' => $conversationId]);
    }

    $messages = $msgStmt->fetchAll();

    $formatted = array_map(function ($msg) use ($currentUserId) {
        return [
            'id'              => (int)$msg['id'],
            'conversation_id' => (int)$msg['conversation_id'],
            'sender_id'       => (int)$msg['sender_id'],
            'is_mine'         => (int)$msg['sender_id'] === $currentUserId,
            'message'         => $msg['message'],
            'is_read'         => (bool)$msg['is_read'],
            'created_at'      => $msg['created_at'],
            'time_ago'        => time_ago($msg['created_at']),
            'sender_name'     => $msg['sender_name'],
            'sender_username' => $msg['sender_username'],
            'sender_avatar'   => get_avatar_url($msg['sender_avatar']),
        ];
    }, $messages);

    json_response(true, 'Messages loaded.', $formatted);

} catch (Throwable $e) {
    error_log('[SkillSwap Messaging] messages.php error: ' . $e->getMessage());
    json_response(false, 'Failed to load messages.', null, 500);
}
