<?php
/**
 * API: Send a new message in a conversation
 * POST /api/messages/send.php
 * Body (JSON): { conversation_id: int, message: string }
 *           OR { partner_id: int, message: string }  (creates conversation if none exists)
 * Skill Swap Marketplace - Messaging System
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', null, 405);
}

$currentUserId = current_user_id();
$pdo = get_db();

$input = get_json_input();

$rawMessage     = $input['message']         ?? '';
$conversationId = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
$partnerId      = isset($input['partner_id'])      ? (int)$input['partner_id']      : 0;

// Validate message content
$message = trim($rawMessage);
if ($message === '') {
    json_response(false, 'Message cannot be empty.', null, 422);
}
if (mb_strlen($message) > 5000) {
    json_response(false, 'Message is too long (max 5000 characters).', null, 422);
}

try {
    // Determine or create the conversation
    if ($conversationId > 0) {
        // Verify the user is a participant
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
    } elseif ($partnerId > 0) {
        // Cannot message yourself
        if ($partnerId === $currentUserId) {
            json_response(false, 'You cannot message yourself.', null, 422);
        }

        // Verify partner exists
        $partnerStmt = $pdo->prepare("SELECT id FROM users WHERE id = :pid AND status = 'active' LIMIT 1");
        $partnerStmt->execute([':pid' => $partnerId]);
        if (!$partnerStmt->fetch()) {
            json_response(false, 'User not found.', null, 404);
        }

        // Ensure user_one < user_two for the unique constraint
        $userOne = min($currentUserId, $partnerId);
        $userTwo = max($currentUserId, $partnerId);

        // Find existing conversation or create one
        $convStmt = $pdo->prepare("
            SELECT id FROM conversations
            WHERE user_one = :u1 AND user_two = :u2
            LIMIT 1
        ");
        $convStmt->execute([':u1' => $userOne, ':u2' => $userTwo]);
        $existingConv = $convStmt->fetch();

        if ($existingConv) {
            $conversationId = (int)$existingConv['id'];
        } else {
            $createConv = $pdo->prepare("
                INSERT INTO conversations (user_one, user_two, created_at, updated_at)
                VALUES (:u1, :u2, NOW(), NOW())
            ");
            $createConv->execute([':u1' => $userOne, ':u2' => $userTwo]);
            $conversationId = (int)$pdo->lastInsertId();
        }
    } else {
        json_response(false, 'Must provide conversation_id or partner_id.', null, 422);
    }

    // Insert message
    $insertStmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, message, is_read, created_at)
        VALUES (:conv_id, :sender, :message, 0, NOW())
    ");
    $insertStmt->execute([
        ':conv_id' => $conversationId,
        ':sender'  => $currentUserId,
        ':message' => $message,
    ]);
    $newMessageId = (int)$pdo->lastInsertId();

    // Update conversation's updated_at to surface it to the top of the list
    $updateConvStmt = $pdo->prepare("
        UPDATE conversations SET updated_at = NOW() WHERE id = :conv_id
    ");
    $updateConvStmt->execute([':conv_id' => $conversationId]);

    // Fetch the newly inserted message to return to client
    $fetchStmt = $pdo->prepare("
        SELECT
            m.id,
            m.conversation_id,
            m.sender_id,
            m.message,
            m.is_read,
            m.created_at,
            u.name            AS sender_name,
            u.username        AS sender_username,
            u.profile_image   AS sender_avatar
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.id = :msg_id
        LIMIT 1
    ");
    $fetchStmt->execute([':msg_id' => $newMessageId]);
    $newMessage = $fetchStmt->fetch();

    json_response(true, 'Message sent.', [
        'id'              => (int)$newMessage['id'],
        'conversation_id' => (int)$newMessage['conversation_id'],
        'sender_id'       => (int)$newMessage['sender_id'],
        'is_mine'         => true,
        'message'         => $newMessage['message'],
        'is_read'         => false,
        'created_at'      => $newMessage['created_at'],
        'time_ago'        => 'Just now',
        'sender_name'     => $newMessage['sender_name'],
        'sender_username' => $newMessage['sender_username'],
        'sender_avatar'   => get_avatar_url($newMessage['sender_avatar']),
    ]);

} catch (Throwable $e) {
    error_log('[SkillSwap Messaging] send.php error: ' . $e->getMessage());
    json_response(false, 'Failed to send message.', null, 500);
}
