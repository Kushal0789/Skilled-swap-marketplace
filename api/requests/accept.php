<?php
/**
 * Accept Swap Request API Endpoint
 * POST /api/requests/accept.php
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
$requestId = (int)($input['request_id'] ?? 0);

if ($requestId <= 0) {
    json_response(false, 'Invalid request ID.', null, 422);
}

try {
    $pdo = get_db();

    // Check request exists and current user is receiver
    $stmt = $pdo->prepare("
        SELECT sr.id, sr.sender_id, sr.receiver_id, sr.status,
               s_off.name AS offered_skill, s_req.name AS requested_skill,
               u.name AS receiver_name
        FROM swap_requests sr
        INNER JOIN skills s_off ON sr.offered_skill_id = s_off.id
        INNER JOIN skills s_req ON sr.requested_skill_id = s_req.id
        INNER JOIN users u ON sr.receiver_id = u.id
        WHERE sr.id = :id AND sr.receiver_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':id' => $requestId, ':uid' => $userId]);
    $req = $stmt->fetch();

    if (!$req) {
        json_response(false, 'Swap request not found or unauthorized.', null, 404);
    }

    if ($req['status'] !== 'pending') {
        json_response(false, "This request has already been {$req['status']}.", null, 400);
    }

    // Update request status to accepted
    $updateStmt = $pdo->prepare("UPDATE swap_requests SET status = 'accepted', updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([':id' => $requestId]);

    $senderId = (int)$req['sender_id'];

    // Ensure conversation exists between these two users
    $u1 = min($userId, $senderId);
    $u2 = max($userId, $senderId);

    $convStmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one = :u1 AND user_two = :u2 LIMIT 1");
    $convStmt->execute([':u1' => $u1, ':u2' => $u2]);
    $conv = $convStmt->fetch();

    if ($conv) {
        $conversationId = (int)$conv['id'];
    } else {
        $insertConv = $pdo->prepare("INSERT INTO conversations (user_one, user_two, created_at) VALUES (:u1, :u2, NOW())");
        $insertConv->execute([':u1' => $u1, ':u2' => $u2]);
        $conversationId = (int)$pdo->lastInsertId();
    }

    // Post an automated greeting/notification message in the conversation thread
    $autoMsg = "Hello! I accepted your skill swap request for {$req['offered_skill']} & {$req['requested_skill']}. Let's coordinate our first session!";
    $msgStmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_read, created_at) VALUES (:cid, :sid, :msg, 0, NOW())");
    $msgStmt->execute([
        ':cid' => $conversationId,
        ':sid' => $userId,
        ':msg' => $autoMsg
    ]);

    // Send notification to the sender
    create_notification(
        $pdo,
        $senderId,
        'swap_accepted',
        'Swap Request Accepted!',
        "{$req['receiver_name']} accepted your skill swap request for {$req['requested_skill']}!",
        $requestId
    );

    json_response(true, 'Swap request accepted! A new chat conversation has been started.', [
        'request_id'      => $requestId,
        'conversation_id' => $conversationId,
        'status'          => 'accepted'
    ]);

} catch (Exception $e) {
    error_log('[Accept Swap Request Error] ' . $e->getMessage());
    json_response(false, 'Failed to accept swap request.', null, 500);
}
