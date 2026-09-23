<?php
/**
 * Admin Statistics API Endpoint
 * GET /api/admin/stats.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

try {
    $pdo = get_db();

    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
    $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND role != 'admin'")->fetchColumn();
    $totalSkills = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
    $totalSwaps = (int)$pdo->query("SELECT COUNT(*) FROM swap_requests")->fetchColumn();
    $acceptedSwaps = (int)$pdo->query("SELECT COUNT(*) FROM swap_requests WHERE status = 'accepted'")->fetchColumn();
    $pendingReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
    $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

    json_response(true, 'Admin stats loaded.', [
        'total_users'     => $totalUsers,
        'active_users'    => $activeUsers,
        'total_skills'    => $totalSkills,
        'total_swaps'     => $totalSwaps,
        'accepted_swaps'  => $acceptedSwaps,
        'pending_reports' => $pendingReports,
        'total_messages'  => $totalMessages
    ]);

} catch (Exception $e) {
    error_log('[Admin Stats Error] ' . $e->getMessage());
    json_response(false, 'Unable to load statistics.', null, 500);
}
