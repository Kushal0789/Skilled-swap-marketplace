<?php
/**
 * Send Message API Endpoint
 * POST /api/messages/send.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

$userId = current_user_id();
$input = get_json_input();

$convId = (int)($input['conversation_id'] ?? 0);
$message = trim($input['message'] ?? '');

if ($convId <= 0) {
    json_response(false, 'Invalid conversation.', null, 422);
}

if (empty($message)) {
    json_response(false, 'Message text cannot be empty.', null, 422);
}

try {
    $pdo = get_db();

    // Verify user belongs to conversation
    $cStmt = $pdo->prepare("SELECT id, user_one, user_two FROM conversations WHERE id = :cid AND (user_one = :uid OR user_two = :uid) LIMIT 1");
    $cStmt->execute([':cid' => $convId, ':uid' => $userId]);
    $conv = $cStmt->fetch();

    if (!$conv) {
        json_response(false, 'Conversation not found or unauthorized.', null, 404);
    }

    $partnerId = ($conv['user_one'] == $userId) ? (int)$conv['user_two'] : (int)$conv['user_one'];

    // Insert message
    $msgStmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, message, is_read, created_at)
        VALUES (:cid, :sid, :msg, 0, NOW())
    ");
    $msgStmt->execute([
        ':cid' => $convId,
        ':sid' => $userId,
        ':msg' => $message
    ]);

    $messageId = (int)$pdo->lastInsertId();

    // Update conversation timestamp
    $upConv = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = :cid");
    $upConv->execute([':cid' => $convId]);

    // Send notification to recipient
    $sender = current_user();
    $senderName = $sender['name'] ?? 'A member';
    $preview = (strlen($message) > 60) ? substr($message, 0, 57) . '...' : $message;

    create_notification(
        $pdo,
        $partnerId,
        'new_message',
        "New message from {$senderName}",
        $preview,
        $convId
    );

    json_response(true, 'Message sent.', [
        'id'         => $messageId,
        'is_mine'    => true,
        'message'    => $message,
        'created_at' => date('Y-m-d H:i:s'),
        'time'       => date('g:i A')
    ], 201);

} catch (Exception $e) {
    error_log('[Send Message Error] ' . $e->getMessage());
    json_response(false, 'Unable to send message.', null, 500);
}
