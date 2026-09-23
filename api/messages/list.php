<?php
/**
 * Message Thread API Endpoint (Supports Polling via since_id)
 * GET /api/messages/list.php?conversation_id=123&since_id=45
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

$userId = current_user_id();
$convId = (int)($_GET['conversation_id'] ?? 0);
$sinceId = (int)($_GET['since_id'] ?? 0);

if ($convId <= 0) {
    json_response(false, 'Conversation ID is required.', null, 422);
}

try {
    $pdo = get_db();

    // Verify user is a member of this conversation
    $convStmt = $pdo->prepare("SELECT id, user_one, user_two FROM conversations WHERE id = :cid AND (user_one = :uid OR user_two = :uid) LIMIT 1");
    $convStmt->execute([':cid' => $convId, ':uid' => $userId]);
    $conv = $convStmt->fetch();

    if (!$conv) {
        json_response(false, 'Conversation not found or unauthorized.', null, 404);
    }

    $partnerId = ($conv['user_one'] == $userId) ? (int)$conv['user_two'] : (int)$conv['user_one'];
    $pStmt = $pdo->prepare("SELECT id, name, username, profile_image, location FROM users WHERE id = :pid LIMIT 1");
    $pStmt->execute([':pid' => $partnerId]);
    $partner = $pStmt->fetch();

    // Query messages
    if ($sinceId > 0) {
        $msgStmt = $pdo->prepare("
            SELECT id, sender_id, message, is_read, created_at
            FROM messages
            WHERE conversation_id = :cid AND id > :since_id
            ORDER BY id ASC
        ");
        $msgStmt->execute([':cid' => $convId, ':since_id' => $sinceId]);
    } else {
        $msgStmt = $pdo->prepare("
            SELECT id, sender_id, message, is_read, created_at
            FROM messages
            WHERE conversation_id = :cid
            ORDER BY id ASC LIMIT 100
        ");
        $msgStmt->execute([':cid' => $convId]);
    }

    $messages = $msgStmt->fetchAll();

    // Mark unread messages sent by the partner as read
    $markStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = :cid AND sender_id = :pid AND is_read = 0");
    $markStmt->execute([':cid' => $convId, ':pid' => $partnerId]);

    $formatted = [];
    foreach ($messages as $m) {
        $formatted[] = [
            'id'         => (int)$m['id'],
            'sender_id'  => (int)$m['sender_id'],
            'is_mine'    => ((int)$m['sender_id'] === $userId),
            'message'    => $m['message'],
            'is_read'    => (bool)$m['is_read'],
            'created_at' => $m['created_at'],
            'time'       => date('g:i A', strtotime($m['created_at'])),
            'time_ago'   => time_ago($m['created_at'])
        ];
    }

    json_response(true, 'Messages loaded.', [
        'conversation_id' => $convId,
        'partner'         => [
            'id'         => $partner['id'],
            'name'       => $partner['name'],
            'username'   => $partner['username'],
            'avatar_url' => get_avatar_url($partner['profile_image']),
            'location'   => $partner['location']
        ],
        'messages'        => $formatted,
        'new_count'       => count($formatted)
    ]);

} catch (Exception $e) {
    error_log('[Messages List Error] ' . $e->getMessage());
    json_response(false, 'Failed to load messages.', null, 500);
}
