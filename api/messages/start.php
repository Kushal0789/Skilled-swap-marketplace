<?php
/**
 * API: Start or find an existing conversation with a user
 * POST /api/messages/start.php
 * Body (JSON): { partner_id: int }
 * Returns: { conversation_id, partner_id, partner_name, ... }
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

$input     = get_json_input();
$partnerId = isset($input['partner_id']) ? (int)$input['partner_id'] : 0;

if ($partnerId <= 0) {
    json_response(false, 'Invalid partner ID.', null, 400);
}

if ($partnerId === $currentUserId) {
    json_response(false, 'You cannot message yourself.', null, 422);
}

try {
    // Verify partner exists and is active
    $partnerStmt = $pdo->prepare("
        SELECT id, name, username, profile_image
        FROM users
        WHERE id = :pid AND status = 'active'
        LIMIT 1
    ");
    $partnerStmt->execute([':pid' => $partnerId]);
    $partner = $partnerStmt->fetch();

    if (!$partner) {
        json_response(false, 'User not found.', null, 404);
    }

    // Normalize pair so user_one < user_two (enforced by UNIQUE constraint)
    $userOne = min($currentUserId, $partnerId);
    $userTwo = max($currentUserId, $partnerId);

    // Find or create conversation
    $convStmt = $pdo->prepare("
        SELECT id FROM conversations
        WHERE user_one = :u1 AND user_two = :u2
        LIMIT 1
    ");
    $convStmt->execute([':u1' => $userOne, ':u2' => $userTwo]);
    $existing = $convStmt->fetch();

    if ($existing) {
        $conversationId = (int)$existing['id'];
    } else {
        $createStmt = $pdo->prepare("
            INSERT INTO conversations (user_one, user_two, created_at, updated_at)
            VALUES (:u1, :u2, NOW(), NOW())
        ");
        $createStmt->execute([':u1' => $userOne, ':u2' => $userTwo]);
        $conversationId = (int)$pdo->lastInsertId();
    }

    json_response(true, 'Conversation ready.', [
        'conversation_id' => $conversationId,
        'partner_id'      => (int)$partner['id'],
        'partner_name'    => $partner['name'],
        'partner_username'=> $partner['username'],
        'partner_avatar'  => get_avatar_url($partner['profile_image']),
    ]);

} catch (Throwable $e) {
    error_log('[SkillSwap Messaging] start.php error: ' . $e->getMessage());
    json_response(false, 'Failed to start conversation.', null, 500);
}
