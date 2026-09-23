<?php
/**
 * User Conversations List API Endpoint
 * GET /api/messages/conversations.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

$userId = current_user_id();

try {
    $pdo = get_db();

    // If query has recipient_id, find or create conversation
    $targetRecipientId = !empty($_GET['recipient_id']) ? (int)$_GET['recipient_id'] : 0;
    if ($targetRecipientId > 0 && $targetRecipientId !== $userId) {
        $u1 = min($userId, $targetRecipientId);
        $u2 = max($userId, $targetRecipientId);

        $checkConv = $pdo->prepare("SELECT id FROM conversations WHERE user_one = :u1 AND user_two = :u2 LIMIT 1");
        $checkConv->execute([':u1' => $u1, ':u2' => $u2]);
        $existingConv = $checkConv->fetch();

        if (!$existingConv) {
            $createConv = $pdo->prepare("INSERT INTO conversations (user_one, user_two, created_at) VALUES (:u1, :u2, NOW())");
            $createConv->execute([':u1' => $u1, ':u2' => $u2]);
        }
    }

    // Fetch all conversations where current user is a participant
    $stmt = $pdo->prepare("
        SELECT c.id, c.user_one, c.user_two, c.updated_at,
               CASE WHEN c.user_one = :uid THEN c.user_two ELSE c.user_one END AS partner_id
        FROM conversations c
        WHERE c.user_one = :uid OR c.user_two = :uid
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $rawConvs = $stmt->fetchAll();

    $conversations = [];

    foreach ($rawConvs as $rc) {
        $partnerId = (int)$rc['partner_id'];

        // Partner details
        $pStmt = $pdo->prepare("SELECT id, name, username, profile_image, location FROM users WHERE id = :pid LIMIT 1");
        $pStmt->execute([':pid' => $partnerId]);
        $partner = $pStmt->fetch();

        if (!$partner) continue;

        // Last message in this conversation
        $mStmt = $pdo->prepare("
            SELECT id, sender_id, message, is_read, created_at
            FROM messages
            WHERE conversation_id = :cid
            ORDER BY id DESC LIMIT 1
        ");
        $mStmt->execute([':cid' => $rc['id']]);
        $lastMsg = $mStmt->fetch();

        // Unread messages count for current user
        $unreadStmt = $pdo->prepare("
            SELECT COUNT(*) FROM messages
            WHERE conversation_id = :cid AND sender_id != :uid AND is_read = 0
        ");
        $unreadStmt->execute([':cid' => $rc['id'], ':uid' => $userId]);
        $unreadCount = (int)$unreadStmt->fetchColumn();

        $conversations[] = [
            'id'             => $rc['id'],
            'partner'        => [
                'id'         => $partner['id'],
                'name'       => $partner['name'],
                'username'   => $partner['username'],
                'avatar_url' => get_avatar_url($partner['profile_image']),
                'location'   => $partner['location']
            ],
            'last_message'   => $lastMsg ? $lastMsg['message'] : 'No messages yet.',
            'last_sender_id' => $lastMsg ? (int)$lastMsg['sender_id'] : null,
            'last_time'      => $lastMsg ? time_ago($lastMsg['created_at']) : time_ago($rc['updated_at']),
            'unread_count'   => $unreadCount
        ];
    }

    json_response(true, 'Conversations loaded.', [
        'conversations' => $conversations
    ]);

} catch (Exception $e) {
    error_log('[Conversations Error] ' . $e->getMessage());
    json_response(false, 'Unable to load conversations.', null, 500);
}
