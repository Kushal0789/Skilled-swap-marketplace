<?php
/**
 * List Swap Requests API Endpoint
 * GET /api/requests/list.php?filter=incoming|outgoing|all
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

    // 1. Incoming requests (where current user is receiver)
    $inStmt = $pdo->prepare("
        SELECT sr.id, sr.sender_id, sr.receiver_id, sr.message, sr.status, sr.created_at, sr.updated_at,
               u.name AS sender_name, u.username AS sender_username, u.profile_image AS sender_avatar, u.location AS sender_location,
               s_off.id AS offered_skill_id, s_off.name AS offered_skill_name,
               s_req.id AS requested_skill_id, s_req.name AS requested_skill_name
        FROM swap_requests sr
        INNER JOIN users u ON sr.sender_id = u.id
        INNER JOIN skills s_off ON sr.offered_skill_id = s_off.id
        INNER JOIN skills s_req ON sr.requested_skill_id = s_req.id
        WHERE sr.receiver_id = :uid
        ORDER BY sr.created_at DESC
    ");
    $inStmt->execute([':uid' => $userId]);
    $incoming = $inStmt->fetchAll();

    foreach ($incoming as &$in) {
        $in['sender_avatar_url'] = get_avatar_url($in['sender_avatar']);
        $in['time_ago'] = time_ago($in['created_at']);
    }

    // 2. Outgoing requests (where current user is sender)
    $outStmt = $pdo->prepare("
        SELECT sr.id, sr.sender_id, sr.receiver_id, sr.message, sr.status, sr.created_at, sr.updated_at,
               u.name AS receiver_name, u.username AS receiver_username, u.profile_image AS receiver_avatar, u.location AS receiver_location,
               s_off.id AS offered_skill_id, s_off.name AS offered_skill_name,
               s_req.id AS requested_skill_id, s_req.name AS requested_skill_name
        FROM swap_requests sr
        INNER JOIN users u ON sr.receiver_id = u.id
        INNER JOIN skills s_off ON sr.offered_skill_id = s_off.id
        INNER JOIN skills s_req ON sr.requested_skill_id = s_req.id
        WHERE sr.sender_id = :uid
        ORDER BY sr.created_at DESC
    ");
    $outStmt->execute([':uid' => $userId]);
    $outgoing = $outStmt->fetchAll();

    foreach ($outgoing as &$out) {
        $out['receiver_avatar_url'] = get_avatar_url($out['receiver_avatar']);
        $out['time_ago'] = time_ago($out['created_at']);
    }

    json_response(true, 'Swap requests retrieved.', [
        'incoming'        => $incoming,
        'outgoing'        => $outgoing,
        'pending_in_count' => count(array_filter($incoming, fn($r) => $r['status'] === 'pending')),
        'active_swaps'     => count(array_filter($incoming, fn($r) => $r['status'] === 'accepted')) +
                             count(array_filter($outgoing, fn($r) => $r['status'] === 'accepted'))
    ]);

} catch (Exception $e) {
    error_log('[List Swap Requests Error] ' . $e->getMessage());
    json_response(false, 'Unable to load swap requests.', null, 500);
}
