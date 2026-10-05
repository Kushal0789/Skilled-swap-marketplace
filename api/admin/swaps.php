<?php
/**
 * Admin Swaps Management API Endpoint
 * Skill Swap Marketplace
 *
 * GET  /api/admin/swaps.php            - List all swap requests
 * GET  /api/admin/swaps.php?status=X   - Filter by status
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

$pdo = get_db();

$allowedStatuses = ['pending', 'accepted', 'rejected', 'cancelled'];
$filterStatus = isset($_GET['status']) && in_array($_GET['status'], $allowedStatuses, true)
    ? $_GET['status']
    : null;

try {
    $sql = "
        SELECT
            sr.id,
            sr.status,
            sr.message,
            sr.created_at,
            sr.updated_at,
            sender.id        AS sender_id,
            sender.name      AS sender_name,
            sender.username  AS sender_username,
            receiver.id      AS receiver_id,
            receiver.name    AS receiver_name,
            receiver.username AS receiver_username,
            offered.name     AS offered_skill,
            requested.name   AS requested_skill
        FROM swap_requests sr
        INNER JOIN users    sender   ON sr.sender_id          = sender.id
        INNER JOIN users    receiver ON sr.receiver_id         = receiver.id
        INNER JOIN skills   offered  ON sr.offered_skill_id   = offered.id
        INNER JOIN skills   requested ON sr.requested_skill_id = requested.id
    ";

    $params = [];
    if ($filterStatus) {
        $sql .= " WHERE sr.status = :status";
        $params[':status'] = $filterStatus;
    }

    $sql .= " ORDER BY sr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $swaps = $stmt->fetchAll();

    foreach ($swaps as &$s) {
        $s['time_ago'] = time_ago($s['created_at']);
    }

    json_response(true, 'Swaps loaded.', $swaps);

} catch (Exception $e) {
    error_log('[Admin Swaps Error] ' . $e->getMessage());
    json_response(false, 'Failed to load swaps.', null, 500);
}
