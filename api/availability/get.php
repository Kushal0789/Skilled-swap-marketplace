<?php
/**
 * Get User Availability API Endpoint
 * GET /api/availability/get.php?user_id=123 (defaults to current logged-in user)
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/availability.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed. Use GET.', null, 405);
}

$requestedId = isset($_GET['user_id']) && $_GET['user_id'] !== ''
    ? (int)$_GET['user_id']
    : current_user_id();

if (!$requestedId || $requestedId <= 0) {
    require_auth();
    $requestedId = current_user_id();
}

try {
    $pdo = get_db();

    // Ensure requested user exists and is active (public availability respects account status)
    $uStmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = :id LIMIT 1");
    $uStmt->execute([':id' => $requestedId]);
    $user = $uStmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        json_response(false, 'User not found.', null, 404);
    }

    $availability = get_user_availability($pdo, $requestedId);

    json_response(true, 'Availability loaded.', [
        'user_id'      => $requestedId,
        'is_own'       => (current_user_id() === $requestedId),
        'availability' => $availability
    ]);
} catch (Exception $e) {
    error_log('[Availability Get Error] ' . $e->getMessage());
    json_response(false, 'Unable to load availability.', null, 500);
}
