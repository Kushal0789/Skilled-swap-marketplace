<?php
/**
 * Reject Swap Request API Endpoint
 * POST /api/requests/reject.php
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

    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT sr.id, sr.sender_id, sr.receiver_id, sr.status, u.name AS receiver_name
        FROM swap_requests sr
        INNER JOIN users u ON sr.receiver_id = u.id
        WHERE sr.id = :id AND sr.receiver_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':id' => $requestId, ':uid' => $userId]);
    $req = $stmt->fetch();

    if (!$req) {
        json_response(false, 'Request not found or unauthorized.', null, 404);
    }

    if ($req['status'] !== 'pending') {
        json_response(false, "This request has already been {$req['status']}.", null, 400);
    }

    $updateStmt = $pdo->prepare("UPDATE swap_requests SET status = 'rejected', updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([':id' => $requestId]);

    create_notification(
        $pdo,
        $req['sender_id'],
        'swap_rejected',
        'Swap Request Update',
        "{$req['receiver_name']} was unable to accept your skill swap request at this time.",
        $requestId
    );

    json_response(true, 'Swap request rejected.', [
        'request_id' => $requestId,
        'status'     => 'rejected'
    ]);

} catch (Exception $e) {
    error_log('[Reject Swap Request Error] ' . $e->getMessage());
    json_response(false, 'Failed to update request.', null, 500);
}
