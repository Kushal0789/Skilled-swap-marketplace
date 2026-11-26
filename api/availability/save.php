<?php
/**
 * Save User Availability API Endpoint
 * POST /api/availability/save.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/availability.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

$userId = current_user_id();
$input = get_json_input();

if (!verify_csrf_token($input['csrf_token'] ?? null)) {
    json_response(false, 'Security token invalid or expired. Please refresh the page.', null, 403, ['csrf_invalid']);
}

// Security guard: never allow editing another user's availability
if (isset($input['user_id']) && (int)$input['user_id'] !== $userId) {
    json_response(false, 'Unauthorized: you can only manage your own availability.', null, 403, ['forbidden']);
}

$rawStatus = $input['availability_status'] ?? ($input['status'] ?? '');
$rawSchedule = $input['schedule'] ?? ($input['slots'] ?? []);

$validation = validate_availability_payload($rawStatus, $rawSchedule);
if (!$validation['valid']) {
    json_response(false, $validation['errors'][0] ?? 'Invalid availability schedule.', null, 422, $validation['errors']);
}

try {
    $pdo = get_db();

    // Ensure user exists and is active
    $checkStmt = $pdo->prepare("SELECT id, status FROM users WHERE id = :id LIMIT 1");
    $checkStmt->execute([':id' => $userId]);
    $userRow = $checkStmt->fetch();
    if (!$userRow || $userRow['status'] !== 'active') {
        json_response(false, 'Account unavailable.', null, 403);
    }

    $saved = save_user_availability($pdo, $userId, $validation['status'], $validation['slots']);

    json_response(true, 'Availability saved successfully!', [
        'availability' => $saved
    ]);
} catch (Exception $e) {
    error_log('[Availability Save Error] ' . $e->getMessage());
    json_response(false, 'Unable to save availability right now. Please try again.', null, 500);
}
