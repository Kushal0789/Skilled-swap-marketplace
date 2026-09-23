<?php
/**
 * Mark Notifications as Read API Endpoint
 * POST /api/notifications/read.php
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

$notifId = (int)($input['notification_id'] ?? 0);
$all = !empty($input['all']);

try {
    $pdo = get_db();

    if ($all) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    } elseif ($notifId > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notifId, ':uid' => $userId]);
    }

    json_response(true, 'Notifications updated.');

} catch (Exception $e) {
    error_log('[Mark Notifications Read Error] ' . $e->getMessage());
    json_response(false, 'Failed to update notifications.', null, 500);
}
