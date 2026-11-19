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

    // Send notification to the sender
    create_notification(
        $pdo,
        $senderId,
        'swap_accepted',
        'Swap Request Accepted!',
        "{$req['receiver_name']} accepted your skill swap request for {$req['requested_skill']}!",
        $requestId
    );

    json_response(true, 'Swap request accepted!', [
        'request_id' => $requestId,
        'status'     => 'accepted'
    ]);

} catch (Exception $e) {
    error_log('[Accept Swap Request Error] ' . $e->getMessage());
    json_response(false, 'Failed to accept swap request.', null, 500);
}
