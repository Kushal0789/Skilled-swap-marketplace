<?php
/**
 * Admin User Management API Endpoint
 * GET /api/admin/users.php
 * POST /api/admin/users.php (Action: toggle_status, delete)
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = get_json_input();
    $action = $input['action'] ?? '';
    $targetUserId = (int)($input['user_id'] ?? 0);

    if ($targetUserId <= 0) {
        json_response(false, 'Target user ID required.', null, 422);
    }

    if ($targetUserId === current_user_id()) {
        json_response(false, 'You cannot modify your own administrative account.', null, 403);
    }

    try {
        if ($action === 'toggle_status') {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
            $stmt->execute([':id' => $targetUserId]);
            $currentStatus = $stmt->fetchColumn();

            if (!$currentStatus) {
                json_response(false, 'User not found.', null, 404);
            }

            $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';
            $upStmt = $pdo->prepare("UPDATE users SET status = :st WHERE id = :id");
            $upStmt->execute([':st' => $newStatus, ':id' => $targetUserId]);

            json_response(true, "User status updated to {$newStatus}.", ['status' => $newStatus]);
        } elseif ($action === 'delete') {
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $delStmt->execute([':id' => $targetUserId]);

            json_response(true, 'User permanently deleted.');
        } else {
            json_response(false, 'Invalid action specified.', null, 422);
        }
    } catch (Exception $e) {
        error_log('[Admin Users Action Error] ' . $e->getMessage());
        json_response(false, 'Operation failed.', null, 500);
    }
}

// GET: List users
try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.username, u.email, u.role, u.status, u.created_at,
               (SELECT COUNT(*) FROM user_skills WHERE user_id = u.id AND skill_type = 'OFFER') AS offered_count,
               (SELECT COUNT(*) FROM user_skills WHERE user_id = u.id AND skill_type = 'WANT') AS wanted_count
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    foreach ($users as &$u) {
        $u['created_formatted'] = date('M j, Y', strtotime($u['created_at']));
    }

    json_response(true, 'Users loaded.', $users);
} catch (Exception $e) {
    error_log('[Admin Users List Error] ' . $e->getMessage());
    json_response(false, 'Unable to load users.', null, 500);
}
