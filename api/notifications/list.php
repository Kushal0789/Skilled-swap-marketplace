<?php
/**
 * Notifications List API Endpoint
 * GET /api/notifications/list.php
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

    $stmt = $pdo->prepare("
        SELECT id, type, title, message, reference_id, is_read, created_at
        FROM notifications
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 25
    ");
    $stmt->execute([':uid' => $userId]);
    $notifications = $stmt->fetchAll();

    foreach ($notifications as &$n) {
        $n['time_ago'] = time_ago($n['created_at']);
        $n['is_read'] = (int)$n['is_read'];
    }

    $unreadCount = get_unread_notifications_count($pdo, $userId);

    json_response(true, 'Notifications loaded.', $notifications);

} catch (Exception $e) {
    error_log('[Notifications List Error] ' . $e->getMessage());
    json_response(false, 'Unable to load notifications.', null, 500);
}
