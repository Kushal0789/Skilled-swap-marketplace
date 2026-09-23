<?php
/**
 * Send Swap Request API Endpoint (Business Layer)
 * POST /api/requests/send.php
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

$senderId = current_user_id();
$input = get_json_input();

$receiverId = (int)($input['receiver_id'] ?? 0);
$offeredSkillId = (int)($input['offered_skill_id'] ?? 0);
$requestedSkillId = (int)($input['requested_skill_id'] ?? 0);
$message = trim($input['message'] ?? '');

if ($receiverId <= 0) {
    json_response(false, 'Invalid recipient.', null, 422);
}

if ($receiverId === $senderId) {
    json_response(false, 'You cannot send a swap request to yourself.', null, 422);
}

if ($offeredSkillId <= 0 || $requestedSkillId <= 0) {
    json_response(false, 'Please select both an offered skill and a requested skill.', null, 422);
}

try {
    $pdo = get_db();

    // Check receiver exists and is active
    $recStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND status = 'active'");
    $recStmt->execute([':id' => $receiverId]);
    $receiver = $recStmt->fetch();
    if (!$receiver) {
        json_response(false, 'Target user does not exist or is unavailable.', null, 404);
    }

    // Check BOTH skills exist in the database properly
    $skillStmt = $pdo->prepare("SELECT id FROM skills WHERE id = :s1 OR id = :s2");
    $skillStmt->execute([':s1' => $offeredSkillId, ':s2' => $requestedSkillId]);
    $skills = $skillStmt->fetchAll();
    
    // Determine expected distinct skill count (1 if same skill swapped, 2 if different skills)
    $expectedCount = ($offeredSkillId === $requestedSkillId) ? 1 : 2;

    if (count($skills) < $expectedCount) {
        json_response(false, 'One or both selected skills do not exist in the database.', null, 422);
    }

    // Prevent duplicate active requests (pending or accepted) between these users
    $dupStmt = $pdo->prepare("
        SELECT id, status FROM swap_requests
        WHERE ((sender_id = :s1 AND receiver_id = :r1) OR (sender_id = :r2 AND receiver_id = :s2))
          AND status IN ('pending', 'accepted')
        LIMIT 1
    ");
    $dupStmt->execute([
        ':s1' => $senderId, 
        ':r1' => $receiverId,
        ':r2' => $senderId,
        ':s2' => $receiverId
    ]);
    $existing = $dupStmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'pending') {
            json_response(false, 'There is already an active pending request between you and this user.', null, 409);
        } else {
            json_response(false, 'You already have an active skill swap connection with this user!', null, 409);
        }
    }

    // Insert swap request
    $insertStmt = $pdo->prepare("
        INSERT INTO swap_requests (sender_id, receiver_id, offered_skill_id, requested_skill_id, message, status, created_at)
        VALUES (:s, :r, :offered, :requested, :msg, 'pending', NOW())
    ");
    $insertStmt->execute([
        ':s'         => $senderId,
        ':r'         => $receiverId,
        ':offered'   => $offeredSkillId,
        ':requested' => $requestedSkillId,
        ':msg'       => $message
    ]);

    $requestId = (int)$pdo->lastInsertId();

    // Fetch sender name for notification
    $sender = current_user();
    $senderName = $sender['name'] ?? 'A member';

    // Notify receiver if helper exists
    if (function_exists('create_notification')) {
        create_notification(
            $pdo,
            $receiverId,
            'swap_received',
            'New Skill Swap Request',
            "{$senderName} sent you a skill swap request!",
            $requestId
        );
    }

    json_response(true, 'Swap request sent successfully! You will be notified when they respond.', [
        'request_id' => $requestId,
        'status'     => 'pending'
    ], 201);

} catch (PDOException $e) {
    error_log('[Send Swap Request PDO Error] ' . $e->getMessage());
    json_response(false, 'Database Error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    error_log('[Send Swap Request Error] ' . $e->getMessage());
    json_response(false, 'Failed to send swap request: ' . $e->getMessage(), null, 500);
}