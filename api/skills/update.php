<?php
/**
 * Update User Skill API Endpoint
 * POST /api/skills/update.php
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
$proficiency = trim($input['proficiency'] ?? 'Intermediate');
$description = trim($input['description'] ?? '');

if ($userSkillId <= 0) {
    json_response(false, 'Invalid user skill ID.', null, 422);
}

$validProficiencies = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
if (!in_array($proficiency, $validProficiencies, true)) {
    $proficiency = 'Intermediate';
}

try {
    $pdo = get_db();

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM user_skills WHERE id = :id AND user_id = :uid LIMIT 1");
    $stmt->execute([':id' => $userSkillId, ':uid' => $userId]);
    if (!$stmt->fetch()) {
        json_response(false, 'Skill entry not found or unauthorized.', null, 404);
    }

    $updateStmt = $pdo->prepare("
        UPDATE user_skills
        SET proficiency = :prof, description = :desc
        WHERE id = :id AND user_id = :uid
    ");
    $updateStmt->execute([
        ':prof' => $proficiency,
        ':desc' => $description,
        ':id'   => $userSkillId,
        ':uid'  => $userId
    ]);

    json_response(true, 'Skill details updated successfully.', [
        'id'          => $userSkillId,
        'proficiency' => $proficiency,
        'description' => $description
    ]);

} catch (Exception $e) {
    error_log('[Update User Skill Error] ' . $e->getMessage());
    json_response(false, 'Failed to update skill.', null, 500);
}
