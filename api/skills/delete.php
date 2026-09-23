<?php
/**
 * Delete User Skill API Endpoint
 * POST /api/skills/delete.php
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
$userSkillId = (int)($input['user_skill_id'] ?? 0);

if ($userSkillId <= 0) {
    json_response(false, 'Invalid skill ID provided.', null, 422);
}

try {
    $pdo = get_db();

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM user_skills WHERE id = :id AND user_id = :uid LIMIT 1");
    $stmt->execute([':id' => $userSkillId, ':uid' => $userId]);
    if (!$stmt->fetch()) {
        json_response(false, 'Skill entry not found or unauthorized.', null, 404);
    }

    $deleteStmt = $pdo->prepare("DELETE FROM user_skills WHERE id = :id AND user_id = :uid");
    $deleteStmt->execute([':id' => $userSkillId, ':uid' => $userId]);

    json_response(true, 'Skill removed from your profile successfully.');

} catch (Exception $e) {
    error_log('[Delete User Skill Error] ' . $e->getMessage());
    json_response(false, 'Failed to remove skill.', null, 500);
}
