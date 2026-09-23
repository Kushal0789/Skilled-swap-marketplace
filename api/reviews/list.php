<?php
/**
 * List User Reviews API Endpoint
 * GET /api/reviews/list.php?user_id=123
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_GET['user_id'] ?? current_user_id() ?? 0);

if ($userId <= 0) {
    json_response(false, 'User ID required.', null, 422);
}

try {
    $pdo = get_db();

    $stmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at,
               u.id AS reviewer_id, u.name AS reviewer_name, u.username AS reviewer_username, u.profile_image AS reviewer_avatar
        FROM reviews r
        INNER JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewed_user_id = :uid
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $reviews = $stmt->fetchAll();

    foreach ($reviews as &$r) {
        $r['reviewer_avatar_url'] = get_avatar_url($r['reviewer_avatar']);
        $r['time_ago'] = time_ago($r['created_at']);
    }

    json_response(true, 'Reviews loaded.', $reviews);

} catch (Exception $e) {
    error_log('[List Reviews Error] ' . $e->getMessage());
    json_response(false, 'Failed to load reviews.', null, 500);
}
