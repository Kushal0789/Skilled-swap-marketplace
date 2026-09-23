<?php
/**
 * Cancel Outgoing Swap Request API Endpoint
 * POST /api/requests/cancel.php
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

    // Verify sender ownership
    $stmt = $pdo->prepare("SELECT id, status FROM swap_requests WHERE id = :id AND sender_id = :uid LIMIT 1");
    $stmt->execute([':id' => $requestId, ':uid' => $userId]);
    $req = $stmt->fetch();

    if (!$req) {
        json_response(false, 'Swap request not found or unauthorized.', null, 404);
    }

    if ($req['status'] !== 'pending') {
        json_response(false, 'Only pending requests can be cancelled.', null, 400);
    }

    $updateStmt = $pdo->prepare("UPDATE swap_requests SET status = 'cancelled', updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([':id' => $requestId]);

    json_response(true, 'Swap request cancelled successfully.', [
        'request_id' => $requestId,
        'status'     => 'cancelled'
    ]);

} catch (Exception $e) {
    error_log('[Cancel Swap Request Error] ' . $e->getMessage());
    json_response(false, 'Failed to cancel request.', null, 500);
}
