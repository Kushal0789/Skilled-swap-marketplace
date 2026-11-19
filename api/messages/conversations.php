<?php
/**
 * API: List all conversations for the authenticated user
 * GET /api/messages/conversations.php
 * Skill Swap Marketplace - Messaging System
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

$currentUserId = current_user_id();
$pdo = get_db();

try {
    // Fetch all conversations the current user participates in,
    // along with the partner's info and the latest message preview.
    $stmt = $pdo->prepare("
        SELECT
            c.id              AS conversation_id,
            c.updated_at,
            -- Partner identification (user who is NOT the current user)
            CASE WHEN c.user_one = :uid1 THEN c.user_two ELSE c.user_one END AS partner_id,
            u.name            AS partner_name,
            u.username        AS partner_username,
            u.profile_image   AS partner_avatar,
            -- Latest message preview
            m.message         AS last_message,
            m.sender_id       AS last_sender_id,
            m.created_at      AS last_message_time,
            -- Unread count: messages sent by the partner that the current user hasn't read
            (
                SELECT COUNT(*)
                FROM messages unread_m
                WHERE unread_m.conversation_id = c.id
                  AND unread_m.sender_id != :uid2
                  AND unread_m.is_read = 0
            ) AS unread_count
        FROM conversations c
        JOIN users u ON u.id = CASE WHEN c.user_one = :uid3 THEN c.user_two ELSE c.user_one END
        LEFT JOIN messages m ON m.id = (
            SELECT id FROM messages
            WHERE conversation_id = c.id
            ORDER BY created_at DESC
            LIMIT 1
        )
        WHERE c.user_one = :uid4 OR c.user_two = :uid5
        ORDER BY COALESCE(m.created_at, c.created_at) DESC
    ");

    $stmt->execute([
        ':uid1' => $currentUserId,
        ':uid2' => $currentUserId,
        ':uid3' => $currentUserId,
        ':uid4' => $currentUserId,
        ':uid5' => $currentUserId,
    ]);

    $conversations = $stmt->fetchAll();

    // Format for frontend
    $formatted = array_map(function ($row) use ($currentUserId) {
        // Truncate preview to 80 chars
        $preview = $row['last_message'] ?? '';
        if (mb_strlen($preview) > 80) {
            $preview = mb_substr($preview, 0, 77) . '...';
        }

        // If the last message was sent by the current user, prefix "You: "
        $previewPrefix = '';
        if ($row['last_sender_id'] && (int)$row['last_sender_id'] === $currentUserId) {
            $previewPrefix = 'You: ';
        }

        return [
            'conversation_id' => (int)$row['conversation_id'],
            'partner_id'      => (int)$row['partner_id'],
            'partner_name'    => $row['partner_name'],
            'partner_username'=> $row['partner_username'],
            'partner_avatar'  => get_avatar_url($row['partner_avatar']),
            'last_message'    => $previewPrefix . $preview,
            'last_message_time' => $row['last_message_time'] ?? $row['updated_at'],
            'time_ago'        => time_ago($row['last_message_time'] ?? $row['updated_at']),
            'unread_count'    => (int)$row['unread_count'],
        ];
    }, $conversations);

    json_response(true, 'Conversations loaded.', $formatted);

} catch (Throwable $e) {
    error_log('[SkillSwap Messaging] conversations.php error: ' . $e->getMessage());
    json_response(false, 'Failed to load conversations.', null, 500);
}
