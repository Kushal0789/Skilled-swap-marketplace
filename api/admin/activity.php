<?php
/**
 * Admin Recent Activity API Endpoint
 * Skill Swap Marketplace
 *
 * GET /api/admin/activity.php
 * Returns a merged, time-ordered list of recent platform events.
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

try {
    $pdo = get_db();

    $activities = [];

    // Recent user registrations (last 10)
    $stmt = $pdo->query("
        SELECT id, name, username, created_at
        FROM users
        WHERE role = 'user'
        ORDER BY created_at DESC
        LIMIT 8
    ");
    foreach ($stmt->fetchAll() as $row) {
        $activities[] = [
            'type'     => 'user_registered',
            'icon'     => '👤',
            'label'    => 'New user registered',
            'detail'   => $row['name'] . ' (@' . $row['username'] . ')',
            'time'     => $row['created_at'],
            'time_ago' => time_ago($row['created_at'])
        ];
    }

    // Recent swap requests (last 10)
    $stmt = $pdo->query("
        SELECT sr.id, sr.status, sr.created_at,
               s.name AS sender_name, r.name AS receiver_name,
               sk_off.name AS offered_skill, sk_req.name AS requested_skill
        FROM swap_requests sr
        INNER JOIN users  s     ON sr.sender_id          = s.id
        INNER JOIN users  r     ON sr.receiver_id         = r.id
        INNER JOIN skills sk_off ON sr.offered_skill_id  = sk_off.id
        INNER JOIN skills sk_req ON sr.requested_skill_id = sk_req.id
        ORDER BY sr.created_at DESC
        LIMIT 8
    ");
    foreach ($stmt->fetchAll() as $row) {
        $statusLabel = [
            'pending'   => '🔄 Pending',
            'accepted'  => '✅ Accepted',
            'rejected'  => '❌ Rejected',
            'cancelled' => '🚫 Cancelled',
        ][$row['status']] ?? $row['status'];

        $activities[] = [
            'type'     => 'swap_request',
            'icon'     => '🔄',
            'label'    => 'Swap request — ' . $statusLabel,
            'detail'   => $row['sender_name'] . ' offered ' . $row['offered_skill'] . ' → ' . $row['receiver_name'],
            'time'     => $row['created_at'],
            'time_ago' => time_ago($row['created_at'])
        ];
    }

    // Recent skills added (last 5)
    $stmt = $pdo->query("
        SELECT id, name, category, created_at
        FROM skills
        ORDER BY created_at DESC
        LIMIT 5
    ");
    foreach ($stmt->fetchAll() as $row) {
        $activities[] = [
            'type'     => 'skill_added',
            'icon'     => '🎯',
            'label'    => 'Skill in catalog',
            'detail'   => $row['name'] . ' (' . $row['category'] . ')',
            'time'     => $row['created_at'],
            'time_ago' => time_ago($row['created_at'])
        ];
    }

    // Sort all by time descending
    usort($activities, fn($a, $b) => strcmp($b['time'], $a['time']));

    // Return top 15 most recent events
    json_response(true, 'Activity loaded.', array_slice($activities, 0, 15));

} catch (Exception $e) {
    error_log('[Admin Activity Error] ' . $e->getMessage());
    json_response(false, 'Failed to load activity.', null, 500);
}
